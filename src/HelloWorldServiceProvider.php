<?php

declare(strict_types=1);

namespace Fapost\HelloWorld;

use Fapost\Foundation\Console\Contracts\ConsoleExtensionsInterface;
use Fapost\Foundation\Console\DTO\ConsoleApplication;
use Fapost\Foundation\Console\DTO\MenuItem;
use Fapost\Foundation\Contracts\CoreRegistrarInterface;
use Fapost\Foundation\Lifecycle\AbstractSolutionServiceProvider;
use Fapost\HelloWorld\Actions\GreetAction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Gate;

/**
 * The provider of the Solution `hello`: one action for the `call` node and one console application with one page.
 * Core boots it from the manifest in composer.json; there is no Laravel package discovery.
 */
final class HelloWorldServiceProvider extends AbstractSolutionServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'hello');

        // A real Solution checks its own policy or permission here.
        Gate::define('hello.use', static fn (Authenticatable $user): bool => true);

        $console = $this->app->make(ConsoleExtensionsInterface::class);

        $console->application(new ConsoleApplication(
            label: 'hello::console.app',
            icon: 'hand',
            entryRoute: 'solutions.hello.home',
            menu: [new MenuItem('hello::console.menu.home', 'solutions.hello.home', 'house')],
            ability: 'hello.use',
            shortLabel: 'hello::console.short',
        ));

        $console->routes(static function (Router $router): void {
            $router->get('home', [HelloWorldController::class, 'home'])->name('home');
        });
    }

    protected function registerExtensions(CoreRegistrarInterface $registrar): void
    {
        $registrar->registerActionHandler(GreetAction::class);
    }
}
