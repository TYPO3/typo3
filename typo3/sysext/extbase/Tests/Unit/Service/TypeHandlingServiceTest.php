<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
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
	 * @expectedException \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException
	 */
	public function parseTypeThrowsExceptionOnInvalidType() {
		$this->typeHandlingService->parseType('something not a type');
	}

	/**
	 * @test
	 * @expectedException \TYPO3\CMS\Extbase\Utility\Exception\InvalidTypeException
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

	/**
	 * data provider for isSimpleTypeReturnsTrueForSimpleTypes
	 *
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 * @return array
	 */
	public function simpleTypes() {
		return array(
			array('integer', TRUE),
			array('int', TRUE),
			array('float', TRUE),
			array('double', TRUE),
			array('boolean', TRUE),
			array('bool', TRUE),
			array('string', TRUE),
			array('DateTime', FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider simpleTypes
	 * @author Alexander Schnitzler <alex.schnitzler@typovision.de>
	 * @param string $type
	 * @param boolean $result
	 */
	public function isSimpleTypeReturnsOnlyTrueForSimpleTypes($type, $result) {
		$this->assertSame($result, $this->typeHandlingService->isSimpleType($type));
	}
}

?>