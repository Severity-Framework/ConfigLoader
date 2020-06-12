<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use ReflectionException;
use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Contracts\ResolverInterface;
use Severity\ConfigLoader\ResolveManager;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;
use function ociresult;

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
     * @throws ReflectionException
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
     * @throws ReflectionException
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

    public function testResolve(): void
    {
        $resolverMock = $this->getMockBuilder(ResolverInterface::class)
                             ->getMock();

        $resolverMock->expects($this->at(1))
                     ->method('translate')
                     ->with('baz')
                     ->willReturn('baz');

        $resolverMock->expects($this->at(2))
                     ->method('translate')
                     ->with('foo')
                     ->willReturn('foo');

        $resolverMock->expects($this->at(3))
                     ->method('translate')
                     ->with('foo-foo')
                     ->willReturn('foo-foo');

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