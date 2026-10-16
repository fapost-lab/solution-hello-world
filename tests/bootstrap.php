<?php

declare(strict_types=1);

/**
 * Test bootstrap: the package is tested inside the real FaPost Core application.
 *
 * Core's autoloader knows the package's `src/` (Composer installed it through the
 * composer overlay) but not `tests/`, because Composer autoloads only the root
 * package's autoload-dev. The tests namespace is registered here.
 *
 * Core's `Fapost\HelloWorld\` mapping points at the main checkout of the package (a path repository
 * symlinked into vendor/). When the tests run from a git worktree they must exercise the
 * worktree's own `src/`, so a prepended autoloader for it is registered after Core's. When the
 * worktree is the main checkout the two mappings are the same directory and nothing changes.
 */

$coreRoot = getenv('FAPOST_CORE_PATH') ?: findCoreRoot(__DIR__);

/**
 * Walks up to the first ancestor that is a Core checkout (has `artisan` and `vendor/autoload.php`),
 * so both packages/solution-hello-world and packages/solution-hello-world.worktrees/<id> find Core without the variable.
 */
function findCoreRoot(string $from): string
{
    $dir = dirname($from);

    while (true) {
        if (is_file($dir . '/artisan') && is_file($dir . '/vendor/autoload.php')) {
            return $dir;
        }

        $parent = dirname($dir);

        if ($parent === $dir) {
            return dirname($from, 3);
        }

        $dir = $parent;
    }
}

$autoload = $coreRoot . '/vendor/autoload.php';

if (! is_file($autoload)) {
    fwrite(STDERR, "FaPost Core was not found at {$coreRoot}. Set FAPOST_CORE_PATH, or check the package out into Core's packages/ directory.\n");

    exit(1);
}

require $autoload;

spl_autoload_register(static function (string $class): void {
    $prefix = 'Fapost\\HelloWorld\\';

    if (! str_starts_with($class, $prefix) || str_starts_with($class, $prefix . 'Tests\\')) {
        return;
    }

    $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', mb_substr($class, mb_strlen($prefix))) . '.php';

    if (is_file($file)) {
        require $file;
    }
}, true, true);

spl_autoload_register(static function (string $class): void {
    $prefix = 'Fapost\\HelloWorld\\Tests\\';

    if (! str_starts_with($class, $prefix)) {
        return;
    }

    $file = __DIR__ . '/' . str_replace('\\', '/', mb_substr($class, mb_strlen($prefix))) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

// TestCase reads the same root, so it is exported for the process.
putenv('FAPOST_CORE_PATH=' . $coreRoot);
$_ENV['FAPOST_CORE_PATH'] = $coreRoot;
