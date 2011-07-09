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

/**
 * Testcase for the Utility\TypeHandling class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @deprecated since Extbase 1.4.0; will be removed in Extbase 1.6.0. Please use Tx_Extbase_Tests_Unit_Service_TypeHandlingServiceTest instead
 */
class Tx_Extbase_Tests_Unit_Utility_TypeHandlingTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeThrowsExceptionOnInvalidType() {
		Tx_Extbase_Utility_TypeHandling::parseType('something not a type');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeThrowsExceptionOnInvalidElementTypeHint() {
		Tx_Extbase_Utility_TypeHandling::parseType('string<integer>');
	}

	/**
	 * data provider for parseTypeReturnsArrayWithInformation
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function types() {
		return array(
			array('int', array('type' => 'integer', 'elementType' => NULL)),
			array('string', array('type' => 'string', 'elementType' => NULL)),
			array('DateTime', array('type' => 'DateTime', 'elementType' => NULL)),
			array('Tx_Extbase_Bar', array('type' => 'Tx_Extbase_Bar', 'elementType' => NULL)),
			array('Tx_Extbase_Bar', array('type' => 'Tx_Extbase_Bar', 'elementType' => NULL)),
			array('array<integer>', array('type' => 'array', 'elementType' => 'integer')),
			array('ArrayObject<string>', array('type' => 'ArrayObject', 'elementType' => 'string')),
			array('SplObjectStorage<Tx_Extbase_Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'Tx_Extbase_Bar')),
			array('SplObjectStorage<Tx_Extbase_Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'Tx_Extbase_Bar')),
		);
	}

	/**
	 * @test
	 * @dataProvider types
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeReturnsArrayWithInformation($type, $expectedResult) {
		$this->assertEquals(
			Tx_Extbase_Utility_TypeHandling::parseType($type),
			$expectedResult
		);
	}

	/**
	 * data provider for normalizeTypesReturnsNormalizedType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function normalizeTypes() {
		return array(
			array('int', 'integer'),
			array('double', 'float'),
			array('bool', 'boolean'),
			array('string', 'string')
		);
	}

	/**
	 * @test
	 * @dataProvider normalizeTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function normalizeTypesReturnsNormalizedType($type, $normalized) {
		$this->assertEquals(Tx_Extbase_Utility_TypeHandling::normalizeType($type), $normalized);
	}
}
?>