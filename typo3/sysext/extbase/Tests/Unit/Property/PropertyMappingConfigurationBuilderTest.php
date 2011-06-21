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
 * @covers Tx_Extbase_Property_PropertyMappingConfigurationBuilder
 */
class Tx_Extbase_Tests_Unit_Property_PropertyMappingConfigurationBuilderTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 *
	 * @var Tx_Extbase_Property_PropertyMappingConfigurationBuilder
	 */
	protected $propertyMappingConfigurationBuilder;

	public function setUp() {
		$this->propertyMappingConfigurationBuilder = new Tx_Extbase_Property_PropertyMappingConfigurationBuilder();
	}

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getTargetPropertyNameShouldReturnTheUnmodifiedPropertyNameWithoutConfiguration() {
		$defaultConfiguration = $this->propertyMappingConfigurationBuilder->build();
		$this->assertTrue($defaultConfiguration->getConfigurationValue('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter', Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertTrue($defaultConfiguration->getConfigurationValue('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter', Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));

		$this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter', Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED));
		$this->assertNull($defaultConfiguration->getConfigurationFor('foo')->getConfigurationValue('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter', Tx_Extbase_Property_TypeConverter_PersistentObjectConverter::CONFIGURATION_MODIFICATION_ALLOWED));
	}
}
?>