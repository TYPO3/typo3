<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
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

require_once __DIR__ . '/../../Fixture/ClassWithSetters.php';

/**
 * Testcase for the Property Mapper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers \TYPO3\CMS\Extbase\Property\PropertyMapper
 */
class PropertyMapperTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	protected $mockConfigurationBuilder;

	protected $mockConfiguration;

	/**
	 * Sets up this test case
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUp() {
		$this->mockConfigurationBuilder = $this->getMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfigurationBuilder');
		$this->mockConfiguration = $this->getMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMappingConfigurationInterface');
	}

	/**
	 * @return array
	 */
	public function validSourceTypes() {
		return array(
			array('someString', 'string'),
			array(42, 'integer'),
			array(3.5, 'float'),
			array(TRUE, 'boolean'),
			array(array(), 'array')
		);
	}

	/**
	 * @test
	 * @dataProvider validSourceTypes
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @param mixed $source
	 * @param mixed $sourceType
	 */
	public function sourceTypeCanBeCorrectlyDetermined($source, $sourceType) {
		/** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$propertyMapper = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper', array('dummy'));
		$this->assertEquals($sourceType, $propertyMapper->_call('determineSourceType', $source));
	}

	/**
	 * @return array
	 */
	public function invalidSourceTypes() {
		return array(
			array(NULL),
			array(new \stdClass()),
			array(new \ArrayObject())
		);
	}

	/**
	 * @test
	 * @dataProvider invalidSourceTypes
	 * @expectedException \TYPO3\CMS\Extbase\Property\Exception\InvalidSourceException
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @param mixed $source
	 */
	public function sourceWhichIsNoSimpleTypeThrowsException($source) {
		/** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$propertyMapper = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper', array('dummy'));
		$propertyMapper->_call('determineSourceType', $source);
	}

	/**
	 * @param string $name
	 * @param boolean $canConvertFrom
	 * @param array $properties
	 * @param string $typeOfSubObject
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	protected function getMockTypeConverter($name = '', $canConvertFrom = TRUE, $properties = array(), $typeOfSubObject = '') {
		$mockTypeConverter = $this->getMock('TYPO3\\CMS\\Extbase\\Property\\TypeConverterInterface');
		$mockTypeConverter->_name = $name;
		$mockTypeConverter->expects($this->any())->method('canConvertFrom')->will($this->returnValue($canConvertFrom));
		$mockTypeConverter->expects($this->any())->method('convertFrom')->will($this->returnValue($name));
		$mockTypeConverter->expects($this->any())->method('getSourceChildPropertiesToBeConverted')->will($this->returnValue($properties));
		$mockTypeConverter->expects($this->any())->method('getTypeOfChildProperty')->will($this->returnValue($typeOfSubObject));
		return $mockTypeConverter;
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function findTypeConverterShouldReturnTypeConverterFromConfigurationIfItIsSet() {
		$mockTypeConverter = $this->getMockTypeConverter();
		$this->mockConfiguration->expects($this->any())->method('getTypeConverter')->will($this->returnValue($mockTypeConverter));
		/** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$propertyMapper = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper', array('dummy'));
		$this->assertSame($mockTypeConverter, $propertyMapper->_call('findTypeConverter', 'someSource', 'someTargetType', $this->mockConfiguration));
	}

	/**
	 * Simple type conversion
	 * @return array
	 */
	public function dataProviderForFindTypeConverter() {
		return array(
			array(
				'someStringSource',
				'string',
				array(
					'string' => array(
						'string' => array(
							10 => $this->getMockTypeConverter('string2string,prio10'),
							1 => $this->getMockTypeConverter('string2string,prio1')
						)
					)
				),
				'string2string,prio10'
			),
			array(array('some' => 'array'), 'string', array(
				'array' => array(
					'string' => array(
						10 => $this->getMockTypeConverter('array2string,prio10'),
						1 => $this->getMockTypeConverter('array2string,prio1')
					)
				)
			), 'array2string,prio10')
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForFindTypeConverter
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @param mixed $source
	 * @param mixed $targetType
	 * @param mixed $typeConverters
	 * @param mixed $expectedTypeConverter
	 */
	public function findTypeConverterShouldReturnHighestPriorityTypeConverterForSimpleType($source, $targetType, $typeConverters, $expectedTypeConverter) {
		$propertyMapper = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\PropertyMapper', array('dummy'));
		$propertyMapper->_set('typeConverters', $typeConverters);
		$actualTypeConverter = $propertyMapper->_call('findTypeConverter', $source, $targetType, $this->mockConfiguration);
		$this->assertSame($expectedTypeConverter, $actualTypeConverter->_name);
	}

	/**
	 * @return array
	 */
	public function dataProviderForObjectTypeConverters() {
		$data = array();

		$className1 = uniqid('TYPO3_Flow_Testclass1_', FALSE);
		$className2 = uniqid('TYPO3_Flow_Testclass2_', FALSE);
		$className3 = uniqid('TYPO3_Flow_Testclass3_', FALSE);

		$interfaceName1 = uniqid('TYPO3_Flow_TestInterface1_', FALSE);
		$interfaceName2 = uniqid('TYPO3_Flow_TestInterface2_', FALSE);
		$interfaceName3 = uniqid('TYPO3_Flow_TestInterface3_', FALSE);

		eval("
			interface $interfaceName2 {}
			interface $interfaceName1 {}

			interface $interfaceName3 extends $interfaceName2 {}

			class $className1 implements $interfaceName1 {}
			class $className2 extends $className1 {}
			class $className3 extends $className2 implements $interfaceName3 {}
		");

		// The most specific converter should win
		$data[] = array(
			'target' => $className3,
			'expectedConverter' => 'Class3Converter',
			'typeConverters' => array(
				$className2 => array(0 => $this->getMockTypeConverter('Class2Converter')),
				$className3 => array(0 => $this->getMockTypeConverter('Class3Converter')),

				$interfaceName1 => array(0 => $this->getMockTypeConverter('Interface1Converter')),
				$interfaceName2 => array(0 => $this->getMockTypeConverter('Interface2Converter')),
				$interfaceName3 => array(0 => $this->getMockTypeConverter('Interface3Converter')),
			)
		);

		// In case the most specific converter does not want to handle this conversion, the second one is taken.
		$data[] = array(
			'target' => $className3,
			'expectedConverter' => 'Class2Converter',
			'typeConverters' => array(
				$className2 => array(0 => $this->getMockTypeConverter('Class2Converter')),
				$className3 => array(0 => $this->getMockTypeConverter('Class3Converter', FALSE)),

				$interfaceName1 => array(0 => $this->getMockTypeConverter('Interface1Converter')),
				$interfaceName2 => array(0 => $this->getMockTypeConverter('Interface2Converter')),
				$interfaceName3 => array(0 => $this->getMockTypeConverter('Interface3Converter')),
			)
		);

		// In case there is no most-specific-converter, we climb ub the type hierarchy
		$data[] = array(
			'target' => $className3,
			'expectedConverter' => 'Class2Converter-HighPriority',
			'typeConverters' => array(
				$className2 => array(0 => $this->getMockTypeConverter('Class2Converter'), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority'))
			)
		);

		// If no parent class converter wants to handle it, we ask for all interface converters.
		$data[] = array(
			'target' => $className3,
			'expectedConverter' => 'Interface1Converter',
			'typeConverters' => array(
				$className2 => array(0 => $this->getMockTypeConverter('Class2Converter', FALSE), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', FALSE)),

				$interfaceName1 => array(4 => $this->getMockTypeConverter('Interface1Converter')),
				$interfaceName2 => array(1 => $this->getMockTypeConverter('Interface2Converter')),
				$interfaceName3 => array(2 => $this->getMockTypeConverter('Interface3Converter')),
			)
		);

		// If two interface converters have the same priority, an exception is thrown.
		$data[] = array(
			'target' => $className3,
			'expectedConverter' => 'Interface1Converter',
			'typeConverters' => array(
				$className2 => array(0 => $this->getMockTypeConverter('Class2Converter', FALSE), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', FALSE)),

				$interfaceName1 => array(4 => $this->getMockTypeConverter('Interface1Converter')),
				$interfaceName2 => array(2 => $this->getMockTypeConverter('Interface2Converter')),
				$interfaceName3 => array(2 => $this->getMockTypeConverter('Interface3Converter')),
			),
			'shouldFailWithException' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\DuplicateTypeConverterException'
		);

		// If no interface converter wants to handle it, a converter for "object" is looked up.
		$data[] = array(
			'target' => $className3,
			'expectedConverter' => 'GenericObjectConverter-HighPriority',
			'typeConverters' => array(
				$className2 => array(0 => $this->getMockTypeConverter('Class2Converter', FALSE), 10 => $this->getMockTypeConverter('Class2Converter-HighPriority', FALSE)),

				$interfaceName1 => array(4 => $this->getMockTypeConverter('Interface1Converter', FALSE)),
				$interfaceName2 => array(3 => $this->getMockTypeConverter('Interface2Converter', FALSE)),
				$interfaceName3 => array(2 => $this->getMockTypeConverter('Interface3Converter', FALSE)),
				'object' => array(1 => $this->getMockTypeConverter('GenericObjectConverter'), 10 => $this->getMockTypeConverter('GenericObjectConverter-HighPriority'))
			),
		);

		// If the target is no valid class name and no simple type, an exception is thrown
		$data[] = array(
			'target' => 'SomeNotExistingClassName',
			'expectedConverter' => 'GenericObjectConverter-HighPriority',
			'typeConverters' => array(),
			'shouldFailWithException' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidTargetException'
		);

		// if the type converter is not found, we expect an exception
		$data[] = array(
			'target' => $className3,
			'expectedConverter' => 'Class3Converter',
			'typeConverters' => array(),
			'shouldFailWithException' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\TypeConverterException'
		);

		// If The target type is no string, we expect an exception.
		$data[] = array(
			'target' => new \stdClass(),
			'expectedConverter' => '',
			'typeConverters' => array(),
			'shouldFailWithException' => 'TYPO3\\CMS\\Extbase\\Property\\Exception\\InvalidTargetException'
		);
		return $data;
	}

	/**
	 * @test
	 * @dataProvider dataProviderForObjectTypeConverters
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @param mixed $targetClass
	 * @param mixed $expectedTypeConverter
	 * @param mixed $typeConverters
	 * @param boolean $shouldFailWithException
	 * @throws \Exception
	 * @return void
	 */
	public function findTypeConverterShouldReturnConverterForTargetObjectIfItExists($targetClass, $expectedTypeConverter, $typeConverters, $shouldFailWithException = FALSE) {
		$propertyMapper = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\PropertyMapper', array('dummy'));
		$propertyMapper->_set('typeConverters', array('string' => $typeConverters));
		try {
			$actualTypeConverter = $propertyMapper->_call('findTypeConverter', 'someSourceString', $targetClass, $this->mockConfiguration);
			if ($shouldFailWithException) {
				$this->fail('Expected exception ' . $shouldFailWithException . ' which was not thrown.');
			}
			$this->assertSame($expectedTypeConverter, $actualTypeConverter->_name);
		} catch (\Exception $e) {
			if ($shouldFailWithException === FALSE) {
				throw $e;
			}
			$this->assertInstanceOf($shouldFailWithException, $e);
		}
	}

	/**
	 * @test
	 */
	public function convertShouldAskConfigurationBuilderForDefaultConfiguration() {
		$propertyMapper = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\PropertyMapper', array('dummy'));
		$this->inject($propertyMapper, 'configurationBuilder', $this->mockConfigurationBuilder);

		$this->mockConfigurationBuilder->expects($this->once())->method('build')->will($this->returnValue($this->mockConfiguration));

		$converter = $this->getMockTypeConverter('string2string');
		$typeConverters = array(
			'string' => array(
				'string' => array(10 => $converter)
			)
		);

		$propertyMapper->_set('typeConverters', $typeConverters);
		$this->assertEquals('string2string', $propertyMapper->convert('source', 'string'));
	}

	/**
	 * @test
	 */
	public function findFirstEligibleTypeConverterInObjectHierarchyShouldReturnNullIfSourceTypeIsUnknown() {
		/** @var \TYPO3\CMS\Extbase\Property\PropertyMapper|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$propertyMapper = $this->getAccessibleMock('TYPO3\\CMS\\Extbase\\Property\\PropertyMapper', array('dummy'));
		$this->assertNull($propertyMapper->_call('findFirstEligibleTypeConverterInObjectHierarchy', 'source', 'unknownSourceType', 'TYPO3\\CMS\\Extbase\\Core\\Bootstrap'));
	}

	/**
	 * @test
	 */
	public function doMappingReturnsSourceUnchangedIfAlreadyConverted() {
		$source = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$targetType = 'TYPO3\CMS\Extbase\Persistence\ObjectStorage';
		$propertyPath = '';
		$propertyMapper = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\PropertyMapper', array('dummy'));
		$this->assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
	}

	/**
	 * @test
	 */
	public function doMappingReturnsSourceUnchangedIfAlreadyConvertedToCompositeType() {
		$source = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
		$targetType = 'TYPO3\CMS\Extbase\Persistence\ObjectStorage<SomeEntity>';
		$propertyPath = '';
		$propertyMapper = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\PropertyMapper', array('dummy'));
		$this->assertSame($source, $propertyMapper->_callRef('doMapping', $source, $targetType, $this->mockConfiguration, $propertyPath));
	}

	/**
	 * @test
	 */
	public function convertSkipsPropertiesIfConfiguredTo() {
		$source = array('firstProperty' => 1, 'secondProperty' => 2);
		$typeConverters = array(
			'array' => array(
				'stdClass' => array(10 => $this->getMockTypeConverter('array2object', TRUE, $source, 'integer'))
			),
			'integer' => array(
				'integer' => array(10 => $this->getMockTypeConverter('integer2integer'))
			)
		);
		$configuration = new \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration();

		$propertyMapper = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\PropertyMapper', array('dummy'));
		$propertyMapper->_set('typeConverters', $typeConverters);

		$propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipProperties('secondProperty'));
	}

	/**
	 * @test
	 */
	public function convertSkipsUnknownPropertiesIfConfiguredTo() {
		$source = array('firstProperty' => 1, 'secondProperty' => 2);
		$typeConverters = array(
			'array' => array(
				'stdClass' => array(10 => $this->getMockTypeConverter('array2object', TRUE, $source, 'integer'))
			),
			'integer' => array(
				'integer' => array(10 => $this->getMockTypeConverter('integer2integer'))
			)
		);
		$configuration = new \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration();

		$propertyMapper = $this->getAccessibleMock('TYPO3\CMS\Extbase\Property\PropertyMapper', array('dummy'));
		$propertyMapper->_set('typeConverters', $typeConverters);

		$propertyMapper->convert($source, 'stdClass', $configuration->allowProperties('firstProperty')->skipUnknownProperties());
	}
}

?>