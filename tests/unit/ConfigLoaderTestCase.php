<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests;

use PHPUnit\Framework\TestCase;
use function dirname;

class ConfigLoaderTestCase extends TestCase
{
    protected static string $cwd;

    public static function setUpBeforeClass(): void
    {
        self::$cwd = dirname(__DIR__) . '/';
    }

    public function getFixturePath(string $path): string
    {
        return self::$cwd . "fixtures/$path";
    }
}