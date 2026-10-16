<?php

declare(strict_types=1);

namespace Fapost\HelloWorld\Actions;

use Fapost\Foundation\Action\ActionHandlerInterface;
use Fapost\Foundation\Flow\Call\CallContext;

/**
 * The `hello.greet` action: greets the `name` parameter, or the world when there is none.
 * Pure: it reads nothing and changes nothing, so a flow can call it anywhere.
 */
final class GreetAction implements ActionHandlerInterface
{
    /**
     * The greeting for a name; anything but a non-blank string greets the world.
     */
    public static function greeting(mixed $name): string
    {
        $name = is_string($name) ? mb_trim($name) : '';

        return sprintf('Hello, %s!', '' === $name ? 'world' : $name);
    }

    public function id(): string
    {
        return 'hello.greet';
    }

    public function version(): int
    {
        return 1;
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array{greeting: string}
     */
    public function handle(array $parameters, CallContext $context): array
    {
        return ['greeting' => self::greeting($parameters['name'] ?? null)];
    }
}
