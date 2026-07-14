<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class LayerDependenciesTest extends TestCase
{
    private const array FORBIDDEN_REFERENCES = [
        'Domain' => [
            'App\\Application\\',
            'App\\Presentation\\',
            'App\\Infrastructure\\',
            'Illuminate\\',
            'Laravel\\',
        ],
        'Application' => [
            'App\\Presentation\\',
            'App\\Infrastructure\\',
            'Illuminate\\',
            'Laravel\\',
        ],
        'Presentation' => [
            'App\\Domain\\',
            'App\\Infrastructure\\',
        ],
        'Infrastructure' => [
            'App\\Presentation\\',
        ],
    ];

    public function test_legacy_laravel_app_source_root_is_not_used(): void
    {
        self::assertDirectoryDoesNotExist(self::projectPath('app'));
        self::assertDirectoryDoesNotExist(self::projectPath('routes'));
        self::assertDirectoryDoesNotExist(self::projectPath('database'));
    }

    /** @return iterable<string, array{string, string}> */
    public static function layerFiles(): iterable
    {
        foreach (array_keys(self::FORBIDDEN_REFERENCES) as $layer) {
            $directory = self::projectPath('src/'.$layer);
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    yield $layer.': '.$file->getPathname() => [$layer, $file->getPathname()];
                }
            }
        }
    }

    #[DataProvider('layerFiles')]
    public function test_namespace_matches_top_level_layer(string $layer, string $file): void
    {
        $contents = $this->contentsOf($file);

        self::assertMatchesRegularExpression(
            '/namespace App\\\\'.preg_quote($layer, '/').'\\\\/',
            $contents,
            sprintf('%s must use the App\\%s namespace.', $file, $layer),
        );
    }

    #[DataProvider('layerFiles')]
    public function test_layer_does_not_reference_forbidden_dependencies(string $layer, string $file): void
    {
        $contents = $this->contentsOf($file);

        foreach (self::FORBIDDEN_REFERENCES[$layer] as $forbiddenReference) {
            self::assertStringNotContainsString(
                $forbiddenReference,
                $contents,
                sprintf('%s layer must not reference %s in %s.', $layer, $forbiddenReference, $file),
            );
        }
    }

    private function contentsOf(string $file): string
    {
        $contents = file_get_contents($file);

        self::assertIsString($contents);

        return $contents;
    }

    private static function projectPath(string $path): string
    {
        return dirname(__DIR__, 2).'/'.$path;
    }
}
