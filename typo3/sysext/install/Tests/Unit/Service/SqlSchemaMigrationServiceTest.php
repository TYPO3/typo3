<?php
namespace TYPO3\CMS\Install\Tests\Unit\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Mario Rimann <mario.rimann@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Install\Service\SqlSchemaMigrationService;

/**
 * Test case
 *
 * @author Mario Rimann <mario.rimann@typo3.org>
 */
class SqlSchemaMigrationServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Install\Service\SqlSchemaMigrationService
	 */
	protected $fixture;

	/**
	 * Sets up the fixture for testing
	 */
	public function setUp() {
		$this->fixture = new SqlSchemaMigrationService();
	}

	/**
	 * @test
	 */
	public function getDatabaseExtraFindsChangedFields() {
		$differenceArray = $this->fixture->getDatabaseExtra(
			array(
				'tx_foo' => array(
					'fields' => array(
						'foo' => 'varchar(999) DEFAULT \'0\' NOT NULL'
					)
				)
			),
			array(
				'tx_foo' => array(
					'fields' => array(
						'foo' => 'varchar(255) DEFAULT \'0\' NOT NULL'
					)
				)
			)
		);



		$this->assertEquals(
			$differenceArray,
			array(
				'extra' => array(),
				'diff' => array(
					'tx_foo' => array(
						'fields' => array(
							'foo' => 'varchar(999) DEFAULT \'0\''
						)
					)
				),
				'diff_currentValues' => array(
					'tx_foo' => array(
						'fields' => array(
							'foo' => 'varchar(255) DEFAULT \'0\''
						)
					)
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function getDatabaseExtraIgnoresCaseDifference() {
		$differenceArray = $this->fixture->getDatabaseExtra(
			array(
				'tx_foo' => array(
					'fields' => array(
						'foo' => 'INT(11) DEFAULT \'0\' NOT NULL'
					)
				)
			),
			array(
				'tx_foo' => array(
					'fields' => array(
						'foo' => 'int(11) DEFAULT \'0\' NOT NULL'
					)
				)
			)
		);


		$this->assertEquals(
			$differenceArray,
			array(
				'extra' => array(),
				'diff' => array(),
				'diff_currentValues' => NULL
			)
		);
	}

	/**
	 * @test
	 */
	public function getDatabaseExtraDoesNotLowercaseReservedWordsForTheComparison() {
		$differenceArray = $this->fixture->getDatabaseExtra(
			array(
				'tx_foo' => array(
					'fields' => array(
						'PRIMARY KEY (md5hash)'
					)
				)
			),
			array(
				'tx_foo' => array(
					'fields' => array(
						'PRIMARY KEY (md5hash)')
				)
			)
		);


		$this->assertEquals(
			$differenceArray,
			array(
				'extra' => array(),
				'diff' => array(),
				'diff_currentValues' => NULL
			)
		);
	}
}
