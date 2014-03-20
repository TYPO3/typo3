<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE;

/***************************************************************
*  Copyright notice
*
*  (c) 2010 Oliver Hader <oliver@typo3.org>
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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generic test helpers.
 *
 */
abstract class AbstractTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {
	const VALUE_LanguageId = 2;

	const TABLE_Pages = 'pages';

	const COMMAND_Copy = 'copy';
	const COMMAND_Localize = 'localize';
	const COMMAND_Delete = 'delete';

	const PROPERTY_LocalizeReferencesAtParentLocalization = 'localizeReferencesAtParentLocalization';
	const BEHAVIOUR_LocalizeChildrenAtParentLocalization = 'localizeChildrenAtParentLocalization';
	const BEHAVIOUR_LocalizationMode = 'localizationMode';

	protected $testExtensionsToLoad = array('typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial');

	/**
	 * @var integer
	 */
	private $expectedLogEntries = 0;

	/**
	 * Sets up this test case.
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->setUpBackendUserFromFixture(1);
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

		$this->expectedLogEntries = 0;

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['sqlDebug'] = 1;

		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/pages.xml');
		$this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/sys_language.xml');
	}

	/**
	 * Tears down this test case.
	 *
	 * @return void
	 */
	public function tearDown() {
		$this->assertNoLogEntries();

		$this->expectedLogEntries = 0;

		parent::tearDown();
	}

	/**
	 * Sets the number of expected log entries.
	 *
	 * @param integer $count
	 * @return void
	 */
	protected function setExpectedLogEntries($count) {
		$count = (int)$count;

		if ($count > 0) {
			$this->expectedLogEntries = $count;
		}
	}

	/**
	 * @param string $command
	 * @param mixed $value
	 * @param array $tables Table names with list of ids to be edited
	 * @return array
	 */
	protected function getElementStructureForCommands($command, $value, array $tables) {
		$commandStructure = array();

		foreach ($tables as $tableName => $idList) {
			$ids = GeneralUtility::trimExplode(',', $idList, TRUE);
			foreach ($ids as $id) {
				$commandStructure[$tableName][$id] = array(
					$command => $value
				);
			}
		}

		return $commandStructure;
	}

	/**
	 * Simulates executing commands by using t3lib_TCEmain.
	 *
	 * @param  array $elements The cmdmap to be delivered to DataHandler
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function simulateCommandByStructure(array $elements) {
		$tceMain = $this->getTceMain();
		$tceMain->start(array(), $elements);
		$tceMain->process_cmdmap();

		return $tceMain;
	}

	/**
	 * @param string $command
	 * @param mixed $value
	 * @param array $tables Table names with list of ids to be edited
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function simulateCommand($command, $value, array $tables) {
		return $this->simulateCommandByStructure(
			$this->getElementStructureForCommands($command, $value, $tables)
		);
	}

	/**
	 * Gets the last log entry.
	 *
	 * @return array
	 */
	protected function getLastLogEntryMessage() {
		$message = '';

		$logEntries = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'sys_log', 'error IN (1,2)', '', '', 1);

		if (is_array($logEntries) && count($logEntries)) {
			$message = $logEntries[0]['details'];
		}

		return $message;
	}

	/**
	 * @param  array $itemArray
	 * @return array
	 */
	protected function getElementsByItemArray(array $itemArray) {
		$elements = array();

		foreach ($itemArray as $item) {
			$elements[$item['table']][$item['id']] = BackendUtility::getRecord($item['table'], $item['id']);
		}

		return $elements;
	}

	/**
	 * Gets all records of a table.
	 *
	 * @param string $table Name of the table
	 * @param string $indexField
	 * @return array
	 */
	protected function getAllRecords($table, $indexField = 'uid') {
		return $this->getDatabaseConnection()->exec_SELECTgetRows('*', $table, '1=1', '', '', '', $indexField);
	}

	/**
	 * Gets the TCE configuration of a field.
	 *
	 * @param  $tableName
	 * @param  $fieldName
	 * @return array
	 */
	protected function getTcaFieldConfiguration($tableName, $fieldName) {
		if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'])) {
			$this->fail('TCA definition for field ' . $tableName . '.' . $fieldName . ' not available');
		}

		return $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
	}

	/**
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string $propertyName
	 * @param mixed $value
	 * @return void
	 */
	protected function setTcaFieldConfiguration($tableName, $fieldName, $propertyName, $value) {
		if (isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'])) {
			$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'][$propertyName] = $value;
		}
	}

	/**
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string $behaviourName
	 * @param mixed $value
	 * @return void
	 */
	protected function setTcaFieldConfigurationBehaviour($tableName, $fieldName, $behaviourName, $value) {
		if (isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'])) {
			if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['behaviour'])) {
				$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['behaviour'] = array();
			}

			$GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['behaviour'][$behaviourName] = $value;
		}
	}

	/**
	 * Gets the field value of a record.
	 *
	 * @param  $tableName
	 * @param  $id
	 * @param  $fieldName
	 * @return string
	 */
	protected function getFieldValue($tableName, $id, $fieldName) {
		$record = BackendUtility::getRecord($tableName, $id, $fieldName);

		if (!is_array($record)) {
			$this->fail('Record ' . $tableName . ':' . $id . ' not available');
		}

		return $record[$fieldName];
	}

	/**
	 * Gets instance of \TYPO3\CMS\Core\Database\RelationHandler.
	 *
	 * @return \TYPO3\CMS\Core\Database\RelationHandler
	 */
	protected function getLoadDbGroup() {
		$loadDbGroup = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\RelationHandler');

		return $loadDbGroup;
	}

	/**
	 * Gets an instance of \TYPO3\CMS\Core\DataHandling\DataHandler.
	 *
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function getTceMain() {
		$tceMain = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		return $tceMain;
	}

	/**
	 * Assert that no sys_log entries had been written.
	 *
	 * @return void
	 */
	protected function assertNoLogEntries() {
		$logEntries = $this->getLogEntries();

		if (count($logEntries) > $this->expectedLogEntries) {
			var_dump(array_values($logEntries));
			ob_flush();
			$this->fail('The sys_log table contains unexpected entries.');
		} elseif (count($logEntries) < $this->expectedLogEntries) {
			$this->fail('Expected count of sys_log entries no reached.');
		}
	}

	/**
	 * Asserts the correct order of elements.
	 *
	 * @param string $table
	 * @param string $field
	 * @param array $expectedOrderOfIds
	 * @param string $message
	 * @return void
	 */
	protected function assertSortingOrder($table, $field, $expectedOrderOfIds, $message) {
		$expectedOrderOfIdsCount = count($expectedOrderOfIds);
		$elements = $this->getAllRecords($table);

		for ($i = 0; $i < $expectedOrderOfIdsCount-1; $i++) {
			$this->assertLessThan(
				$elements[$expectedOrderOfIds[$i+1]][$field],
				$elements[$expectedOrderOfIds[$i]][$field],
				$message
			);
		}
	}

	/**
	 * Asserts reference index elements.
	 *
	 * @param array $assertions
	 * @param boolean $expected
	 */
	protected function assertReferenceIndex(array $assertions, $expected = TRUE) {
		$references = $this->getAllRecords('sys_refindex', 'hash');

		foreach ($assertions as $parent => $children) {
			foreach ($children as $child) {
				$parentItems = explode(':', $parent);
				$childItems = explode(':', $child);

				$assertion = array(
					'tablename' => $parentItems[0],
					'recuid' => $parentItems[1],
					'field' => $parentItems[2],
					'ref_table' => $childItems[0],
					'ref_uid' => $childItems[1],
				);

				$this->assertTrue(
					($expected === $this->executeAssertionOnElements($assertion, $references)),
					'Expected reference index element for ' . $parent . ' -> ' . $child
				);
			}
		}
	}

	/**
	 * @param string $parentTableName
	 * @param integer $parentId
	 * @param string $parentFieldName
	 * @param array $assertions
	 * @param string $mmTable
	 * @param boolean $expected
	 * @return void
	 */
	protected function assertChildren($parentTableName, $parentId, $parentFieldName, array $assertions, $mmTable = '', $expected = TRUE) {
		$tcaFieldConfiguration = $this->getTcaFieldConfiguration($parentTableName, $parentFieldName);

		$loadDbGroup = $this->getLoadDbGroup();
		$loadDbGroup->start(
			$this->getFieldValue($parentTableName, $parentId, $parentFieldName),
			$tcaFieldConfiguration['foreign_table'],
			$mmTable,
			$parentId,
			$parentTableName,
			$tcaFieldConfiguration
		);

		$elements = $this->getElementsByItemArray($loadDbGroup->itemArray);

		foreach ($assertions as $index => $assertion) {
			$this->assertTrue(
				($expected === $this->executeAssertionOnElements($assertion, $elements)),
				'Assertion #' . $index . ' failed'
			);
		}
	}

	/**
	 * Gets log entries from the sys_log
	 *
	 * @return array
	 */
	protected function getLogEntries() {
		return $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'sys_log', 'error IN (1,2)');
	}

	/**
	 * @param  array $assertion
	 * @param  array $elements
	 * @return boolean
	 */
	protected function executeAssertionOnElements(array $assertion, array $elements) {
		if (!empty($assertion['tableName'])) {
			$tableName = $assertion['tableName'];
			unset($assertion['tableName']);
			$elements = (array) $elements[$tableName];
		}

		foreach ($elements as $element) {
			$result = FALSE;

			foreach ($assertion as $field => $value) {
				if ($element[$field] == $value) {
					$result = TRUE;
				} else {
					$result = FALSE;
					break;
				}
			}

			if ($result === TRUE) {
				return TRUE;
			}
		}

		return FALSE;
	}

	/**
	 * @param mixed $element
	 * @return string
	 */
	protected function elementToString($element) {
		$result = preg_replace(
			'#\n+#',
			' ',
			var_export($element, TRUE)
		);

		return $result;
	}

	/**
	 * @return string
	 */
	protected function combine() {
		return implode(':', func_get_args());
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}
