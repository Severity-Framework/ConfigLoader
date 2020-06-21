<?php

namespace Severity\ConfigLoader\Tests\Unit;

use Severity\ConfigLoader\Builder\ConfigFile;
use Severity\ConfigLoader\Loader;
use PHPUnit\Framework\TestCase;
use Severity\ConfigLoader\ResolveManager;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;
use function array_shift;

/**
 * Class LoaderTest
 *
 * @covers \Severity\ConfigLoader\Loader
 */
class LoaderTest extends ConfigLoaderTestCase
{
    use VisibilityHelper;

    protected ResolveManager $resolveManager;

    protected function setUp(): void
    {
        $this->resolveManager = $this->createMock(ResolveManager::class);
    }

    public function testLoadConfig(): void
    {
        $loader = new Loader($this->resolveManager, '');
        $configFileMock = $this->createMock(ConfigFile::class);

        $loader->loadConfig($configFileMock);

        $configFiles = $this->getProperty($loader, 'configFiles');

        $this->assertCount(1, $configFiles);

        /** @var ConfigFile $configFile */
        $configFile = array_shift($configFiles);

        $this->assertSame($configFile, $configFileMock);
    }
}
