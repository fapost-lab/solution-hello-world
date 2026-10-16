<?php

declare(strict_types=1);

namespace Fapost\HelloWorld\Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Boots FaPost Core's own application, so a test runs against the real Core with
 * this package discovered into it, not against a stand-in framework.
 */
abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = require $this->coreRoot() . '/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    private function coreRoot(): string
    {
        return (string) (getenv('FAPOST_CORE_PATH') ?: dirname(__DIR__, 3));
    }
}
