<?php

declare(strict_types=1);

namespace Fapost\HelloWorld\Tests\Feature;

use App\Domains\Flow\Action\ActionHandlerRegistry;
use App\Domains\Flow\Call\CallTester;
use App\Domains\Solution\Services\InstalledSolutions;
use App\Domains\Solution\Services\SolutionUiPublisher;
use App\Domains\Staff\Enums\RoleEnum;
use App\Domains\Staff\Models\User;
use App\Domains\Tenancy\Contracts\TenantRepositoryInterface;
use App\Domains\Tenancy\Services\TenantSwitcher;
use App\Domains\Tenancy\Support\TenantHost;
use Database\Seeders\TenantAclSeeder;
use Fapost\HelloWorld\Actions\GreetAction;
use Fapost\HelloWorld\HelloWorldServiceProvider;
use Fapost\HelloWorld\Tests\CoreDatabaseTestCase;
use Illuminate\Filesystem\Filesystem;

/**
 * The Solution inside the real Core: installed from Composer, validated, its action registered, its bundle
 * published and its page served to a tenant that activated it, and to nobody else.
 */
final class HelloWorldSolutionTest extends CoreDatabaseTestCase
{
    private string $publicPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Publication writes into the web root; a throwaway one keeps Core's own public/ untouched.
        $this->publicPath = sys_get_temp_dir() . '/fapost-hello-' . bin2hex(random_bytes(6));
        mkdir($this->publicPath, 0o777, true);
        $this->app->usePublicPath($this->publicPath);
        // Pages are rendered without a Vite manifest: the test asserts on the response, not on compiled assets.
        $this->withoutVite();
        $this->seed(TenantAclSeeder::class);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->deleteDirectory($this->publicPath);

        parent::tearDown();
    }

    public function test_core_boots_the_solution_provider_from_its_manifest(): void
    {
        $this->assertInstanceOf(HelloWorldServiceProvider::class, $this->app->getProvider(HelloWorldServiceProvider::class));
    }

    public function test_it_is_in_the_installed_catalog_with_a_valid_manifest(): void
    {
        $solution = collect($this->app->make(InstalledSolutions::class)->all())->firstWhere('id', 'hello');

        $this->assertNotNull($solution);
        $this->assertSame('fapost/solution-hello-world', $solution->package);
        $this->assertSame(HelloWorldServiceProvider::class, $solution->provider);
    }

    public function test_the_update_command_validates_the_manifests(): void
    {
        $this->artisan('platform:update', ['--validate-only' => true])
            ->expectsOutputToContain('Solution manifests are valid')
            ->assertExitCode(0);
    }

    public function test_the_action_resolves_in_the_registry(): void
    {
        $registry = $this->app->make(ActionHandlerRegistry::class);

        $this->assertTrue($registry->has('hello.greet'));
        $this->assertSame('hello', $registry->ownerOf('hello.greet'));
        $this->assertInstanceOf(GreetAction::class, $registry->get('hello.greet'));
    }

    public function test_the_ui_bundle_is_accepted_and_published(): void
    {
        $published = $this->publishBundle();

        $this->assertSame('hello', $published->solutionId);
        $this->assertDirectoryExists($this->publicPath . '/solutions/hello');
    }

    public function test_the_page_is_served_to_staff_once_the_solution_is_activated(): void
    {
        $this->publishBundle();
        $this->artisan('solutions:activate', ['solution' => 'hello', '--tenant' => 'main'])->assertExitCode(0);
        $this->forgetSolutionState();

        $this->actingAs($this->admin())
            ->get($this->panelUrl('/apps/hello/home'))
            ->assertOk()
            // A Solution page is a prebuilt module, not a file of Core's pages directory: the file check is off.
            ->assertInertia(fn ($page) => $page->component('hello::Home', false)->where('greeting', 'Hello, world!'));
    }

    public function test_the_page_is_not_found_before_activation(): void
    {
        $this->publishBundle();

        $this->actingAs($this->admin())
            ->get($this->panelUrl('/apps/hello/home'))
            ->assertNotFound();
    }

    public function test_the_page_is_not_found_again_after_deactivation(): void
    {
        $this->publishBundle();
        $this->artisan('solutions:activate', ['solution' => 'hello', '--tenant' => 'main'])->assertExitCode(0);
        $this->forgetSolutionState();
        $this->actingAs($this->admin())->get($this->panelUrl('/apps/hello/home'))->assertOk();

        $this->artisan('solutions:deactivate', ['solution' => 'hello', '--tenant' => 'main'])->assertExitCode(0);
        $this->forgetSolutionState();

        $this->get($this->panelUrl('/apps/hello/home'))->assertNotFound();
    }

    public function test_a_call_node_reaches_the_action_while_the_solution_is_active(): void
    {
        $this->artisan('solutions:activate', ['solution' => 'hello', '--tenant' => 'main'])->assertExitCode(0);
        $this->forgetSolutionState();

        $result = $this->testCall(['name' => ' Ada ']);

        $this->assertTrue($result['success']);
        $this->assertSame('Hello, Ada!', $result['body']['greeting']);
    }

    public function test_a_call_node_is_refused_while_the_solution_is_not_active(): void
    {
        $result = $this->testCall(['name' => 'Ada']);

        $this->assertFalse($result['success']);
        $this->assertSame('solution_inactive', $result['error_code']);
    }

    /**
     * The builder's test call: the same `call` node handler transport a flow uses, inside the tenant.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     */
    private function testCall(array $parameters): array
    {
        $tenant = $this->app->make(TenantRepositoryInterface::class)->findBySlug('main');
        $this->assertNotNull($tenant);

        return $this->app->make(TenantSwitcher::class)->runForTenant(
            $tenant,
            fn (): array => $this->app->make(CallTester::class)->run(['transport' => 'handler', 'target' => 'hello.greet', 'parameters' => $parameters], []),
        );
    }

    private function publishBundle(): object
    {
        $publisher = $this->app->make(SolutionUiPublisher::class);
        $check     = $publisher->check($this->app->make(InstalledSolutions::class)->all());

        // Other installed Solutions are not this package's business: only hello's problems count.
        $this->assertSame([], array_values(array_filter($check['problems'], static fn (string $problem): bool => str_starts_with($problem, 'hello:'))));
        $this->assertArrayHasKey('hello', $check['sources']);

        $published = $publisher->publish(['hello' => $check['sources']['hello']]);
        $this->forgetSolutionState();

        return $published[0];
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleEnum::Admin->value);

        return $user;
    }

    private function panelUrl(string $path): string
    {
        $host = TenantHost::forDefaultTenant();

        return null === $host ? $path : 'http://' . $host . '/' . mb_ltrim($path, '/');
    }
}
