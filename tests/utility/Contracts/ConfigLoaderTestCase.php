<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Utility\Contracts;

use org\bovigo\vfs\vfsStreamDirectory;
use org\bovigo\vfs\vfsStreamFile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Severity\ConfigLoader\Builder\ConfigFile;
use function dirname;
use function explode;
use function strpos;
use function strrpos;
use function substr;

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

    public static function getCachePath(string $path): string
    {
        return self::$cwd . "cache/$path";
    }

    protected function mockFile(string $name, ?string $content = null, ?int $lastMTime = null): vfsStreamFile
    {
        $currentPath = $this->path;
        if (strpos($name, '/') !== false) {
            foreach (explode(',', dirname($name)) as $part) {
                $newPath = $currentPath->getChild($part);
                if ($newPath === null) {
                    $currentPath->addChild($newPath = new vfsStreamDirectory($part));
                }

                $currentPath = $newPath;
            }

            $name = substr($name, strrpos($name, '/') + 1);
        }

        $mockFile = new vfsStreamFile($name);

        if ($content !== null) {
            $mockFile->setContent($content);
        }

        if ($lastMTime !== null) {
            $mockFile->lastModified($lastMTime);
        }

        $currentPath->addChild($mockFile);

        return $mockFile;
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