<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
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
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\DataSet;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractDataHandlerActionTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	const VALUE_BackendUserId = 1;

	/**
	 * @var string
	 */
	protected $dataSetDirectory;

	/**
	 * @var array
	 */
	protected $testExtensionsToLoad = array(
		'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
		// 'typo3conf/ext/datahandler',
	);

	/**
	 * @var array
	 */
	protected $pathsToLinkInTestInstance = array(
		'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/extTables.php' => 'typo3conf/extTables.php',
	);

	/**
	 * @var \TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService
	 */
	protected $actionService;

	/**
	 * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected $backendUser;

	public function setUp() {
		parent::setUp();

		$this->backendUser = $this->setUpBackendUserFromFixture(self::VALUE_BackendUserId);
		// By default make tests on live workspace
		$this->backendUser->workspace = 0;

		$this->actionService = $this->getActionService();
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
	}

	public function tearDown() {
		unset($this->actionService);
	}

	/**
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function getDataHandler() {
		$dataHandler = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		return $dataHandler;
	}

	/**
	 * @return \TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService
	 */
	protected function getActionService() {
		return GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Tests\\Functional\\DataHandling\\Framework\\ActionService',
			$this->getDataHandler()
		);
	}

	/**
	 * @param string $dataSetName
	 */
	protected function importScenarioDataSet($dataSetName) {
		$fileName = rtrim($this->dataSetDirectory, '/') . '/Scenario/' . $dataSetName . '.csv';
		$fileName = GeneralUtility::getFileAbsFileName($fileName);

		$dataSet = DataSet::read($fileName);

		foreach ($dataSet->getTableNames() as $tableName) {
			foreach ($dataSet->getElements($tableName) as $element) {
				$this->getDatabase()->exec_INSERTquery(
					$tableName,
					$element
				);
				$sqlError = $this->getDatabase()->sql_error();
				if (!empty($sqlError)) {
					$this->fail('SQL Error for table "' . $tableName . '": ' . LF . $sqlError);
				}
			}
		}
	}

	protected function assertAssertionDataSet($dataSetName) {
		$fileName = rtrim($this->dataSetDirectory, '/') . '/Assertion/' . $dataSetName . '.csv';
		$fileName = GeneralUtility::getFileAbsFileName($fileName);

		$dataSet = DataSet::read($fileName);

		foreach ($dataSet->getTableNames() as $tableName) {
			$hasUidField = ($dataSet->getIdIndex($tableName) !== NULL);
			$records = $this->getAllRecords($tableName, $hasUidField);
			foreach ($dataSet->getElements($tableName) as $assertion) {
				$result = $this->assertInRecords($assertion, $records);
				if ($result === FALSE) {
					if ($hasUidField && empty($records[$assertion['uid']])) {
						$this->fail('Record "' . $tableName . ':' . $assertion['uid'] . '" not found in database');
					}
					$recordIdentifier = $tableName . ($hasUidField ? ':' . $assertion['uid'] : '');
					$additionalInformation = ($hasUidField ? $this->renderRecords($assertion, $records[$assertion['uid']]) : $this->arrayToString($assertion));
					$this->fail('Assertion in data-set failed for "' . $recordIdentifier . '":' . LF . $additionalInformation);
				} else {
					// Unset asserted record
					unset($records[$result]);
					// Increase assertion counter
					$this->assertTrue($result !== FALSE);
				}
			}
		}
	}

	/**
	 * @param array $assertion
	 * @param array $records
	 * @return bool|int|string
	 */
	protected function assertInRecords(array $assertion, array $records) {
		foreach ($records as $index => $record) {
			$differentFields = $this->getDifferentFields($assertion, $record);

			if (empty($differentFields)) {
				return $index;
			}
		}

		return FALSE;
	}

	/**
	 * @param string $tableName
	 * @param bool $hasUidField
	 * @return array
	 */
	protected function getAllRecords($tableName, $hasUidField = FALSE) {
		$allRecords = array();

		$records = $this->getDatabase()->exec_SELECTgetRows(
			'*',
			$tableName,
			'1=1',
			'',
			'',
			'',
			($hasUidField ? 'uid' : '')
		);

		if (!empty($records)) {
			$allRecords = $records;
		}

		return $allRecords;
	}

	/**
	 * @param array $array
	 * @return string
	 */
	protected function arrayToString(array $array) {
		$elements = array();
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$value = $this->arrayToString($value);
			}
			$elements[] = "'" . $key . "' => '" . $value . "'";
		}
		return 'array(' . implode(', ', $elements) . ')';
	}

	/**
	 * @param array $assertion
	 * @param array $record
	 * @return string
	 */
	protected function renderRecords(array $assertion, array $record) {
		$differentFields = $this->getDifferentFields($assertion, $record);
		$columns = array(
			'fields' => array('Fields'),
			'assertion' => array('Assertion'),
			'record' => array('Record'),
		);
		$lines = array();
		$result = '';

		foreach ($differentFields as $differentField) {
			$columns['fields'][] = $differentField;
			$columns['assertion'][] = ($assertion[$differentField] === NULL ? 'NULL' : $assertion[$differentField]);
			$columns['record'][] = ($record[$differentField] === NULL ? 'NULL' : $record[$differentField]);
		}

		foreach ($columns as $columnIndex => $column) {
			$columnLength = NULL;
			foreach ($column as $value) {
				$valueLength = strlen($value);
				if (empty($columnLength) || $valueLength > $columnLength) {
					$columnLength = $valueLength;
				}
			}
			foreach ($column as $valueIndex => $value) {
				$lines[$valueIndex][$columnIndex] = str_pad($value, $columnLength, ' ');
			}
		}

		foreach ($lines as $line) {
			$result .= implode('|', $line) . PHP_EOL;
		}

		return $result;
	}

	/**
	 * @param array $assertion
	 * @param array $record
	 * @return array
	 */
	protected function getDifferentFields(array $assertion, array $record) {
		$differentFields = array();

		foreach ($assertion as $field => $value) {
			if (strpos($value, '\\*') === 0) {
				continue;
			} elseif ((string)$record[$field] !== (string)$value) {
				$differentFields[] = $field;
			}
		}

		return $differentFields;
	}

}
