<?php

namespace Severity\ConfigLoader\Tests\Unit\Resolver;

use Severity\ConfigLoader\Resolver\ParameterResolver;
use Severity\ConfigLoader\Resolver\ResolveContext;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\Traits\VisibilityHelper;

/**
 * Class ParameterResolverTest
 *
 * @covers \Severity\ConfigLoader\Resolver\ParameterResolver
 */
class ParameterResolverTest extends ConfigLoaderTestCase
{
    use VisibilityHelper;

    protected ParameterResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ParameterResolver();
    }

    protected function mockResolveContext(array $arguments): ResolveContext
    {
        $contextMock = $this->createMock(ResolveContext::class);
        foreach ($arguments as $key => $argument) {
            $contextMock->expects($this->at($key))
                        ->method('get')
                        ->with($argument['arg'])
                        ->willReturn($argument['return']);
        }

        return $contextMock;
    }

    /**
     * Provider for {@see testTranslateNotMatching()} method.
     *
     * @return string[][]
     */
    public function translateNotMatchingProvider(): array
    {
        return [
            ['not.matching',      'not.matching',      []],
            ['not-\%1.matching',  'not-\%1.matching',  []],
            ['not-%1.matching',   'not-%1.matching',   []],
            ['not>>matching',     'not>>matching',     []],
            ['not>>matching-%1',  'not>>matching-%1',  []],
            ['not>>matching-\%1', 'not>>matching-\%1', []],
        ];
    }

    /**
     * Tests {@see ParameterResolver::translate()} method.
     *
     * @dataProvider translateNotMatchingProvider()
     *
     * @param string $value
     * @param string $expected
     *
     * @return void
     */
    public function testTranslateNotMatching(string $value, string $expected): void
    {
        $context = $this->createMock(ResolveContext::class);

        $this->assertSame($expected, $this->resolver->translate($value, $context));
    }

    /**
     * Provider for {@see testTranslateMatchingSimple()} method.
     *
     * @return string[][]
     */
    public function translateMatchingSimpleProvider(): array
    {
        return [
            [
                'param-%param-1%', 'param-baz',
                $this->mockResolveContext([[
                    'arg'    => 'param-1',
                    'return' => 'baz'
                ]])
            ],
            [
                'param-\%-%param-1%', 'param-%-baz',
                $this->mockResolveContext([[
                    'arg'    => 'param-1',
                    'return' => 'baz'
                ]])
            ],
            [
                'param-\%-%param-\%-1%', 'param-%-baz',
                $this->mockResolveContext([[
                    'arg'    => 'param-%-1',
                    'return' => 'baz'
                ]])
            ],
            [
                'param-\%-%param-1%-%param-1%', 'param-%-baz-baz',
                $this->mockResolveContext([[
                    'arg'    => 'param-1',
                    'return' => 'baz'
                ], [
                    'arg'    => 'param-1',
                    'return' => 'baz'
                ]])
            ],
            [
                'param-\%-%param-1%-%param-2%', 'param-%-baz-bar',
                $this->mockResolveContext([[
                    'called' => 1,
                    'arg'    => 'param-1',
                    'return' => 'baz'
                ], [
                    'called' => 1,
                    'arg'    => 'param-2',
                    'return' => 'bar'
                ]])
            ],
            [
                'param-\%-%param-1%-%param-2%', 'param-%-baz-bar',
                $this->mockResolveContext([[
                    'called' => 1,
                    'arg'    => 'param-1',
                    'return' => 'baz'
                ], [
                    'called' => 1,
                    'arg'    => 'param-2',
                    'return' => 'bar'
                ]])
            ]
        ];
    }

    /**
     * Tests {@see ParameterResolver::translate()} method.
     *
     * @dataProvider translateMatchingSimpleProvider()
     *
     * @param string         $value
     * @param string         $expected
     * @param ResolveContext $context
     *
     * @return void
     */
    public function testTranslateMatchingSimple(string $value, string $expected, ResolveContext $context): void
    {
        $this->assertSame($expected, $this->resolver->translate($value, $context));
    }

    /**
     * Provider for {@see testTranslateMatchingSimple()} method.
     *
     * @return string[][]
     */
    public function translateMatchingAssocArrayProvider(): array
    {
        return [
            [
                'param-%param>>bar%', 'param-baz',
                $this->mockResolveContext([[
                    'arg'    => 'param.bar',
                    'return' => 'baz'
                ]])
            ],
            [
                'param-%param>>bar>>foo%', 'param-baz',
                $this->mockResolveContext([[
                    'arg'    => 'param.bar.foo',
                    'return' => 'baz'
                ]])
            ],
        ];
    }

    /**
     * Tests {@see ParameterResolver::translate()} method.
     *
     * @dataProvider translateMatchingAssocArrayProvider()
     *
     * @param string         $value
     * @param string         $expected
     * @param ResolveContext $context
     *
     * @return void
     */
    public function testTranslateMatchingAssocArraySimple(string $value, string $expected, ResolveContext $context): void
    {
        $this->assertSame($expected, $this->resolver->translate($value, $context));
    }
}
