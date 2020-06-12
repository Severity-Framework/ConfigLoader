<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Utility\Contracts;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Severity\ConfigLoader\Builder\ConfigFile;
use function dirname;

class ConfigLoaderTestCase extends TestCase
{
    protected static string $cwd;

    public static function setUpBeforeClass(): void
    {
        self::$cwd = dirname(dirname(__DIR__)) . '/';
    }

    public static function getFixturePath(string $path): string
    {
        return self::$cwd . "fixtures/$path";
    }

    /**
     * @param mixed $returnValue
     *
     * @return ConfigFile|MockObject
     */
    public function mockConfigFileWithFetch($returnValue): ConfigFile
    {
        $mock = $this->createMock(ConfigFile::class);
        $mock->method('fetch')
            ->willReturn($returnValue);

        return $mock;
    }

    /**
     * @param mixed $path
     *
     * @return ConfigFile|MockObject
     */
    public function mockConfigFileWithPath($path): ConfigFile
    {
        $mock = $this->createMock(ConfigFile::class);
        $mock->method('getPath')
            ->willReturn($path);

        return $mock;
    }
}