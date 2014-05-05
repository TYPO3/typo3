<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

/***************************************************************
 * Copyright notice
 *
 * (c) 2010-2014 Xavier Perseguers <xavier@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case
 */
class DatabaseConnectionMssqlTest extends AbstractTestCase {

	/**
	 * @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	protected $subject;

	/**
	 * Prepare a DatabaseConnection subject ready to parse mssql queries
	 *
	 * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
	 */
	public function setUp() {
		$configuration = array(
			'handlerCfg' => array(
				'_DEFAULT' => array(
					'type' => 'adodb',
					'config' => array(
						'driver' => 'mssql',
					),
				),
			),
			'mapping' => array(
				'tx_templavoila_tmplobj' => array(
					'mapFieldNames' => array(
						'datastructure' => 'ds',
					),
				),
				'Members' => array(
					'mapFieldNames' => array(
						'pid' => '0',
						'cruser_id' => '1',
						'uid' => 'MemberID',
					),
				),
			),
		);
		$this->subject = $this->prepareSubject('mssql', $configuration);
	}

	/**
	 * @test
	 */
	public function runningADOdbDriverReturnsTrueWithMssqlForMssqlDefaultDriverConfiguration() {
		$this->assertTrue($this->subject->runningADOdbDriver('mssql'));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/23087
	 */
	public function findInSetIsProperlyRemapped() {
		$expected = 'SELECT * FROM "fe_users" WHERE \',\'+"usergroup"+\',\' LIKE \'%,10,%\'';
		$result = $this->subject->SELECTquery('*', 'fe_users', 'FIND_IN_SET(10, usergroup)');
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/27858
	 */
	public function canParseSingleQuote() {
		$parseString = 'SELECT * FROM pages WHERE title=\'1\'\'\' AND deleted=0';
		$components = $this->subject->SQLparser->_callRef('parseSELECT', $parseString);

		$this->assertTrue(is_array($components), $components);
		$this->assertEmpty($components['parseString']);
	}

	///////////////////////////////////////
	// Tests concerning remapping with
	// external (non-TYPO3) databases
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://forge.typo3.org/issues/22096
	 */
	public function canRemapPidToZero() {
		$selectFields = 'uid, FirstName, LastName';
		$fromTables = 'Members';
		$whereClause = 'pid=0 AND cruser_id=1';
		$groupBy = '';
		$orderBy = '';

		$remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);
		$result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
		$expected = 'SELECT "MemberID", "FirstName", "LastName" FROM "Members" WHERE 0 = 0 AND 1 = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	///////////////////////////////////////
	// Tests concerning advanced operators
	///////////////////////////////////////
	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21902
	 */
	public function locateStatementIsProperlyQuoted() {
		$result = $this->subject->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $this->subject->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1');
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "datastructure") > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21902
	 */
	public function locateStatementWithPositionIsProperlyQuoted() {
		$result = $this->subject->SELECTquery('*, CASE WHEN' . ' LOCATE(' . $this->subject->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope', 'tx_templavoila_tmplobj', '1=1');
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "datastructure", 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21902
	 */
	public function locateStatementIsProperlyRemapped() {
		$selectFields = '*, CASE WHEN' . ' LOCATE(' . $this->subject->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope';
		$fromTables = 'tx_templavoila_tmplobj';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

		$result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "ds", 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/21902
	 */
	public function locateStatementWithExternalTableIsProperlyRemapped() {
		$selectFields = '*, CASE WHEN' . ' LOCATE(' . $this->subject->fullQuoteStr('(fce)', 'tx_templavoila_tmplobj') . ', tx_templavoila_tmplobj.datastructure, 4)>0 THEN 2' . ' ELSE 1' . ' END AS scope';
		$fromTables = 'tx_templavoila_tmplobj';
		$whereClause = '1=1';
		$groupBy = '';
		$orderBy = '';
		$remappedParameters = $this->subject->_call('map_remapSELECTQueryParts', $selectFields, $fromTables, $whereClause, $groupBy, $orderBy);

		$result = $this->subject->_call('SELECTqueryFromArray', $remappedParameters);
		$expected = 'SELECT *, CASE WHEN CHARINDEX(\'(fce)\', "tx_templavoila_tmplobj"."ds", 4) > 0 THEN 2 ELSE 1 END AS "scope" FROM "tx_templavoila_tmplobj" WHERE 1 = 1';
		$this->assertEquals($expected, $this->cleanSql($result));
	}

	/**
	 * @test
	 * @see http://forge.typo3.org/issues/17552
	 */
	public function IfNullIsProperlyRemapped() {
		$result = $this->subject->SELECTquery('*', 'tt_news_cat_mm', 'IFNULL(tt_news_cat_mm.uid_foreign,0) IN (21,22)');
		$expected = 'SELECT * FROM "tt_news_cat_mm" WHERE ISNULL("tt_news_cat_mm"."uid_foreign", 0) IN (21,22)';
		$this->assertEquals($expected, $this->cleanSql($result));
	}
}