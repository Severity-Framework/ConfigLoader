<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit\Builder;

use InvalidArgumentException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use function chmod;

/**
 * Class ConfigFileTest
 *
 * @covers \Severity\ConfigLoader\Builder\ConfigFile
 */
class ConfigFileTest extends ConfigLoaderTestCase
{
    public static function getFiles(): array
    {
        return glob(self::getFixturePath('Builder/ConfigFile/unreadable_*.yaml'));
    }

    /**
     * Sets fixture files - prefixed with unreadable - to 0000 permission.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        umask(0000);

        foreach (self::getFiles() as $file) {
            chmod($file, 0000);
        }
    }

    /**
     * Sets fixture files - prefixed with unreadable - back to 0644 permission.
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        parent::setUpBeforeClass();

        foreach (self::getFiles() as $file) {
            chmod($file, 0644);
        }
    }

    /**
     * Tests {@see ConfigFile::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotExistingFile(): void
    {
        $filePath = self::getFixturePath('not/existing/file.yaml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/(does not exist)/');

        new ConfigFile($filePath);
    }
    /**
     * Tests {@see ConfigFile::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotReadable(): void
    {
        $filePath = self::getFixturePath('Builder/ConfigFile/unreadable_example.yaml');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/(is not readable)/');

        new ConfigFile($filePath);
    }

    /**
     * Tests {@see ConfigFile::fetch()} method.
     *
     * @return void
     */
    public function testFetchWithValidContent(): void
    {
        $filePath = self::getFixturePath('Builder/ConfigFile/valid_example1.yaml');

        $configFile = new ConfigFile($filePath);

        $expected = [
            'parameters' => [
                'foo' => 'bar'
            ]
        ];

        $this->assertSame($expected, $configFile->fetch());
    }

    /**
     * Tests {@see ConfigFile::getPath()} method.
     *
     * @return void
     */
    public function testPath(): void
    {
        $testPath = self::getFixturePath('Builder/ConfigFile/valid_example1.yaml');

        $mockFile = new ConfigFile($testPath);

        $this->assertSame($testPath, $mockFile->getPath());
    }
}
