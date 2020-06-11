<?php

namespace Severity\ConfigLoader\Tests\Unit\Resolver;

use ReflectionException;
use Severity\ConfigLoader\Resolver\ParameterResolver;
use Severity\ConfigLoader\Resolver\ResolveContext;
use Severity\ConfigLoader\Tests\Unit\ConfigLoaderTestCase;
use Severity\ConfigLoader\Tests\Utility\VisibilityHelper;

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

    /**
     * Provider for {@see testTranslateNotMatching()} method.
     *
     * @return string[][]
     */
    public function translateNotMatchingProvider(): array
    {
        return [
            ['not.matching', 'not.matching'],
            ['not-\%1.matching', 'not-\%1.matching'],
            ['not-%1.matching', 'not-%1.matching'],
            ['not[matching]', 'not[matching]'],
            ['not[matching-%1]', 'not[matching-%1]'],
            ['not[matching-\%1]', 'not[matching-\%1]']
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
}
