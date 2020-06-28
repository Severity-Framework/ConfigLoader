<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Contracts\ResolverInterface;
use Severity\ConfigLoader\ResolveManager;
use Severity\ConfigLoader\Resolver\ResolveContext;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;

/**
 * Class ResolveManagerTest
 *
 * @covers \Severity\ConfigLoader\ResolveManager
 */
class ResolveManagerTest extends ConfigLoaderTestCase
{
    use VisibilityHelper;

    protected ResolveManager $resolveManager;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->resolveManager = new ResolveManager();
    }

    /**
     * Test {@see ResolveManager::__constructor()} method.
     *
     * @return void
     */
    public function testConstructor(): void
    {
        $this->assertSame([], $this->getProperty($this->resolveManager, 'configResolvers'));
    }

    /**
     * Test {@see ResolveManager::pushResolver()} method.
     *
     * @covers \Severity\ConfigLoader\ResolveManager::pushResolver
     *
     * @return void
     */
    public function testPushResolver(): void
    {
        /** @var ResolverInterface|MockObject $resolverMock */
        $resolverMock = $this->getMockBuilder(ResolverInterface::class)
                             ->getMock();

        $this->resolveManager->pushResolver($resolverMock);

        $this->assertSame([$resolverMock], $this->getProperty($this->resolveManager, 'configResolvers'));

        /** @var ResolverInterface|MockObject $resolverMock2 */
        $resolverMock2 = $this->getMockBuilder(ResolverInterface::class)
                              ->getMock();

        $this->resolveManager->pushResolver($resolverMock2);

        $this->assertSame([$resolverMock, $resolverMock2], $this->getProperty($this->resolveManager, 'configResolvers'));
    }

    /**
     * Test {@see ResolveManager::resolve()} method.
     *
     * @covers \Severity\ConfigLoader\ResolveManager::resolve
     * @covers \Severity\ConfigLoader\ResolveManager::doResolve
     * @covers \Severity\ConfigLoader\ResolveManager::resolveValue
     * @covers \Severity\ConfigLoader\ResolveManager::resolveStringValue
     *
     * @return void
     */
    public function testResolveWithNoMatch(): void
    {
        /** @var ResolverInterface|MockObject $resolverMock */
        $resolverMock = $this->getMockBuilder(ResolverInterface::class)
                             ->getMock();

        $fnMatchResolveContext = $this->callback(function ($instance) {
            return $instance instanceof ResolveContext;
        });

        $resolverMock->expects($this->at(0))
                     ->method('translate')
                     ->with('baz', $fnMatchResolveContext)
                     ->willReturn(null);

        $resolverMock->expects($this->at(1))
                     ->method('translate')
                     ->with('foo', $fnMatchResolveContext)
                     ->willReturn(null);

        $resolverMock->expects($this->at(2))
                     ->method('translate')
                     ->with('foo-foo', $fnMatchResolveContext)
                     ->willReturn(null);

        $configMapMock = $this->createMock(ConfigMap::class);
        $configMapMock->expects($this->once())
                      ->method('get')
                      ->willReturn([
                          'parameters' => [
                              'foo' => 'baz',
                              'bar' => 'foo',
                              'param' => [
                                  'foo' => 12,
                                  'baz' => true,
                                  'bar' => 12.3,
                                  'bar-baz' => 'foo-foo'
                              ]
                          ]
                      ]);

        $this->resolveManager->pushResolver($resolverMock);
        $this->resolveManager->resolve($configMapMock);
    }

    /**
     * Test {@see ResolveManager::resolve()} method.
     *
     * @covers \Severity\ConfigLoader\ResolveManager::resolve
     * @covers \Severity\ConfigLoader\ResolveManager::doResolve
     * @covers \Severity\ConfigLoader\ResolveManager::resolveValue
     * @covers \Severity\ConfigLoader\ResolveManager::resolveStringValue
     *
     * @return void
     */
    public function testResolveWithMatch(): void
    {
        /** @var ResolverInterface|MockObject $resolverMock */
        $resolverMock = $this->getMockBuilder(ResolverInterface::class)
                             ->getMock();

        $fnMatchResolveContext = $this->callback(function ($instance) {
            return $instance instanceof ResolveContext;
        });

        $resolverMock->expects($this->at(0))
                     ->method('translate')
                     ->with('baz', $fnMatchResolveContext)
                     ->willReturn('baz-baz');

        $resolverMock->expects($this->at(1))
                     ->method('translate')
                     ->with('foo', $fnMatchResolveContext)
                     ->willReturn('foo-foo');

        $resolverMock->expects($this->at(2))
                     ->method('translate')
                     ->with('foo-foo', $fnMatchResolveContext)
                     ->willReturn('foo-baz');

        $configMapMock = $this->createMock(ConfigMap::class);
        $configMapMock->expects($this->once())
                      ->method('get')
                      ->willReturn([
                          'parameters' => [
                              'foo' => 'baz',
                              'bar' => 'foo',
                              'param' => [
                                  'foo' => 12,
                                  'baz' => true,
                                  'bar' => 12.3,
                                  'bar-baz' => 'foo-foo'
                              ]
                          ]
                      ]);

        $this->resolveManager->pushResolver($resolverMock);
        $this->resolveManager->resolve($configMapMock);
    }
}
