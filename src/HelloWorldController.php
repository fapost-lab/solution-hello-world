<?php

declare(strict_types=1);

namespace Fapost\HelloWorld;

use Fapost\HelloWorld\Actions\GreetAction;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The page of the Solution. Access is the route group's (`can:hello.use` of the application).
 */
final class HelloWorldController
{
    public function home(): Response
    {
        return Inertia::render('hello::Home', [
            'title'    => __('hello::console.app'),
            'greeting' => GreetAction::greeting(null),
        ]);
    }
}
