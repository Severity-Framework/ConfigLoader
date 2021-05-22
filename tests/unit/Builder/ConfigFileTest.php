<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit\Builder;

use InvalidArgumentException;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use Severity\ConfigLoader\Builder\YamlFileResource;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;

/**
 * Class ConfigFileTest
 *
 * @covers \Severity\ConfigLoader\Builder\YamlFileResource
 */
class ConfigFileTest extends ConfigLoaderTestCase
{
    protected vfsStreamDirectory $path;

    protected function setUp(): void
    {
        $this->path = vfsStream::setup();
    }

    /**
     * Tests {@see YamlFileResource::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotExistingFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/(does not exist)/');

        new YamlFileResource('not_existing_file.yaml');
    }
    /**
     * Tests {@see YamlFileResource::__construct()}
     *
     * @return void
     */
    public function testConstructorForNotReadable(): void
    {
        $mockFile = $this->mockFile('some_file.yaml');
        $mockFile->chmod(0000);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/(is not readable)/');

        new YamlFileResource($mockFile->url());
    }

    /**
     * Tests {@see YamlFileResource::fetch()} method.
     *
     * @return void
     */
    public function testFetchWithValidContent(): void
    {
        $filePath = self::getFixturePath('Builder/ConfigFile/valid_example1.yaml');

        $configFile = new YamlFileResource($filePath);

        $expected = [
            'parameters' => [
                'foo' => 'bar'
            ]
        ];

        $this->assertSame($expected, $configFile->fetch());
    }

    /**
     * Tests {@see YamlFileResource::getPath()} method.
     *
     * @return void
     */
    public function testPath(): void
    {
        $testPath = self::getFixturePath('Builder/ConfigFile/valid_example1.yaml');

        $mockFile = new YamlFileResource($testPath);

        $this->assertSame($testPath, $mockFile->getPath());
    }
}
