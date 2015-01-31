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
	 * @test
	 */
	public function getFieldDefinitionsFileContentHandlesMultipleWhitespacesInFieldDefinitions() {
		$subject = new SqlSchemaMigrationService();
		// Multiple whitespaces and tabs in field definition
		$inputString = 'CREATE table atable (' . LF . 'aFieldName   int(11)' . TAB . TAB . TAB . 'unsigned   DEFAULT \'0\'' . LF . ');';
		$result = $subject->getFieldDefinitions_fileContent($inputString);

		$this->assertEquals(
			array(
				'atable' => array(
					'fields' => array(
						'aFieldName' => 'int(11) unsigned default \'0\'',
					),
					'extra' => array(
						'COLLATE' => '',
					),
				),
			),
			$result
		);
	}

	/**
	 * @test
	 */
	public function getDatabaseExtraFindsChangedFields() {
		$subject = new SqlSchemaMigrationService();
		$differenceArray = $subject->getDatabaseExtra(
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
							'foo' => 'varchar(999) DEFAULT \'0\' NOT NULL'
						)
					)
				),
				'diff_currentValues' => array(
					'tx_foo' => array(
						'fields' => array(
							'foo' => 'varchar(255) DEFAULT \'0\' NOT NULL'
						)
					)
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function getDatabaseExtraFindsChangedFieldsIncludingNull() {
		$subject = new SqlSchemaMigrationService();
		$differenceArray = $subject->getDatabaseExtra(
			array(
				'tx_foo' => array(
					'fields' => array(
						'foo' => 'varchar(999) NULL'
					)
				)
			),
			array(
				'tx_foo' => array(
					'fields' => array(
						'foo' => 'varchar(255) NULL'
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
							'foo' => 'varchar(999) NULL'
						)
					)
				),
				'diff_currentValues' => array(
					'tx_foo' => array(
						'fields' => array(
							'foo' => 'varchar(255) NULL'
						)
					)
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function getDatabaseExtraFindsChangedFieldsIgnoreNotNull() {
		$subject = new SqlSchemaMigrationService();
		$differenceArray = $subject->getDatabaseExtra(
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
			),
			'',
			TRUE
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
		$subject = new SqlSchemaMigrationService();
		$differenceArray = $subject->getDatabaseExtra(
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
		$subject = new SqlSchemaMigrationService();
		$differenceArray = $subject->getDatabaseExtra(
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
