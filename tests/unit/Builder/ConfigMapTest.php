<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Tests\Unit\Builder;

use Severity\ConfigLoader\Builder\ConfigMap;
use Severity\ConfigLoader\Exceptions\ConfigMergeException;
use Severity\ConfigLoader\Exceptions\InvalidPathSegmentException;
use Severity\ConfigLoader\Exceptions\NotExistingPathSegmentException;
use Severity\ConfigLoader\Tests\Utility\Contracts\ConfigLoaderTestCase;

/**
 * Class ConfigMapTest
 *
 * @covers \Severity\ConfigLoader\Builder\ConfigMap
 * @covers \Severity\ConfigLoader\Resolver\ResolveContext
 */
class ConfigMapTest extends ConfigLoaderTestCase
{
    /**
     * Test {@see ConfigMap::merge()) with 1 array.
     *
     * @throws ConfigMergeException
     *
     * @return void
     */
    public function testMergeToEmpty(): void
    {
        $map = new ConfigMap();
        $configMock = $this->mockConfigFileWithFetch($dataToMerge = [
            'parameters' => [
                'foo' => 'baz'
            ],
            'additional-parameters' => [
                'bar' => 'baz'
            ]
        ]);

        $map->merge($configMock);

        $this->assertSame($dataToMerge, $map->get());
    }

    /**
     * Test {@see ConfigMap::merge()) method with associative arrays.
     *
     * @throws ConfigMergeException
     *
     * @return void
     */
    public function testMergeWithAssociativeKeys(): void
    {
        $map = new ConfigMap();

        //<editor-fold defaultstate="collapsed" desc="MockObject A">
        $fileAMock = $this->mockConfigFileWithFetch([
            'parameters' => [
                'foo' => 'baz'
            ],
            'additional-parameters' => [
                'bar' => 'baz'
            ]
        ]);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="MockObject B">
        $fileBMock = $this->mockConfigFileWithFetch([
            'parameters' => [
                'foo' => 'baz2',
                'foo2' => 'bar'
            ],
            'additional-parameters' => [
                'bar2' => 'baz2'
            ]
        ]);
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Expected result">
        $expected = [
            'parameters' => [
                'foo' => 'baz2',
                'foo2' => 'bar'
            ],
            'additional-parameters' => [
                'bar' => 'baz',
                'bar2' => 'baz2'
            ]
        ];
        //</editor-fold>

        $map->merge($fileAMock);
        $map->merge($fileBMock);

        $this->assertSame($expected, $map->get());
    }

    /**
     * Test {@see ConfigMap::merge()) method with numeric arrays with scalar values.
     *
     * @throws ConfigMergeException
     *
     * @return void
     */
    public function testMergeWithNumericKeysScalarValues(): void
    {
        $map = new ConfigMap();

        //<editor-fold defaultstate="collapsed" desc="MockObject A">
        $fileAMock = $this->mockConfigFileWithFetch([
            'parameters-filled-both' => [
                'foo' => [
                    0 => 'Foo',
                    1 => 'Bar'
                ]
            ],
            'parameters-empty-both' => [
                'foo' => []
            ],
            'additional-empty-left' => [
                'bar' => []
            ],
            'additional-empty-right' => [
                'foo' => [
                    0 => 'Bar'
                ]
            ],
            'additional-mixed' => [
                'bar' => [
                    0 => 'Foo'
                ],
                'foo' => [],
                'baz' => [
                    0 => 'Bar'
                ]
            ]
        ]);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="MockObject B">
        $fileBMock = $this->mockConfigFileWithFetch([
            'parameters-filled-both' => [
                'foo' => [
                    0 => 'Foo2'
                ]
            ],
            'parameters-empty-both' => [
                'foo' => []
            ],
            'additional-empty-left' => [
                'bar' => [
                    0 => 'Bar'
                ]
            ],
            'additional-empty-right' => [
                'foo' => []
            ],
            'additional-mixed' => [
                'bar' => [
                    0 => 'Baz'
                ],
                'foo' => [
                    0 => 'Bar'
                ],
                'baz' => []
            ]
        ]);
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Expected result">
        $expected = [
            'parameters-filled-both' => [
                'foo' => [
                    0 => 'Foo',
                    1 => 'Bar',
                    2 => 'Foo2'
                ]
            ],
            'parameters-empty-both' => [
                'foo' => []
            ],
            'additional-empty-left' => [
                'bar' => [
                    0 => 'Bar'
                ]
            ],
            'additional-empty-right' => [
                'foo' => [
                    0 => 'Bar'
                ]
            ],
            'additional-mixed' => [
                'bar' => [
                    0 => 'Foo',
                    1 => 'Baz'
                ],
                'foo' => [
                    0 => 'Bar'
                ],
                'baz' => [
                    0 => 'Bar'
                ]
            ]
        ];
        //</editor-fold>

        $map->merge($fileAMock);
        $map->merge($fileBMock);

        $this->assertSame($expected, $map->get());
    }

    /**
     * Test {@see ConfigMap::merge()) method with numeric arrays with scalar values.
     *
     * @throws ConfigMergeException
     *
     * @return void
     */
    public function testMergeWithNumericKeysMixedValues(): void
    {
        $map = new ConfigMap();

        //<editor-fold defaultstate="collapsed" desc="MockObject A">
        $fileAMock = $this->mockConfigFileWithFetch([
            'parameters-filled-both' => [
                'foo' => [
                    0 => 'Foo',
                    1 => 'Bar'
                ]
            ],
            'additional-empty-left' => [
                'bar' => []
            ],
            'additional-empty-right' => [
                'foo' => [
                    0 => [
                        'baz' => 'foo'
                    ]
                ]
            ],
            'additional-mixed' => [
                'bar' => [
                    0 => 'Foo'
                ],
                'foo' => [],
                'baz' => [
                    0 => 'Bar',
                    1 => [
                        0 => 'Bar',
                        1 => 'Foo'
                    ]
                ]
            ]
        ]);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="MockObject B">
        $fileBMock = $this->mockConfigFileWithFetch([
            'parameters-filled-both' => [
                'foo' => [
                    0 => [
                        'foo' => 'baz'
                    ]
                ]
            ],
            'additional-empty-left' => [
                'bar' => [
                    0 => [
                        'bar' => 'baz'
                    ]
                ]
            ],
            'additional-empty-right' => [
                'foo' => []
            ],
            'additional-mixed' => [
                'bar' => [
                    0 => 'Baz',
                    1 => [
                        'foo' => 'Bar'
                    ]
                ],
                'foo' => [
                    0 => 'Bar',
                    1 => [
                        'Bar' => 'Foo'
                    ],
                    2 => []
                ],
                'baz' => [
                    0 => 'Foo',
                    1 => [
                        0 => 'Bar',
                        1 => 'Baz'
                    ]
                ]
            ]
        ]);
        //</editor-fold>

        //<editor-fold defaultstate="collapsed" desc="Expected result">
        $expected = [
            'parameters-filled-both' => [
                'foo' => [
                    0 => 'Foo',
                    1 => 'Bar',
                    2 => [
                        'foo' => 'baz'
                    ]
                ]
            ],
            'additional-empty-left' => [
                'bar' => [
                    0 => [
                        'bar' => 'baz'
                    ]
                ]
            ],
            'additional-empty-right' => [
                'foo' => [
                    0 => [
                        'baz' => 'foo'
                    ]
                ]
            ],
            'additional-mixed' => [
                'bar' => [
                    0 => 'Foo',
                    1 => 'Baz',
                    2 => [
                        'foo' => 'Bar'
                    ]
                ],
                'foo' => [
                    0 => 'Bar',
                    1 => [
                        'Bar' => 'Foo'
                    ],
                    2 => []
                ],
                'baz' => [
                    0 => 'Bar',
                    1 => [
                        0 => 'Bar',
                        1 => 'Foo'
                    ],
                    2 => 'Foo',
                    3 => [
                        0 => 'Bar',
                        1 => 'Baz'
                    ]
                ]
            ]
        ];
        //</editor-fold>

        $map->merge($fileAMock);
        $map->merge($fileBMock);

        $this->assertSame($expected, $map->get());
    }

    /**
     * Test {@see ConfigMap::merge()) method with numeric arrays with scalar values.
     *
     * @throws ConfigMergeException
     *
     * @return void
     */
    public function testMergeWithErrorOnExistingArrayKey(): void
    {
        $map = new ConfigMap();

        //<editor-fold defaultstate="collapsed" desc="MockObject A">
        $fileAMock = $this->mockConfigFileWithFetch([
            'parameters' => [
                'foo' => [
                    'baz' => 'bar'
                ]
            ]
        ]);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="MockObject B">
        $fileBMock = $this->mockConfigFileWithFetch([
            'parameters' => [
                'foo' => 'baz'
            ]
        ]);
        //</editor-fold>

        $this->expectException(ConfigMergeException::class);

        $map->merge($fileAMock);
        $map->merge($fileBMock);
    }

    /**
     * Test {@see ConfigMap::merge()) method with numeric arrays with scalar values.
     *
     * @throws ConfigMergeException
     *
     * @return void
     */
    public function testMergeWithErrorOnExistingNonArrayKey(): void
    {
        $map = new ConfigMap();

        //<editor-fold defaultstate="collapsed" desc="MockObject A">
        $fileAMock = $this->mockConfigFileWithFetch([
            'parameters' => [
                'foo' => 'baz'
            ]
        ]);
        //</editor-fold>
        //<editor-fold defaultstate="collapsed" desc="MockObject B">
        $fileBMock = $this->mockConfigFileWithFetch([
            'parameters' => [
                'foo' => [
                    'baz' => 'bar'
                ]
            ]
        ]);
        //</editor-fold>

        $this->expectException(ConfigMergeException::class);

        $map->merge($fileAMock);
        $map->merge($fileBMock);
    }

    /**
     * Test {@see ConfigMap::set()) method.
     *
     * @return void
     */
    public function testSet(): void
    {
        $map = new ConfigMap();

        $dataToSet = [
            'parameters' => [
                'foo' => 'baz'
            ]
        ];

        $map->set($dataToSet);

        $this->assertSame($dataToSet, $map->get());
    }

    /**
     * Provider for {@see testGetByPath()} method.
     *
     * @return array[]
     */
    public function getByPathProvider(): array
    {
        $exampleData = [
            'parameters' => [
                'foo' => [
                    'bar' => 'baz'
                ],
                'bar' => 'baz-baz'
            ],
            'additional-param' => [
                'baz' => 'foo'
            ]
        ];

        return [
            [$exampleData, 'parameters.foo.bar', 'baz'],
            [$exampleData, 'parameters.bar', 'baz-baz'],
            [$exampleData, 'additional-param.baz', 'foo'],

            [$exampleData, 'parameters', [
                'foo' => [
                    'bar' => 'baz'
                ],
                'bar' => 'baz-baz'
            ]],

            [$exampleData, 'parameters.foo', [
                'bar' => 'baz'
            ]],

            [$exampleData, 'additional-param', [
                'baz' => 'foo'
            ]],
        ];
    }

    /**
     * Test {@see ConfigMap::getByPath()) method.
     *
     * @dataProvider getByPathProvider
     *
     * @param array  $source
     * @param string $path
     * @param mixed  $expected
     *
     * @throws InvalidPathSegmentException
     * @throws NotExistingPathSegmentException
     *
     * @return void
     */
    public function testGetByPath(array $source, string $path, $expected): void
    {
        $map = new ConfigMap($source);

        $this->assertSame($expected, $map->getByPath($path));
    }

    /**
     * Test {@see ConfigMap::getByPath()) method.
     *
     * @throws InvalidPathSegmentException
     * @throws NotExistingPathSegmentException
     *
     * @return void
     */
    public function testGetByPathWithPathSegmentError(): void
    {
        $map = new ConfigMap([
            'parameters' => [
                'foo' => 'baz'
            ]
        ]);

        $this->expectException(InvalidPathSegmentException::class);

        $map->getByPath('parameters.foo.baz');
    }

    /**
     * Test {@see ConfigMap::getByPath()) method.
     *
     * @throws InvalidPathSegmentException
     * @throws NotExistingPathSegmentException
     *
     * @return void
     */
    public function testGetByPathWithNonExistingPathSegmentError(): void
    {
        $map = new ConfigMap([
            'parameters' => [
                'foo' => 'baz'
            ]
        ]);

        $this->expectException(NotExistingPathSegmentException::class);

        $map->getByPath('parameters.baz');
    }



    /**
     * Provider for {@see testGetByPath()} method.
     *
     * @return array[]
     */
    public function existsProvider(): array
    {
        $exampleData = [
            'parameters' => [
                'foo' => [
                    'bar' => 'baz'
                ],
                'bar' => 'baz-baz'
            ],
            'additional-param' => [
                'baz' => 'foo'
            ]
        ];

        return [
            [$exampleData, 'parameters', true],
            [$exampleData, 'parameters.bar', true],
            [$exampleData, 'parameters.foo', true],
            [$exampleData, 'parameters.foo.bar', true],
            [$exampleData, 'additional-param', true],
            [$exampleData, 'additional-param.baz', true],

            [$exampleData, 'parameters.baz', false],
            [$exampleData, 'parameters.foo.baz', false],
            [$exampleData, 'parameters.foo.bar.baz', false],
            [$exampleData, 'parameters.bar.baz-baz', false],

            [$exampleData, 'additional-param.foo', false],
            [$exampleData, 'additional-param.baz.foo', false],
        ];
    }

    /**
     * Test {@see ConfigMap::exists()) method.
     *
     * @dataProvider existsProvider
     *
     * @param array  $source
     * @param string $path
     * @param bool   $expected
     *
     * @return void
     */
    public function testExists(array $source, string $path, bool $expected): void
    {
        $map = new ConfigMap($source);

        $this->assertSame($expected, $map->exists($path));
    }
}
