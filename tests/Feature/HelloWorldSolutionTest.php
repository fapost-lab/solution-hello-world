<?php

declare(strict_types=1);

namespace Fapost\HelloWorld\Tests\Feature;

use Fapost\Foundation\Solution\Contracts\InstalledSolutionCatalogInterface;
use Fapost\HelloWorld\Actions\GreetAction;
use Fapost\HelloWorld\HelloWorldServiceProvider;
use Fapost\Testing\SolutionTestCase;
use Inertia\Testing\AssertableInertia;

/**
 * The Solution inside the real Core: installed from Composer, validated, its action registered, its bundle
 * published and its page served to a tenant that activated it, and to nobody else.
 */
final class HelloWorldSolutionTest extends SolutionTestCase
{
    public function test_core_boots_the_solution_provider_from_its_manifest(): void
    {
        $this->assertInstanceOf(HelloWorldServiceProvider::class, $this->app->getProvider(HelloWorldServiceProvider::class));
    }

    public function test_it_is_in_the_installed_catalog_with_a_valid_manifest(): void
    {
        $solution = $this->app->make(InstalledSolutionCatalogInterface::class)->find('hello');

        $this->assertNotNull($solution);
        $this->assertSame('Hello World', $solution->name);
    }

    public function test_the_update_command_validates_the_manifests(): void
    {
        $this->artisan('platform:update', ['--validate-only' => true])
            ->expectsOutputToContain('Solution manifests are valid')
            ->assertExitCode(0);
    }

    public function test_the_action_resolves_in_the_registry(): void
    {
        $this->assertActionRegistered('hello.greet', GreetAction::class);
    }

    public function test_the_ui_bundle_is_accepted_and_published(): void
    {
        $this->publishSolutionUi('hello');

        $this->assertDirectoryExists(public_path('solutions/hello'));
    }

    public function test_the_page_is_served_to_staff_once_the_solution_is_activated(): void
    {
        $this->publishSolutionUi('hello');
        $this->activateSolution('hello');

        $this->assertSolutionPage(
            $this->actingAsStaff()->getConsole('/apps/hello/home'),
            'hello::Home',
            fn (AssertableInertia $page) => $page->where('greeting', 'Hello, world!'),
        );
    }

    public function test_the_page_is_not_found_before_activation(): void
    {
        $this->publishSolutionUi('hello');

        $this->actingAsStaff()->getConsole('/apps/hello/home')->assertNotFound();
    }

    public function test_the_page_is_not_found_again_after_deactivation(): void
    {
        $this->publishSolutionUi('hello');
        $this->activateSolution('hello');
        $this->assertSolutionPage($this->actingAsStaff()->getConsole('/apps/hello/home'), 'hello::Home');

        $this->deactivateSolution('hello');

        $this->getConsole('/apps/hello/home')->assertNotFound();
    }

    public function test_a_call_node_reaches_the_action_while_the_solution_is_active(): void
    {
        $this->activateSolution('hello');

        $result = $this->callAction('hello.greet', ['name' => ' Ada '])->assertSucceeded();

        $this->assertSame('Hello, Ada!', $result->body['greeting']);
    }

    public function test_a_call_node_is_refused_while_the_solution_is_not_active(): void
    {
        $this->callAction('hello.greet', ['name' => 'Ada'])->assertRefused('solution_inactive');
    }
}
