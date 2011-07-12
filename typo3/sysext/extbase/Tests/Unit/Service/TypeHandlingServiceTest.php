<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Extbase Team
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Testcase for class Tx_Extbase_Service_TypeHandling
 *
 * @package Extbase
 * @subpackage extbase
 */

class Tx_Extbase_Tests_Unit_Service_TypeHandlingServiceTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Extbase_Service_TypeHandlingService
	 */
	protected $typeHandlingService;

	public function setUp() {
		$this->typeHandlingService = new Tx_Extbase_Service_TypeHandlingService();
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeThrowsExceptionOnInvalidType() {
		$this->typeHandlingService->parseType('something not a type');
	}

	/**
	 * @test
	 * @expectedException InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeThrowsExceptionOnInvalidElementTypeHint() {
		$this->typeHandlingService->parseType('string<integer>');
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
			$this->typeHandlingService->parseType($type),
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
		$this->assertEquals($this->typeHandlingService->normalizeType($type), $normalized);
	}

	/**
	 * data provider for isLiteralReturnsFalseForNonLiteralTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function nonLiteralTypes() {
		return array(
			array('DateTime'),
			array('\Foo\Bar'),
			array('array'),
			array('ArrayObject'),
			array('stdClass')
		);
	}

	/**
	 * @test
	 * @dataProvider nonliteralTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isLiteralReturnsFalseForNonLiteralTypes($type) {
		$this->assertFalse($this->typeHandlingService->isLiteral($type));
	}

	/**
	 * data provider for isLiteralReturnsTrueForLiteralType
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function literalTypes() {
		return array(
			array('integer'),
			array('int'),
			array('float'),
			array('double'),
			array('boolean'),
			array('bool'),
			array('string')
		);
	}

	/**
	 * @test
	 * @dataProvider literalTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isLiteralReturnsTrueForLiteralType($type) {
		$this->assertTrue($this->typeHandlingService->isLiteral($type));
	}
}
?>