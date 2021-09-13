<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Tests\Unit\Mvc\View;

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for the JSON view
 */
class JsonViewTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @var JsonView
     */
    protected $view;

    /**
     * Sets up this test case
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->view = $this->getMockBuilder(JsonView::class)
            ->addMethods(['loadConfigurationFromYamlFile'])
            ->getMock();
    }

    /**
     * data provider for testTransformValue()
     * @return array
     */
    public function jsonViewTestData(): array
    {
        $output = [];

        $object = new \stdClass();
        $object->value1 = 'foo';
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value1' => 'foo', 'value2' => 1];
        $output[] = [$object, $configuration, $expected, 'all direct child properties should be serialized'];

        $configuration = ['_only' => ['value1']];
        $expected = ['value1' => 'foo'];
        $output[] = [$object, $configuration, $expected, 'if "only" properties are specified, only these should be serialized'];

        $configuration = ['_exclude' => ['value1']];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'if "exclude" properties are specified, they should not be serialized'];

        $object = new \stdClass();
        $object->value1 = new \stdClass();
        $object->value1->subvalue1 = 'Foo';
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'by default, sub objects of objects should not be serialized.'];

        $object = new \stdClass();
        $object->value1 = ['subarray' => 'value'];
        $object->value2 = 1;
        $configuration = [];
        $expected = ['value2' => 1];
        $output[] = [$object, $configuration, $expected, 'by default, sub arrays of objects should not be serialized.'];

        $object = ['foo' => 'bar', 1 => 'baz', 'deep' => ['test' => 'value']];
        $configuration = [];
        $expected = ['foo' => 'bar', 1 => 'baz', 'deep' => ['test' => 'value']];
        $output[] = [$object, $configuration, $expected, 'associative arrays should be serialized deeply'];

        $object = ['foo', 'bar'];
        $configuration = [];
        $expected = ['foo', 'bar'];
        $output[] = [$object, $configuration, $expected, 'numeric arrays should be serialized'];

        $nestedObject = new \stdClass();
        $nestedObject->value1 = 'foo';
        $object = [$nestedObject];
        $configuration = [];
        $expected = [['value1' => 'foo']];
        $output[] = [$object, $configuration, $expected, 'array of objects should be serialized'];

        $properties = ['foo' => 'bar', 'prohibited' => 'xxx'];
        $nestedObject = new class($properties) {
            private $properties;
            private $prohibited;
            public function __construct($properties)
            {
                $this->properties = $properties;
            }
            public function getName(): string
            {
                return 'name';
            }

            public function getPath(): string
            {
                return 'path';
            }

            public function getProperties()
            {
                return $this->properties;
            }
        };
        $object = $nestedObject;
        $configuration = [
            '_only' => ['name', 'path', 'properties'],
            '_descend' => [
                 'properties' => [
                      '_exclude' => ['prohibited'],
                 ],
            ],
        ];
        $expected = [
            'name' => 'name',
            'path' => 'path',
            'properties' => ['foo' => 'bar'],
        ];
        $output[] = [$object, $configuration, $expected, 'descending into arrays should be possible'];

        $nestedObject = new \stdClass();
        $nestedObject->value1 = 'foo';
        $value = new \SplObjectStorage();
        $value->attach($nestedObject);
        $configuration = [];
        $expected = [['value1' => 'foo']];
        $output[] = [$value, $configuration, $expected, 'SplObjectStorage with objects should be serialized'];

        $dateTimeObject = new \DateTime('2011-02-03T03:15:23', new \DateTimeZone('UTC'));
        $configuration = [];
        $expected = '2011-02-03T03:15:23+00:00';
        $output[] = [$dateTimeObject, $configuration, $expected, 'DateTime object in UTC time zone should not be serialized.'];

        $dateTimeObject = new \DateTime('2013-08-15T15:25:30', new \DateTimeZone('America/Los_Angeles'));
        $configuration = [];
        $expected = '2013-08-15T15:25:30-07:00';
        $output[] = [$dateTimeObject, $configuration, $expected, 'DateTime object in America/Los_Angeles time zone should not be serialized.'];

        return $output;
    }

    /**
     * @test
     * @param object|array $object
     * @param array $configuration
     * @param array|string $expected
     * @param string $description
     * @dataProvider jsonViewTestData
     */
    public function transformValue($object, array $configuration, $expected, string $description): void
    {
        $jsonView = $this->getAccessibleMock(JsonView::class, ['dummy'], [], '', false);

        $actual = $jsonView->_call('transformValue', $object, $configuration);

        self::assertSame($expected, $actual, $description);
    }
    /**
     * data provider for testRecursive()
     * @return array
     */
    public function jsonViewTestDataRecursive(): array
    {
        $object = new class('foo') {
            private $value1 = '';
            private $child;
            public function __construct($value1)
            {
                $this->value1 = $value1;
            }
            public function getValue1(): string
            {
                return $this->value1;
            }
            public function setValue1(string $value1): void
            {
                $this->value1 = $value1;
            }
            public function getChild()
            {
                return $this->child;
            }
            public function setChild($child): void
            {
                $this->child = $child;
            }
        };

        $child1 = clone $object;
        $child1->setValue1('bar');
        $child2 = clone $object;
        $child2->setValue1('baz');
        $child1->setChild($child2);
        $object->setChild($child1);

        $configuration = [
            'testData' => [
                '_recursive' => ['child']
            ]
        ];

        $expected = [
            'child' => [
                'child' => [
                    'child' => null,
                    'value1' => 'baz'
                ],
                'value1' => 'bar',
            ],
            'value1' => 'foo',
        ];

        $output[] = [$object, $configuration, $expected, 'testData', 'Recursive rendering of defined property should be possible.'];

        $object = new class('foo') {
            private $value1 = '';
            private $children = [];
            private $secret = 'secret';
            public function __construct($value1)
            {
                $this->value1 = $value1;
            }
            public function getValue1(): string
            {
                return $this->value1;
            }
            public function setValue1(string $value1): void
            {
                $this->value1 = $value1;
            }
            public function getChildren(): array
            {
                return $this->children;
            }
            public function addChild($child): void
            {
                $this->children[] = $child;
            }
            public function getSecret(): string
            {
                return $this->secret;
            }
        };
        $child1 = clone $object;
        $child1->setValue1('bar');
        $child1->addChild(clone $object);
        $child1->addChild(clone $object);

        $child2 = clone $object;
        $child2->setValue1('baz');
        $child2->addChild(clone $object);
        $child2->addChild(clone $object);

        $object->addChild($child1);
        $object->addChild($child2);
        $children = [
            clone $object,
            clone $object
        ];

        $configuration = [
            'testData' => [
                '_descendAll' => [
                    '_exclude' => ['secret'],
                    '_recursive' => ['children']
                ],
            ]
        ];

        $expected = [
            [
                'children' => [
                    [
                        'children' => [
                            ['children' => [], 'value1' => 'foo'],
                            ['children' => [], 'value1' => 'foo']
                        ],
                        'value1' => 'bar'
                    ],
                    [
                        'children' => [
                            ['children' => [], 'value1' => 'foo'],
                            ['children' => [], 'value1' => 'foo']
                        ],
                        'value1' => 'baz'
                    ]
                ],
                'value1' => 'foo'
            ],
            [
                'children' => [
                    [
                        'children' => [
                            ['children' => [], 'value1' => 'foo'],
                            ['children' => [], 'value1' => 'foo']
                        ],
                        'value1' => 'bar'
                    ],
                    [
                        'children' => [
                            ['children' => [], 'value1' => 'foo'],
                            ['children' => [], 'value1' => 'foo']
                        ],
                        'value1' => 'baz'
                    ]
                ],
                'value1' => 'foo'
            ]
        ];
        $output[] = [$children, $configuration, $expected, 'testData', 'Recursive rendering of lists of defined property should be possible.'];

        return $output;
    }

    /**
     * @test
     * @param object|array $object
     * @param array $configuration
     * @param array|string $expected
     * @param string $variableToRender
     * @param string $description
     * @dataProvider jsonViewTestDataRecursive
     */
    public function recursive($object, array $configuration, $expected, string $variableToRender, string $description): void
    {
        $jsonView = $this->getAccessibleMock(JsonView::class, ['dummy'], [], '', false);
        $jsonView->_set('configuration', $configuration);
        $jsonView->_set('variablesToRender', [$variableToRender]);
        $jsonView->_call('assign', $variableToRender, $object);
        $actual = $jsonView->_call('renderArray');

        self::assertSame($expected, $actual, $description);
    }

    /**
     * data provider for testTransformValueWithObjectIdentifierExposure()
     * @return array
     */
    public function objectIdentifierExposureTestData(): array
    {
        $output = [];

        $dummyIdentifier = 'e4f40dfc-8c6e-4414-a5b1-6fd3c5cf7a53';

        $object = new \stdClass();
        $object->value1 = new \stdClass();
        $configuration = [
            '_descend' => [
                 'value1' => [
                      '_exposeObjectIdentifier' => true,
                 ],
            ],
        ];

        $expected = ['value1' => ['__identity' => $dummyIdentifier]];
        $output[] = [$object, $configuration, $expected, $dummyIdentifier, 'boolean TRUE should result in __identity key'];

        $configuration['_descend']['value1']['_exposedObjectIdentifierKey'] = 'guid';
        $expected = ['value1' => ['guid' => $dummyIdentifier]];
        $output[] = [$object, $configuration, $expected, $dummyIdentifier, 'string value should result in string-equal key'];

        return $output;
    }

    /**
     * @test
     * @param object $object
     * @param array $configuration
     * @param array $expected
     * @param string $dummyIdentifier
     * @param string $description
     * @dataProvider objectIdentifierExposureTestData
     */
    public function transformValueWithObjectIdentifierExposure(
        object $object,
        array $configuration,
        array $expected,
        string $dummyIdentifier,
        string $description
    ): void {
        $persistenceManagerMock = $this->getMockBuilder(PersistenceManager::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getIdentifierByObject'])
            ->getMock();
        $jsonView = $this->getAccessibleMock(JsonView::class, ['dummy'], [], '', false);
        $jsonView->_set('persistenceManager', $persistenceManagerMock);

        $persistenceManagerMock->expects(self::once())->method('getIdentifierByObject')->with($object->value1)->willReturn($dummyIdentifier);

        $actual = $jsonView->_call('transformValue', $object, $configuration);

        self::assertSame($expected, $actual, $description);
    }

    /**
     * A data provider
     */
    public function exposeClassNameSettingsAndResults(): array
    {
        $className = StringUtility::getUniqueId('DummyClass');
        $namespace = 'TYPO3\CMS\Extbase\Tests\Unit\Mvc\View\\' . $className;
        return [
            [
                JsonView::EXPOSE_CLASSNAME_FULLY_QUALIFIED,
                $className,
                $namespace,
                ['value1' => ['__class' => $namespace . '\\' . $className]],
            ],
            [
                JsonView::EXPOSE_CLASSNAME_UNQUALIFIED,
                $className,
                $namespace,
                ['value1' => ['__class' => $className]],
            ],
            [
                null,
                $className,
                $namespace,
                ['value1' => []],
            ]
        ];
    }

    /**
     * @test
     * @param int|null $exposeClassNameSetting
     * @param string $className
     * @param string $namespace
     * @param array $expected
     * @dataProvider exposeClassNameSettingsAndResults
     */
    public function viewExposesClassNameFullyIfConfiguredSo(
        ?int $exposeClassNameSetting,
        string $className,
        string $namespace,
        array $expected
    ): void {
        $fullyQualifiedClassName = $namespace . '\\' . $className;
        if (class_exists($fullyQualifiedClassName) === false) {
            eval('namespace ' . $namespace . '; class ' . $className . ' {}');
        }

        $object = new \stdClass();
        $object->value1 = new $fullyQualifiedClassName();
        $configuration = [
            '_descend' => [
                'value1' => [
                    '_exposeClassName' => $exposeClassNameSetting,
                ],
            ],
        ];

        $jsonView = $this->getAccessibleMock(JsonView::class, ['dummy'], [], '', false);
        $actual = $jsonView->_call('transformValue', $object, $configuration);
        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function renderReturnsJsonRepresentationOfAssignedObject(): void
    {
        $object = new \stdClass();
        $object->foo = 'Foo';
        $this->view->assign('value', $object);

        $expectedResult = '{"foo":"Foo"}';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsJsonRepresentationOfAssignedArray(): void
    {
        $array = ['foo' => 'Foo', 'bar' => 'Bar'];
        $this->view->assign('value', $array);

        $expectedResult = '{"foo":"Foo","bar":"Bar"}';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsJsonRepresentationOfAssignedSimpleValue(): void
    {
        $value = 'Foo';
        $this->view->assign('value', $value);

        $expectedResult = '"Foo"';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderKeepsUtf8CharactersUnescaped(): void
    {
        $value = 'Gürkchen';
        $this->view->assign('value', $value);

        $actualResult = $this->view->render();

        $expectedResult = '"' . $value . '"';
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @return string[][]
     */
    public function escapeCharacterDataProvider(): array
    {
        return [
            'backslash' => ['\\'],
            'double quote' => ['"'],
        ];
    }

    /**
     * @test
     * @param string $character
     * @dataProvider escapeCharacterDataProvider
     */
    public function renderEscapesEscapeCharacters(string $character): void
    {
        $this->view->assign('value', $character);

        $actualResult = $this->view->render();

        $expectedResult = '"\\' . $character . '"';
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderReturnsNullIfNameOfAssignedVariableIsNotEqualToValue(): void
    {
        $value = 'Foo';
        $this->view->assign('foo', $value);

        $expectedResult = 'null';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderOnlyRendersVariableWithTheNameValue(): void
    {
        $this->view
            ->assign('value', 'Value')
            ->assign('someOtherVariable', 'Foo');

        $expectedResult = '"Value"';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function setVariablesToRenderOverridesValueToRender(): void
    {
        $value = 'Foo';
        $this->view->assign('foo', $value);
        $this->view->setVariablesToRender(['foo']);

        $expectedResult = '"Foo"';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderRendersMultipleValuesIfTheyAreSpecifiedAsVariablesToRender(): void
    {
        $this->view
            ->assign('value', 'Value1')
            ->assign('secondValue', 'Value2')
            ->assign('someOtherVariable', 'Value3');
        $this->view->setVariablesToRender(['value', 'secondValue']);

        $expectedResult = '{"value":"Value1","secondValue":"Value2"}';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCanRenderMultipleComplexObjects(): void
    {
        $array = ['foo' => ['bar' => 'Baz']];
        $object = new \stdClass();
        $object->foo = 'Foo';

        $this->view
            ->assign('array', $array)
            ->assign('object', $object)
            ->assign('someOtherVariable', 'Value3');
        $this->view->setVariablesToRender(['array', 'object']);

        $expectedResult = '{"array":{"foo":{"bar":"Baz"}},"object":{"foo":"Foo"}}';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCanRenderPlainArray(): void
    {
        $array = [['name' => 'Foo', 'secret' => true], ['name' => 'Bar', 'secret' => true]];

        $this->view->assign('value', $array);
        $this->view->setConfiguration([
            'value' => [
                '_descendAll' => [
                    '_only' => ['name'],
                ],
            ],
        ]);

        $expectedResult = '[{"name":"Foo"},{"name":"Bar"}]';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function renderCanRenderPlainArrayWithNumericKeys(): void
    {
        $array = [
            'items' => [
                ['name' => 'Foo'],
                ['name' => 'Bar']
            ],
        ];

        $this->view->assign('value', $array);
        $this->view->setConfiguration([
            'value' => [
                'items' => [
                    // note: this exclude is just here, and should have no effect as the items have numeric keys
                    '_exclude' => ['secret']
                ]
            ],
        ]);

        $expectedResult = '{"items":[{"name":"Foo"},{"name":"Bar"}]}';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }

    /**
     * @test
     */
    public function descendAllKeepsArrayIndexes(): void
    {
        $array = [['name' => 'Foo', 'secret' => true], ['name' => 'Bar', 'secret' => true]];

        $this->view->assign('value', $array);
        $this->view->setConfiguration([
            'value' => [
                '_descendAll' => [
                    '_descendAll' => [],
                ],
            ],
        ]);

        $expectedResult = '[{"name":"Foo","secret":true},{"name":"Bar","secret":true}]';
        $actualResult = $this->view->render();
        self::assertSame($expectedResult, $actualResult);
    }
}
