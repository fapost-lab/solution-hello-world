<?php

declare(strict_types=1);

namespace Fapost\HelloWorld\Tests\Unit;

use Fapost\Foundation\Console\Bundle\UiBundleParser;
use Fapost\Foundation\Solution\Manifest\ManifestParser;
use PHPUnit\Framework\TestCase;

/**
 * The package's declarations are checked with Foundation's parsers, the same ones Core runs on `platform:update`.
 */
final class ManifestTest extends TestCase
{
    public function test_composer_json_holds_a_valid_manifest(): void
    {
        $result = (new ManifestParser())->parse($this->decode('composer.json'));

        $this->assertSame([], $result->lines());
        $this->assertSame('hello', $result->manifest?->id);
        $this->assertSame(['hello.greet'], $result->manifest->actions);
    }

    public function test_the_ui_bundle_declaration_is_valid_and_its_files_exist(): void
    {
        $result = (new UiBundleParser())->parse($this->decode('dist/fapost-ui.json'), 'hello');

        $this->assertSame([], $result->lines());

        foreach ($result->bundle->pages ?? [] as $file) {
            $this->assertFileExists(dirname(__DIR__, 2) . '/dist/' . $file);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $file): array
    {
        return json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/' . $file), true, 512, JSON_THROW_ON_ERROR);
    }
}
