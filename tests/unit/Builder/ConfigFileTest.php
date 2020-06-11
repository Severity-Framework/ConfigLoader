<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Builder;

use InvalidArgumentException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Tests\ConfigLoaderTestCase;

/**
 * Class ConfigFileTest
 *
 * @covers \Severity\ConfigLoader\Builder\ConfigFile
 */
class ConfigFileTest extends ConfigLoaderTestCase
{
    /**
     * Tests {@see ConfigFile::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotExistingFile(): void
    {
        $filePath = $this->getFixturePath('not/existing/file.yaml');

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
        $filePath = $this->getFixturePath('Builder/ConfigFile/unreadable_example.yaml');

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
        $filePath = $this->getFixturePath('Builder/ConfigFile/valid_example1.yaml');

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
        $testPath = $this->getFixturePath('Builder/ConfigFile/valid_example1.yaml');

        $mockFile = new ConfigFile($testPath);

        $this->assertSame($testPath, $mockFile->getPath());
    }
}
