<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Property\TypeConverter;

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

/**
 * Testcase for the ObjectConverter
 *
 * @covers \TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter<extended>
 */
class ObjectConverterTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter
	 */
	protected $converter;

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $mockReflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $mockObjectManager;

	public function setUp() {
		$this->mockReflectionService = $this->getMock('TYPO3\CMS\Extbase\Reflection\ReflectionService');
		$this->mockObjectManager = $this->getMock('TYPO3\CMS\Extbase\Object\ObjectManagerInterface');

		$this->converter = new \TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter();
		$this->inject($this->converter, 'reflectionService', $this->mockReflectionService);
		$this->inject($this->converter, 'objectManager', $this->mockObjectManager);
	}

	/**
	 * @test
	 */
	public function checkMetadata() {
		$this->assertEquals(array('array'), $this->converter->getSupportedSourceTypes(), 'Source types do not match');
		$this->assertEquals('object', $this->converter->getSupportedTargetType(), 'Target type does not match');
		$this->assertEquals(0, $this->converter->getPriority(), 'Priority does not match');
	}

	public function dataProviderForCanConvert() {
		return array(
			// Is entity => cannot convert
			array('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\Entity', FALSE),
			// Is valueobject => cannot convert
			array('TYPO3\\CMS\\Extbase\\Tests\\Fixture\\ValueObject', FALSE),
			// Is no entity and no value object => can convert
			array('stdClass', TRUE)
		);
	}

	/**
	 * @test
	 * @dataProvider dataProviderForCanConvert
	 */
	public function canConvertFromReturnsTrueIfClassIsTaggedWithEntityOrValueObject($className, $expected) {
		$this->assertEquals($expected, $this->converter->canConvertFrom('myInputData', $className));
	}

	/**
	 * @test
	 */
	public function getTypeOfChildPropertyShouldUseReflectionServiceToDetermineType() {
		$this->mockReflectionService->expects($this->any())->method('hasMethod')->with('TheTargetType', 'setThePropertyName')->will($this->returnValue(FALSE));
		$this->mockReflectionService->expects($this->any())->method('getMethodParameters')->with('TheTargetType', '__construct')->will($this->returnValue(array(
			'thePropertyName' => array(
				'type' => 'TheTypeOfSubObject',
				'elementType' => NULL
			)
		)));
		$configuration = new \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration();
		$configuration->setTypeConverterOptions('TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter', array());
		$this->assertEquals('TheTypeOfSubObject', $this->converter->getTypeOfChildProperty('TheTargetType', 'thePropertyName', $configuration));
	}

}
?>