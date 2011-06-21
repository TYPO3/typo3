<?php

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

require_once (__DIR__ . '/../Fixtures/ClassWithSetters.php');

/**
 * Testcase for the Property Mapper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @covers Tx_Extbase_Property_PropertyMappingConfiguration
 */
class Tx_Extbase_Tests_Unit_Property_PropertyMappingConfigurationTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 *
	 * @var Tx_Extbase_Property_PropertyMappingConfiguration
	 */
	protected $propertyMappingConfiguration;

	public function setUp() {
		$this->propertyMappingConfiguration = new Tx_Extbase_Property_PropertyMappingConfiguration();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @covers Tx_Extbase_Property_PropertyMappingConfiguration::getTargetPropertyName
	 */
	public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration() {
		$this->assertEquals('someSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someSourceProperty'));
		$this->assertEquals('someOtherSourceProperty', $this->propertyMappingConfiguration->getTargetPropertyName('someOtherSourceProperty'));
	}

	/**
	 * @test
	 * @covers Tx_Extbase_Property_PropertyMappingConfiguration::shouldMap
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function shouldMapReturnsTrue() {
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someSourceProperty'));
		$this->assertTrue($this->propertyMappingConfiguration->shouldMap('someOtherSourceProperty'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTypeConverterOptionsCanBeRetrievedAgain() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->assertEquals('v1', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function inexistentTypeConverterOptionsReturnNull() {
		$this->assertNull($this->propertyMappingConfiguration->getConfigurationValue('foo', 'bar'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTypeConverterOptionsShouldOverrideAlreadySetOptions() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k3' => 'v3'));

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k3'));
		$this->assertNull($this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setTypeConverterOptionShouldOverrideAlreadySetOptions() {
		$this->propertyMappingConfiguration->setTypeConverterOptions('someConverter', array('k1' => 'v1', 'k2' => 'v2'));
		$this->propertyMappingConfiguration->setTypeConverterOption('someConverter', 'k1', 'v3');

		$this->assertEquals('v3', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k1'));
		$this->assertEquals('v2', $this->propertyMappingConfiguration->getConfigurationValue('someConverter', 'k2'));
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeConverterReturnsNullIfNoTypeConverterSet() {
		$this->assertNull($this->propertyMappingConfiguration->getTypeConverter());
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTypeConverterReturnsTypeConverterIfItHasBeenSet() {
		$mockTypeConverter = $this->getMock('Tx_Extbase_Property_TypeConverterInterface');
		$this->propertyMappingConfiguration->setTypeConverter($mockTypeConverter);
		$this->assertSame($mockTypeConverter, $this->propertyMappingConfiguration->getTypeConverter());
	}

	/**
	 * @return Tx_Extbase_Property_PropertyMappingConfiguration
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function buildChildConfigurationForSingleProperty() {
		$childConfiguration = $this->propertyMappingConfiguration->forProperty('key1.key2');
		$childConfiguration->setTypeConverterOption('someConverter', 'foo', 'specialChildConverter');

		return $childConfiguration;
	}


	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTargetPropertyNameShouldRespectMapping() {
		$this->propertyMappingConfiguration->setMapping('k1', 'k1a');
		$this->assertEquals('k1a', $this->propertyMappingConfiguration->getTargetPropertyName('k1'));
		$this->assertEquals('k2', $this->propertyMappingConfiguration->getTargetPropertyName('k2'));
	}
}
?>