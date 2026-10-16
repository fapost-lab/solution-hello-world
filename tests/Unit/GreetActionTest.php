<?php

declare(strict_types=1);

namespace Fapost\HelloWorld\Tests\Unit;

use Fapost\Foundation\Flow\Call\CallContext;
use Fapost\HelloWorld\Actions\GreetAction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GreetActionTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function worldCases(): array
    {
        return [
            'missing'  => [[]],
            'null'     => [['name' => null]],
            'empty'    => [['name' => '']],
            'blank'    => [['name' => "  \n "]],
            'not text' => [['name' => ['Ada']]],
        ];
    }

    public function test_it_declares_its_id_and_version(): void
    {
        $action = new GreetAction();

        $this->assertSame('hello.greet', $action->id());
        $this->assertSame(1, $action->version());
    }

    public function test_it_greets_the_name_it_is_given(): void
    {
        $this->assertSame(['greeting' => 'Hello, Ada!'], $this->greet(['name' => 'Ada']));
    }

    public function test_it_trims_the_name(): void
    {
        $this->assertSame(['greeting' => 'Hello, Ada!'], $this->greet(['name' => "  Ada\t"]));
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    #[DataProvider('worldCases')]
    public function test_it_greets_the_world_without_a_usable_name(array $parameters): void
    {
        $this->assertSame(['greeting' => 'Hello, world!'], $this->greet($parameters));
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array{greeting: string}
     */
    private function greet(array $parameters): array
    {
        return (new GreetAction())->handle($parameters, new CallContext('tenant', 'contact', 'session', 'node', 'key'));
    }
}
