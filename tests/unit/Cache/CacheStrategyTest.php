<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit\Cache;

use BadMethodCallException;
use DateTime;
use Exception;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Cache\CacheStrategy;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;
use function crc32;
use function file_put_contents;
use function serialize;
use function strrpos;
use function substr;
use function time;

/**
 * Class CacheConfigurationTest
 *
 * @covers \Severity\ConfigLoader\Cache\CacheStrategy
 */
class CacheStrategyTest extends ConfigLoaderTestCase
{
    use VisibilityHelper;

    protected vfsStreamDirectory $path;

    protected function setUp(): void
    {
        $this->path = vfsStream::setup();
    }

    /**
     * @param string $path
     * @param array $content
     *
     * @return ConfigFile|MockObject
     */
    protected function mockConfigFile(string $path, array $content): ConfigFile
    {
        $configFileMock = $this->createMock(ConfigFile::class);
        $configFileMock->method('getPath')
                       ->willReturn($path);
        $configFileMock->method('fetch')
                       ->willReturn($content);

        return $configFileMock;
    }

    /**
     * Tests {@see CacheStrategy::__construct()} with empty file list.
     *
     * @return void
     */
    public function testConstructWithEmpty(): void
    {
        $config = new CacheStrategy([], $this->path->url() . '/cache');

        $property = $this->getProperty($config, 'files');

        $this->assertEquals([], $property);
    }

    /**
     * Tests {@see CacheStrategy::__construct()} with non-empty file list.
     *
     * @return void
     */
    public function testConstructWithNonEmpty(): void
    {
        $configFileMock = $this->mockConfigFileWithPath('SomePath');

        $config = new CacheStrategy([
            $configFileMock
        ], $this->path->url() . '/cache');

        $property = $this->getProperty($config, 'files');

        $this->assertCount(1, $property);
        $this->assertSame($configFileMock, $property[0]);
    }

    /**
     * Tests {@see CacheStrategy::shouldGenerate()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testShouldGenerateWithNoGenerateCase(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = 'config/example_a.yaml';
        $mockA = $this->mockFile($configFilePathA, null, (new DateTime('-2 hours'))->getTimestamp());

        $configFilePathA = $mockA->url();
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = 'config/example_b.yaml';
        $mockB = $this->mockFile($configFilePathB, null, (new DateTime('-1 hours -31 minutes'))->getTimestamp());

        $configFilePathB = $mockB->url();
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        // @todo: introduce a cache key generation policy class
        $cacheFileKey = substr((string) crc32($mockA->url() . $mockB->url()), 0, 16);

        $cacheContent = "<?php return [
            '{$configFileMockA->getPath()}',
            '{$configFileMockB->getPath()}'
        ];";

        $this->mockFile("cache/{$cacheFileKey}.cache.properties", $cacheContent);
        $this->mockFile("cache/{$cacheFileKey}.cache", null, time());
        //</editor-fold>

        $config = new CacheStrategy([$configFileMockA, $configFileMockB], $this->path->url() . '/cache/');

        $this->assertFalse($config->shouldGenerate());
    }

    /**
     * Tests {@see CacheStrategy::shouldGenerate()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testShouldGenerateWithGenerateCase(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = 'config/example_c.yaml';
        $mockA = $this->mockFile($configFilePathA, null, (new DateTime('-4 seconds'))->getTimestamp());

        $configFilePathA = $mockA->url();
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = 'config/example_d.yaml';
        $mockB = $this->mockFile($configFilePathB, null, (new DateTime('-1 minutes'))->getTimestamp());

        $configFilePathB = $mockB->url();
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        // @todo: introduce a cache key generation policy class
        $cacheFileKey = substr((string) crc32($mockA->url() . $mockB->url()), 0, 16);

        $cacheContent = "<?php return [
            '{$configFileMockA->getPath()}',
            '{$configFileMockB->getPath()}'
        ];";

        // Create cache file
        $this->mockFile("cache/{$cacheFileKey}.cache.properties", $cacheContent);
        $this->mockFile("cache/{$cacheFileKey}.cache", null, (new DateTime('-1hours -31 minutes'))->getTimestamp());
        //</editor-fold>

        $config = new CacheStrategy([$configFileMockA, $configFileMockB], $this->path->url() . '/cache/');
        $this->assertTrue($config->shouldGenerate());
    }

    /**
     * Tests {@see CacheStrategy::fetchCache()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testFetchCache(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = 'config/example_e.yaml';
        $mockA = $this->mockFile($configFilePathA, null, (new DateTime('-4 seconds'))->getTimestamp());

        $configFilePathA = $mockA->url();
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = 'config/example_f.yaml';
        $mockB = $this->mockFile($configFilePathB, null, (new DateTime('-1 minutes'))->getTimestamp());

        $configFilePathB = $mockB->url();
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        $cacheFileKey = substr((string) crc32($configFilePathA . $configFilePathB), 0, 16);
        // @todo: introduce a cache key generation policy class

        $cacheContent = [
            'parameters' => [
                'param' => 'foo'
            ],
            'services' => [
                'bar' =>' baz'
            ]
        ];

        file_put_contents($this->getCachePath("{$cacheFileKey}.cache"), serialize($cacheContent));
        $this->mockFile("cache/{$cacheFileKey}.cache", serialize($cacheContent));
        //</editor-fold>

        /** @var CacheStrategy|MockObject $config */
        $config = $this->getMockBuilder(CacheStrategy::class)
                       ->setConstructorArgs([[$configFileMockA, $configFileMockB], $this->path->url() . '/cache/'])
                       ->onlyMethods(['shouldGenerate'])
                       ->getMock();

        $this->setProperty($config, 'valid', true);

        $config->method('shouldGenerate')->willReturn(false);

        $this->assertSame($cacheContent, $config->fetchCache());
    }

    /**
     * Tests {@see CacheStrategy::fetchCache()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testFetchCacheWithInvalidCall(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = 'config/example_g.yaml';
        $mockA = $this->mockFile($configFilePathA, null, (new DateTime('-4 seconds'))->getTimestamp());

        $configFilePathA = $mockA->url();
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = 'config/example_h.yaml';
        $mockB = $this->mockFile($configFilePathB, null, (new DateTime('-1 minutes'))->getTimestamp());

        $configFilePathB = $mockB->url();
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);
        //</editor-fold>

        $config = new CacheStrategy([$configFileMockA, $configFileMockB], $this->path->url() . '/cache/');

        $this->expectException(BadMethodCallException::class);
        $config->fetchCache();
    }

    public function testFetchCacheWithNoPermission(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = 'config/example_i.yaml';
        $mockA = $this->mockFile($configFilePathA, null, (new DateTime('-4 seconds'))->getTimestamp());

        $configFilePathA = $mockA->url();
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = 'config/example_j.yaml';
        $mockB = $this->mockFile($configFilePathB, null, (new DateTime('-1 minutes'))->getTimestamp());

        $configFilePathB = $mockB->url();
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);
        //</editor-fold>

        $config = new CacheStrategy([$configFileMockA, $configFileMockB], $this->path->url() . '/cache/');
        $this->setProperty($config, 'valid', true);

        /** @var string $cachePath */
        $cachePath = $this->getProperty($config, 'fullPath');

        //<editor-fold defaultstate="collapsed" desc="Mock cache file">
        $cacheKey = substr($cachePath, strrpos($cachePath, '/') + 1);

        $mockFile = $this->mockFile("cache/{$cacheKey}.cache");
        $mockFile->chmod(0000);
        //</editor-fold>

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/(Check permissions)/');
        $config->fetchCache();
    }

    /**
     * Tests {@see CacheStrategy::store()} method.
     *
     * @throws Exception
     *
     * @return void
     */
    public function testStore(): void
    {
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock A">
        $configContentA  = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];
        $configFilePathA = 'config/example_k.yaml';
        $mockA = $this->mockFile($configFilePathA);

        $configFilePathA = $mockA->url();
        $configFileMockA = $this->mockConfigFile($configFilePathA, $configContentA);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="Create ConfigFile mock B">
        $configContentB  = [
            'parameters' => [
                'bar' => 'foo'
            ],
            'services' => [

            ]
        ];
        $configFilePathB = 'config/example_l.yaml';
        $mockB = $this->mockFile($configFilePathB);

        $configFilePathB = $mockB->url();
        $configFileMockB = $this->mockConfigFile($configFilePathB, $configContentB);
        //</editor-fold>

        $newConfiguration = [
            'parameters' => [
                'param' => 'bar',
                'baz'   => 'foo'
            ],
            'services' => [],
        ];

        $loader = new CacheStrategy([$configFileMockA, $configFileMockB], $this->getCachePath(''));
        $path = $loader->store($newConfiguration);

        $this->assertFileExists("{$path}");
        $this->assertFileExists("{$path}.properties");

        $this->assertSame($newConfiguration, unserialize(file_get_contents($path)));

        $meta = (require $path . '.properties');

        $this->assertSame([$configFilePathA, $configFilePathB], $meta);
    }
}
