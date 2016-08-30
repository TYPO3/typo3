<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property;

/*                                                                        *
 * This script belongs to the Extbase framework.                          *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Extbase\Tests\Unit\Property\Fixtures\DataProviderOneInterface;
use TYPO3\CMS\Extbase\Tests\Unit\Property\Fixtures\DataProviderThree;
use TYPO3\CMS\Extbase\Tests\Unit\Property\Fixtures\DataProviderThreeInterface;
use TYPO3\CMS\Extbase\Tests\Unit\Property\Fixtures\DataProviderTwo;
use TYPO3\CMS\Extbase\Tests\Unit\Property\Fixtures\DataProviderTwoInterface;

/**
 * Test case
 */
class PropertyMapperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected $mockConfigurationBuilder;

    protected $mockConfiguration;

    /**
     * Sets up this test case
     *
     * @return void
     */
    protected function setUp()
    {
        $this->mockConfigurationBuilder = $this->getMock(\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationBuilder::class);
        $this->mockConfiguration = $this->getMock(\TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface::class);
    }

    /**
     * @return array
     */
    public function validSourceTypes()
    {
        return [
            ['someString', 'string'],
            [42, 'integer'],
            [3.5, 'float'],
            [true, 'boolean'],
            [[], 'array']
        ];
    }

    /**
     * @test
     * @dataProvider validSourceTypes
     * @param mixed $source
     * @param mixed $sourceType
     */
    public function sourceTypeCanBeCorrectlyDetermined($source, $sourceType)
    {
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $this->assertEquals($sourceType, $propertyMapper->_call('determineSourceType', $source));
    }

    /**
     * @return array
     */
    public function invalidSourceTypes()
    {
        return [
            [null],
            [new \stdClass()],
            [new \ArrayObject()]
        ];
    }

    /**
     * @test
     * @dataProvider invalidSourceTypes
     * @expectedException \TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException
     * @param mixed $source
     */
    public function sourceWhichIsNoSimpleTypeThrowsException($source)
    {
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $propertyMapper->_call('determineSourceType', $source);
    }

    /**
     * @param string $name
     * @param bool $canConvertFrom
     * @param array $properties
     * @param string $typeOfSubObject
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockTypeConverter($name = '', $canConvertFrom = true, $properties = [], $typeOfSubObject = '')
    {
        $mockTypeConverter = $this->getMock(\TYPO3\CMS\Extbase\Property\TypeConverterInterface::class);
        $mockTypeConverter->_name = $name;
        $mockTypeConverter->expects($this->any())->method('canConvertFrom')->will($this->returnValue($canConvertFrom));
        $mockTypeConverter->expects($this->any())->method('convertFrom')->will($this->returnValue($name));
        $mockTypeConverter->expects($this->any())->method('getSourceChildPropertiesToBeConverted')->will($this->returnValue($properties));
        $mockTypeConverter->expects($this->any())->method('getTypeOfChildProperty')->will($this->returnValue($typeOfSubObject));
        return $mockTypeConverter;
    }

    /**
     * @test
     */
    public function findTypeConverterShouldReturnTypeConverterFromConfigurationIfItIsSet()
    {
        $mockTypeConverter = $this->getMockTypeConverter();
        $this->mockConfiguration->expects($this->any())->method('getTypeConverter')->will($this->returnValue($mockTypeConverter));
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $this->assertSame($mockTypeConverter, $propertyMapper->_call('findTypeConverter', 'someSource', 'someTargetType', $this->mockConfiguration));
    }

    /**
     * Simple type conversion
     * @return array
     */
    public function dataProviderForFindTypeConverter()
    {
        return [
            ['someStringSource', 'string', [
                'string' => [
                    'string' => [
                        10 => $this->getMockTypeConverter('string2string,prio10'),
                        1 => $this->getMockTypeConverter('string2string,prio1'),
                    ]
                ]], 'string2string,prio10'
            ],
            [['some' => 'array'], 'string', [
                'array' => [
                    'string' => [
                        10 => $this->getMockTypeConverter('array2string,prio10'),
                        1 => $this->getMockTypeConverter('array2string,prio1'),
                    ]
                ]], 'array2string,prio10'
            ],
            ['someStringSource', 'bool', [
                'string' => [
                    'boolean' => [
                        10 => $this->getMockTypeConverter('string2boolean,prio10'),
                        1 => $this->getMockTypeConverter('string2boolean,prio1'),
                    ]
                ]], 'string2boolean,prio10'
            ],
            ['someStringSource', 'int', [
                'string' => [
                    'integer' => [
                        10 => $this->getMockTypeConverter('string2integer,prio10'),
                        1 => $this->getMockTypeConverter('string2integer,prio1'),
                    ],
                ]], 'string2integer,prio10'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider dataProviderForFindTypeConverter
     * @param mixed $source
     * @param mixed $targetType
     * @param mixed $typeConverters
     * @param mixed $expectedTypeConverter
     */
    public function findTypeConverterShouldReturnHighestPriorityTypeConverterForSimpleType($source, $targetType, $typeConverters, $expectedTypeConverter)
    {
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', $typeConverters);
        $actualTypeConverter = $propertyMapper->_call('findTypeConverter', $source, $targetType, $this->mockConfiguration);
        $this->assertSame($expectedTypeConverter, $actualTypeConverter->_name);
    }

    /**
     * @return array
     */
    public function dataProviderForObjectTypeConverters()
    {
        $data = [];

        $className2 = DataProviderTwo::class;
        $className3 = DataProviderThree::class;

        $interfaceName1 = DataProviderOneInterface::class;
        $interfaceName2 = DataProviderTwoInterface::class;
        $interfaceName3 = DataProviderThreeInterface::class;

        // The most specific converter should win
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class3Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter')],
                $className3 => [0 => $this->getMockTypeConverter('Class3Converter')],

                $interfaceName1 => [0 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [0 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [0 => $this->getMockTypeConverter('Interface3Converter')],
            ]
        ];

        // In case the most specific converter does not want to handle this conversion, the second one is taken.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class2Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter')],
                $className3 => [0 => $this->getMockTypeConverter('Class3Converter', false)],

                $interfaceName1 => [0 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [0 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [0 => $this->getMockTypeConverter('Interface3Converter')],
            ]
        ];

        // In case there is no most-specific-converter, we climb ub the type hierarchy
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class2Converter-HighPriority',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter'), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority')]
            ]
        ];

        // If no parent class converter wants to handle it, we ask for all interface converters.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Interface1Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter', false), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', false)],

                $interfaceName1 => [4 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [1 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [2 => $this->getMockTypeConverter('Interface3Converter')],
            ]
        ];

        // If two interface converters have the same priority, an exception is thrown.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Interface1Converter',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter', false), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', false)],

                $interfaceName1 => [4 => $this->getMockTypeConverter('Interface1Converter')],
                $interfaceName2 => [2 => $this->getMockTypeConverter('Interface2Converter')],
                $interfaceName3 => [2 => $this->getMockTypeConverter('Interface3Converter')],
            ],
            'shouldFailWithException' => \TYPO3\CMS\Extbase\Property\Exception\DuplicateTypeConverterException::class
        ];

        // If no interface converter wants to handle it, a converter for "object" is looked up.
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'GenericObjectConverter-HighPriority',
            'typeConverters' => [
                $className2 => [0 => $this->getMockTypeConverter('Class2Converter', false), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', false)],

                $interfaceName1 => [4 => $this->getMockTypeConverter('Interface1Converter', false)],
                $interfaceName2 => [3 => $this->getMockTypeConverter('Interface2Converter', false)],
                $interfaceName3 => [2 => $this->getMockTypeConverter('Interface3Converter', false)],
                'object' => [1 => $this->getMockTypeConverter('GenericObjectConverter'), 10 => $this->getMockTypeConverter('GenericObjectConverter-HighPriority')]
            ],
        ];

        // If the target is no valid class name and no simple type, an exception is thrown
        $data[] = [
            'target' => 'SomeNotExistingClassName',
            'expectedConverter' => 'GenericObjectConverter-HighPriority',
            'typeConverters' => [],
            'shouldFailWithException' => \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException::class
        ];

        // if the type converter is not found, we expect an exception
        $data[] = [
            'target' => $className3,
            'expectedConverter' => 'Class3Converter',
            'typeConverters' => [],
            'shouldFailWithException' => \TYPO3\CMS\Extbase\Property\Exception\TypeConverterException::class
        ];

        // If The target type is no string, we expect an exception.
        $data[] = [
            'target' => new \stdClass(),
            'expectedConverter' => '',
            'typeConverters' => [],
            'shouldFailWithException' => \TYPO3\CMS\Extbase\Property\Exception\InvalidTargetException::class
        ];
        return $data;
    }

    /**
     * @test
     * @dataProvider dataProviderForObjectTypeConverters
     * @param mixed $targetClass
     * @param mixed $expectedTypeConverter
     * @param mixed $typeConverters
     * @param bool $shouldFailWithException
     * @throws \Exception
     * @return void
     */
    public function findTypeConverterShouldReturnConverterForTargetObjectIfItExists($targetClass, $expectedTypeConverter, $typeConverters, $shouldFailWithException = false)
    {
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', ['string' => $typeConverters]);
        try {
            $actualTypeConverter = $propertyMapper->_call('findTypeConverter', 'someSourceString', $targetClass, $this->mockConfiguration);
            if ($shouldFailWithException) {
                $this->fail('Expected exception ' . $shouldFailWithException . ' which was not thrown.');
            }
            $this->assertSame($expectedTypeConverter, $actualTypeConverter->_name);
        } catch (\Exception $e) {
            if ($shouldFailWithException === false) {
                throw $e;
            }
            $this->assertInstanceOf($shouldFailWithException, $e);
        }
    }

    /**
     * @test
     */
    public function convertShouldAskConfigurationBuilderForDefaultConfiguration()
    {
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $this->inject($propertyMapper, 'configurationBuilder', $this->mockConfigurationBuilder);

        $this->mockConfigurationBuilder->expects($this->once())->method('build')->will($this->returnValue($this->mockConfiguration));

        $converter = $this->getMockTypeConverter('string2string');
        $typeConverters = [
            'string' => [
                'string' => [10 => $converter]
            ]
        ];

        $propertyMapper->_set('typeConverters', $typeConverters);
        $this->assertEquals('string2string', $propertyMapper->convert('source', 'string'));
    }

    /**
     * @test
     */
    public function findFirstEligibleTypeConverterInObjectHierarchyShouldReturnNullIfSourceTypeIsUnknown()
    {
        /** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $this->assertNull($propertyMapper->_call('findFirstEligibleTypeConverterInObjectHierarchy', 'source', 'unknownSourceType', \TYPO3\CMS\Extbase\Core\Bootstrap::class));
    }

    /**
     * @test
     */
    public function doMappingReturnsSourceUnchangedIfAlreadyConverted()
    {
        $source = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $targetType = \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class;
        $propertyPath = '';
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $this->assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
    }

    /**
     * @test
     */
    public function doMappingReturnsSourceUnchangedIfAlreadyConvertedToCompositeType()
    {
        $source = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $targetType = \TYPO3\CMS\Extbase\Persistence\ObjectStorage::class . '<SomeEntity>';
        $propertyPath = '';
        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $this->assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
    }

    /**
     * @test
     */
    public function convertSkipsPropertiesIfConfiguredTo()
    {
        $source = ['firstProperty' => 1, 'secondProperty' => 2];
        $typeConverters = [
            'array' => [
                'stdClass' => [10 => $this->getMockTypeConverter('array2object', true, $source, 'integer')]
            ],
            'integer' => [
                'integer' => [10 => $this->getMockTypeConverter('integer2integer')]
            ]
        ];
        $configuration = new \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration();

        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', $typeConverters);

        $propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipProperties('secondProperty'));
    }

    /**
     * @test
     */
    public function convertSkipsUnknownPropertiesIfConfiguredTo()
    {
        $source = ['firstProperty' => 1, 'secondProperty' => 2];
        $typeConverters = [
            'array' => [
                'stdClass' => [10 => $this->getMockTypeConverter('array2object', true, $source, 'integer')]
            ],
            'integer' => [
                'integer' => [10 => $this->getMockTypeConverter('integer2integer')]
            ]
        ];
        $configuration = new \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration();

        $propertyMapper = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Property\PropertyMapper::class, ['dummy']);
        $propertyMapper->_set('typeConverters', $typeConverters);

        $propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipUnknownProperties());
    }
}
