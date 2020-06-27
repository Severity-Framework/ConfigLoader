<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit\Builder;

use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;

/**
 * Class ConfigFileTest
 *
 * @covers \Severity\ConfigLoader\Builder\ConfigFile
 */
class ConfigFileTest extends ConfigLoaderTestCase
{
    protected vfsStreamDirectory $path;

    protected function setUp(): void
    {
        $this->path = vfsStream::setup();
    }

    /**
     * Tests {@see ConfigFile::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotExistingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/(does not exist)/');

        new ConfigFile('not_existing_file.yaml');
    }
    /**
     * Tests {@see ConfigFile::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotReadable(): void
    {
        $mockFile = $this->mockFile('some_file.yaml');
        $mockFile->chmod(0000);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/(is not readable)/');

        new ConfigFile($mockFile->url());
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
