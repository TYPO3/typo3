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
use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\ResponseContent;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractDataHandlerActionTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	const VALUE_BackendUserId = 1;

	/**
	 * @var string
	 */
	protected $scenarioDataSetDirectory;

	/**
	 * @var string
	 */
	protected $assertionDataSetDirectory;

	/**
	 * If this value is NULL, log entries are not considered.
	 * If it's an integer value, the number of log entries is asserted.
	 *
	 * @var NULL|int
	 */
	protected $expectedErrorLogEntries = 0;

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
		'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
		'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/extTables.php' => 'typo3conf/extTables.php',
	);

	/**
	 * @var array
	 */
	protected $recordIds = array();

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
		$this->assertErrorLogEntries();
		unset($this->actionService);
		unset($this->recordIds);
		parent::tearDown();
	}

	/**
	 * @return \TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService
	 */
	protected function getActionService() {
		return GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Tests\\Functional\\DataHandling\\Framework\\ActionService'
		);
	}

	/**
	 * @param string $dataSetName
	 */
	protected function importScenarioDataSet($dataSetName) {
		$fileName = rtrim($this->scenarioDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
		$fileName = GeneralUtility::getFileAbsFileName($fileName);

		$dataSet = DataSet::read($fileName);

		foreach ($dataSet->getTableNames() as $tableName) {
			foreach ($dataSet->getElements($tableName) as $element) {
				$this->getDatabaseConnection()->exec_INSERTquery(
					$tableName,
					$element
				);
				$sqlError = $this->getDatabaseConnection()->sql_error();
				if (!empty($sqlError)) {
					$this->fail('SQL Error for table "' . $tableName . '": ' . LF . $sqlError);
				}
			}
		}
	}

	protected function assertAssertionDataSet($dataSetName) {
		$fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
		$fileName = GeneralUtility::getFileAbsFileName($fileName);

		$dataSet = DataSet::read($fileName);
		$failMessages = array();

		foreach ($dataSet->getTableNames() as $tableName) {
			$hasUidField = ($dataSet->getIdIndex($tableName) !== NULL);
			$records = $this->getAllRecords($tableName, $hasUidField);
			foreach ($dataSet->getElements($tableName) as $assertion) {
				$result = $this->assertInRecords($assertion, $records);
				if ($result === FALSE) {
					if ($hasUidField && empty($records[$assertion['uid']])) {
						$failMessages[] = 'Record "' . $tableName . ':' . $assertion['uid'] . '" not found in database';
						continue;
					}
					$recordIdentifier = $tableName . ($hasUidField ? ':' . $assertion['uid'] : '');
					$additionalInformation = ($hasUidField ? $this->renderRecords($assertion, $records[$assertion['uid']]) : $this->arrayToString($assertion));
					$failMessages[] = 'Assertion in data-set failed for "' . $recordIdentifier . '":' . LF . $additionalInformation;
					// Unset failed asserted record
					if ($hasUidField) {
						unset($records[$assertion['uid']]);
					}
				} else {
					// Unset asserted record
					unset($records[$result]);
					// Increase assertion counter
					$this->assertTrue($result !== FALSE);
				}
			}
			if (!empty($records)) {
				foreach ($records as $record) {
					$recordIdentifier = $tableName . ':' . $record['uid'];
					$emptyAssertion = array_fill_keys($dataSet->getFields($tableName), '[none]');
					$reducedRecord = array_intersect_key($record, $emptyAssertion);
					$additionalInformation = ($hasUidField ? $this->renderRecords($emptyAssertion, $reducedRecord) : $this->arrayToString($reducedRecord));
					$failMessages[] = 'Not asserted record found for "' . $recordIdentifier . '":' . LF . $additionalInformation;
				}
			}
		}

		if (!empty($failMessages)) {
			$this->fail(implode(LF, $failMessages));
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
	 * Asserts correct number of warning and error log entries.
	 *
	 * @return void
	 */
	protected function assertErrorLogEntries() {
		if ($this->expectedErrorLogEntries === NULL) {
			return;
		}
		$errorLogEntries = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'sys_log', 'error IN (1,2)');
		$actualErrorLogEntries = count($errorLogEntries);
		if ($actualErrorLogEntries === $this->expectedErrorLogEntries) {
			$this->assertSame($this->expectedErrorLogEntries, $actualErrorLogEntries);
		} else {
			$failureMessage = 'Expected ' . $this->expectedErrorLogEntries . ' entries in sys_log, but got ' . $actualErrorLogEntries . LF;
			foreach ($errorLogEntries as $entry) {
				$entryData = unserialize($entry['log_data']);
				$entryMessage = vsprintf($entry['details'], $entryData);
				$failureMessage .= '* ' . $entryMessage . LF;
			}
			$this->fail($failureMessage);
		}
	}

	/**
	 * @param string $tableName
	 * @param bool $hasUidField
	 * @return array
	 */
	protected function getAllRecords($tableName, $hasUidField = FALSE) {
		$allRecords = array();

		$records = $this->getDatabaseConnection()->exec_SELECTgetRows(
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
		return 'array(' . PHP_EOL . '   ' . implode(', ' . PHP_EOL . '   ', $elements) . PHP_EOL . ')' . PHP_EOL;
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
		$linesFromXmlValues = array();
		$result = '';

		foreach ($differentFields as $differentField) {
			$columns['fields'][] = $differentField;
			$columns['assertion'][] = ($assertion[$differentField] === NULL ? 'NULL' : $assertion[$differentField]);
			$columns['record'][] = ($record[$differentField] === NULL ? 'NULL' : $record[$differentField]);
		}

		foreach ($columns as $columnIndex => $column) {
			$columnLength = NULL;
			foreach ($column as $value) {
				if (strpos($value, '<?xml') === 0) {
					$value = '[see diff]';
				}
				$valueLength = strlen($value);
				if (empty($columnLength) || $valueLength > $columnLength) {
					$columnLength = $valueLength;
				}
			}
			foreach ($column as $valueIndex => $value) {
				if (strpos($value, '<?xml') === 0) {
					if ($columnIndex === 'assertion') {
						try {
							$this->assertXmlStringEqualsXmlString((string)$value, (string)$record[$columns['fields'][$valueIndex]]);
						} catch(\PHPUnit_Framework_ExpectationFailedException $e) {
							$linesFromXmlValues[] = 'Diff for field "' . $columns['fields'][$valueIndex] . '":' . PHP_EOL . $e->getComparisonFailure()->getDiff();
						}
					}
					$value = '[see diff]';
				}
				$lines[$valueIndex][$columnIndex] = str_pad($value, $columnLength, ' ');
			}
		}

		foreach ($lines as $line) {
			$result .= implode('|', $line) . PHP_EOL;
		}

		foreach ($linesFromXmlValues as $lineFromXmlValues) {
			$result .= PHP_EOL . $lineFromXmlValues . PHP_EOL;
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
			} elseif (strpos($value, '<?xml') === 0) {
				try {
					$this->assertXmlStringEqualsXmlString((string)$value, (string)$record[$field]);
				} catch (\PHPUnit_Framework_ExpectationFailedException $e) {
					$differentFields[] = $field;
				}
			} elseif ($value === NULL && $record[$field] !== $value) {
				$differentFields[] = $field;
			} elseif ((string)$record[$field] !== (string)$value) {
				$differentFields[] = $field;
			}
		}

		return $differentFields;
	}

	/**
	 * @param ResponseContent $responseContent
	 * @param string $structureRecordIdentifier
	 * @param string $structureFieldName
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string|array $values
	 */
	protected function assertResponseContentStructureHasRecords(ResponseContent $responseContent, $structureRecordIdentifier, $structureFieldName, $tableName, $fieldName, $values) {
		$nonMatchingVariants = array();

		foreach ($responseContent->findStructures($structureRecordIdentifier, $structureFieldName) as $path => $structure) {
			$nonMatchingValues = $this->getNonMatchingValuesFrontendResponseRecords($structure, $tableName, $fieldName, $values);

			if (empty($nonMatchingValues)) {
				// Increase assertion counter
				$this->assertEmpty($nonMatchingValues);
				return;
			}

			$nonMatchingVariants[$path] = $nonMatchingValues;
		}

		$nonMatchingMessage = '';
		foreach ($nonMatchingVariants as $path => $nonMatchingValues) {
			$nonMatchingMessage .= '* ' . $path . ': ' . implode(', ', $nonMatchingValues);
		}

		$this->fail('Could not assert all values for "' . $tableName . '.' . $fieldName . '"' . LF . $nonMatchingMessage);
	}

	/**
	 * @param ResponseContent $responseContent
	 * @param string $structureRecordIdentifier
	 * @param string $structureFieldName
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string|array $values
	 */
	protected function assertResponseContentStructureDoesNotHaveRecords(ResponseContent $responseContent, $structureRecordIdentifier, $structureFieldName, $tableName, $fieldName, $values) {
		if (is_string($values)) {
			$values = array($values);
		}

		$matchingVariants = array();

		foreach ($responseContent->findStructures($structureRecordIdentifier, $structureFieldName) as $path => $structure) {
			$nonMatchingValues = $this->getNonMatchingValuesFrontendResponseRecords($structure, $tableName, $fieldName, $values);
			$matchingValues = array_diff($values, $nonMatchingValues);

			if (!empty($matchingValues)) {
				$matchingVariants[$path] = $matchingValues;
			}
		}

		if (empty($matchingVariants)) {
			// Increase assertion counter
			$this->assertEmpty($matchingVariants);
			return;
		}

		$matchingMessage = '';
		foreach ($matchingVariants as $path => $matchingValues) {
			$matchingMessage .= '* ' . $path . ': ' . implode(', ', $matchingValues);
		}

		$this->fail('Could not assert not having values for "' . $tableName . '.' . $fieldName . '"' . LF . $matchingMessage);
	}

	/**
	 * @param ResponseContent $responseContent
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string|array $values
	 */
	protected function assertResponseContentHasRecords(ResponseContent $responseContent, $tableName, $fieldName, $values) {
		$nonMatchingValues = $this->getNonMatchingValuesFrontendResponseRecords($responseContent->getRecords(), $tableName, $fieldName, $values);

		if (!empty($nonMatchingValues)) {
			$this->fail('Could not assert all values for "' . $tableName . '.' . $fieldName . '": ' . implode(', ', $nonMatchingValues));
		}

		// Increase assertion counter
		$this->assertEmpty($nonMatchingValues);
	}

	/**
	 * @param ResponseContent $responseContent
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string|array $values
	 */
	protected function assertResponseContentDoesNotHaveRecords(ResponseContent $responseContent, $tableName, $fieldName, $values) {
		if (is_string($values)) {
			$values = array($values);
		}

		$nonMatchingValues = $this->getNonMatchingValuesFrontendResponseRecords($responseContent->getRecords(), $tableName, $fieldName, $values);
		$matchingValues = array_diff($values, $nonMatchingValues);

		if (!empty($matchingValues)) {
			$this->fail('Could not assert not having values for "' . $tableName . '.' . $fieldName . '": ' . implode(', ', $matchingValues));
		}

		// Increase assertion counter
		$this->assertTrue(TRUE);
	}

	/**
	 * @param string|array $data
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string|array $values
	 * @return array
	 */
	protected function getNonMatchingValuesFrontendResponseRecords($data, $tableName, $fieldName, $values) {
		if (empty($data) || !is_array($data)) {
			$this->fail('Frontend Response data does not have any records');
		}

		if (is_string($values)) {
			$values = array($values);
		}

		foreach ($data as $recordIdentifier => $recordData) {
			if (strpos($recordIdentifier, $tableName . ':') !== 0) {
				continue;
			}

			if (($foundValueIndex = array_search($recordData[$fieldName], $values)) !== FALSE) {
				unset($values[$foundValueIndex]);
			}
		}

		return $values;
	}

}
