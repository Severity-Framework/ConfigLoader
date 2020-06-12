<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit\Resolver;

use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Resolver\ResolveContext;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;

/**
 * Class ResolveContextTest
 *
 * @covers \Severity\ConfigLoader\Resolver\ResolveContext
 */
class ResolveContextTest extends ConfigLoaderTestCase
{
    /**
     * Tests {@see ResolveContext::get()} method.
     *
     * @reutrn void
     */
    public function testGet(): void
    {
        $configMapMock = $this->createMock(ConfigMap::class);
        $configMapMock->expects($this->once())
                      ->method('getByPath')
                      ->with('parameters.foo');

        $context = new ResolveContext($configMapMock);
        $context->get('foo');
    }

    /**
     * Tests {@see ResolveContext::exists()} method.
     *
     * @reutrn void
     */
    public function testExists(): void
    {
        $configMapMock = $this->createMock(ConfigMap::class);
        $configMapMock->expects($this->once())
            ->method('exists')
            ->with('parameters.foo');

        $context = new ResolveContext($configMapMock);
        $context->exists('foo');
    }

    /**
     * Tests {@see ResolveContext::getCurrentPath()} method.
     *
     * @reutrn void
     */
    public function testGetCurrentPath(): void
    {
        $configMapMock = $this->createMock(ConfigMap::class);
        $context = new ResolveContext($configMapMock);
        $this->assertSame([], $context->getCurrentPath());

        $configMapMock = $this->createMock(ConfigMap::class);
        $context = new ResolveContext($configMapMock, ['parameters']);
        $this->assertSame(['parameters'], $context->getCurrentPath());
    }

    /**
     * Tests {@see ResolveContext::push()} method.
     *
     * @reutrn void
     */
    public function testPush(): void
    {
        $configMapMock = $this->createMock(ConfigMap::class);
        $context = new ResolveContext($configMapMock);
        $context->push('foo');
        $this->assertSame(['foo'], $context->getCurrentPath());

        $configMapMock = $this->createMock(ConfigMap::class);
        $context = new ResolveContext($configMapMock, ['parameters']);
        $context->push('foo');
        $this->assertSame(['parameters', 'foo'], $context->getCurrentPath());
    }

    /**
     * Tests {@see ResolveContext::pop()} method.
     *
     * @reutrn void
     */
    public function testPop(): void
    {
        $configMapMock = $this->createMock(ConfigMap::class);
        $context = new ResolveContext($configMapMock);
        $context->pop();
        $this->assertSame([], $context->getCurrentPath());

        $configMapMock = $this->createMock(ConfigMap::class);
        $context = new ResolveContext($configMapMock, ['parameters', 'foo']);

        $context->pop();
        $this->assertSame(['parameters'], $context->getCurrentPath());

        $context->pop();
        $this->assertSame([], $context->getCurrentPath());
    }
}
