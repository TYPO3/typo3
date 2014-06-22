<?php
namespace TYPO3\CMS\Install\Tests\Unit\Service;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
