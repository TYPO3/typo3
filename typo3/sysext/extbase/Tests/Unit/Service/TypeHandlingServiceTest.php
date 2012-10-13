<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

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
 * Testcase for class \TYPO3\CMS\Extbase\Service\TypeHandling
 */
class TypeHandlingServiceTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypeHandlingService
	 */
	protected $typeHandlingService;

	public function setUp() {
		$this->typeHandlingService = new \TYPO3\CMS\Extbase\Service\TypeHandlingService();
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 */
	public function parseTypeThrowsExceptionOnInvalidType() {
		$this->typeHandlingService->parseType('something not a type');
	}

	/**
	 * @test
	 * @expectedException \InvalidArgumentException
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function parseTypeThrowsExceptionOnInvalidElementTypeHint() {
		$this->typeHandlingService->parseType('string<integer>');
	}

	/**
	 * data provider for parseTypeReturnsArrayWithInformation
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @return array
	 */
	public function types() {
		return array(
			array('int', array('type' => 'integer', 'elementType' => NULL)),
			array('string', array('type' => 'string', 'elementType' => NULL)),
			array('DateTime', array('type' => 'DateTime', 'elementType' => NULL)),
			array('\DateTime', array('type' => 'DateTime', 'elementType' => NULL)),
			array('Tx_Extbase_Bar', array('type' => 'Tx_Extbase_Bar', 'elementType' => NULL)),
			array('\\ExtbaseTeam\\BlogExample\\Foo\\Bar', array('type' => 'ExtbaseTeam\\BlogExample\\Foo\\Bar', 'elementType' => NULL)),
			array('array<integer>', array('type' => 'array', 'elementType' => 'integer')),
			array('ArrayObject<string>', array('type' => 'ArrayObject', 'elementType' => 'string')),
			array('SplObjectStorage<Tx_Extbase_Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'Tx_Extbase_Bar')),
			array('SplObjectStorage<\\ExtbaseTeam\\BlogExample\\Foo\\Bar>', array('type' => 'SplObjectStorage', 'elementType' => 'ExtbaseTeam\\BlogExample\\Foo\\Bar')),
			array('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage<Tx_Extbase_Bar>', array('type' => 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', 'elementType' => 'Tx_Extbase_Bar')),
			array('TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage<\\ExtbaseTeam\\BlogExample\\Foo\\Bar>', array('type' => 'TYPO3\\CMS\\Extbase\\Persistence\\ObjectStorage', 'elementType' => 'ExtbaseTeam\\BlogExample\\Foo\\Bar')),
			array('Tx_Extbase_Persistence_ObjectStorage<Tx_Extbase_Bar>', array('type' => 'Tx_Extbase_Persistence_ObjectStorage', 'elementType' => 'Tx_Extbase_Bar')),
			array('Tx_Extbase_Persistence_ObjectStorage<\\ExtbaseTeam\\BlogExample\\Foo\\Bar>', array('type' => 'Tx_Extbase_Persistence_ObjectStorage', 'elementType' => 'ExtbaseTeam\\BlogExample\\Foo\\Bar')),
		);
	}

	/**
	 * @test
	 * @dataProvider types
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @param mixed $type
	 * @param mixed $expectedResult
	 */
	public function parseTypeReturnsArrayWithInformation($type, $expectedResult) {
		$this->assertEquals($expectedResult, $this->typeHandlingService->parseType($type));
	}

	/**
	 * data provider for normalizeTypesReturnsNormalizedType
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @return array
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
	 * @param mixed $type
	 * @param mixed $normalized
	 */
	public function normalizeTypesReturnsNormalizedType($type, $normalized) {
		$this->assertEquals($this->typeHandlingService->normalizeType($type), $normalized);
	}

	/**
	 * data provider for isLiteralReturnsFalseForNonLiteralTypes
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @return array
	 */
	public function nonLiteralTypes() {
		return array(
			array('DateTime'),
			array('\\Foo\\Bar'),
			array('array'),
			array('ArrayObject'),
			array('stdClass')
		);
	}

	/**
	 * @test
	 * @dataProvider nonliteralTypes
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @param mixed $type
	 */
	public function isLiteralReturnsFalseForNonLiteralTypes($type) {
		$this->assertFalse($this->typeHandlingService->isLiteral($type));
	}

	/**
	 * data provider for isLiteralReturnsTrueForLiteralType
	 *
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @return array
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
	 * @param mixed $type
	 */
	public function isLiteralReturnsTrueForLiteralType($type) {
		$this->assertTrue($this->typeHandlingService->isLiteral($type));
	}

}


?>