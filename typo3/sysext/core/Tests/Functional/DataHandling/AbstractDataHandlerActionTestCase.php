<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling;

/*
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

use TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\DataSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Functional test for the DataHandler
 */
abstract class AbstractDataHandlerActionTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
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
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial',
    ];

    /**
     * @var array
     */
    protected $pathsToLinkInTestInstance = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Frontend/AdditionalConfiguration.php' => 'typo3conf/AdditionalConfiguration.php',
    ];

    /**
     * @var array
     */
    protected $recordIds = [];

    /**
     * @var \TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService
     */
    protected $actionService;

    /**
     * @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected $backendUser;

    protected function setUp()
    {
        parent::setUp();

        $this->backendUser = $this->setUpBackendUserFromFixture(self::VALUE_BackendUserId);
        // By default make tests on live workspace
        $this->backendUser->workspace = 0;

        $this->actionService = $this->getActionService();
        \TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
    }

    protected function tearDown()
    {
        $this->assertErrorLogEntries();
        unset($this->actionService);
        unset($this->recordIds);
        parent::tearDown();
    }

    /**
     * @return \TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService
     */
    protected function getActionService()
    {
        return GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework\ActionService::class
        );
    }

    /**
     * @param string $dataSetName
     */
    protected function importScenarioDataSet($dataSetName)
    {
        $fileName = rtrim($this->scenarioDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);

        $dataSet = DataSet::read($fileName, true);

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

    protected function assertAssertionDataSet($dataSetName)
    {
        $fileName = rtrim($this->assertionDataSetDirectory, '/') . '/' . $dataSetName . '.csv';
        $fileName = GeneralUtility::getFileAbsFileName($fileName);

        $dataSet = DataSet::read($fileName);
        $failMessages = [];

        foreach ($dataSet->getTableNames() as $tableName) {
            $hasUidField = ($dataSet->getIdIndex($tableName) !== null);
            $records = $this->getAllRecords($tableName, $hasUidField);
            foreach ($dataSet->getElements($tableName) as $assertion) {
                $result = $this->assertInRecords($assertion, $records);
                if ($result === false) {
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
                    $this->assertTrue($result !== false);
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
    protected function assertInRecords(array $assertion, array $records)
    {
        foreach ($records as $index => $record) {
            $differentFields = $this->getDifferentFields($assertion, $record);

            if (empty($differentFields)) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Asserts correct number of warning and error log entries.
     *
     * @return void
     */
    protected function assertErrorLogEntries()
    {
        if ($this->expectedErrorLogEntries === null) {
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
    protected function getAllRecords($tableName, $hasUidField = false)
    {
        $allRecords = [];

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
    protected function arrayToString(array $array)
    {
        $elements = [];
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
    protected function renderRecords(array $assertion, array $record)
    {
        $differentFields = $this->getDifferentFields($assertion, $record);
        $columns = [
            'fields' => ['Fields'],
            'assertion' => ['Assertion'],
            'record' => ['Record'],
        ];
        $lines = [];
        $linesFromXmlValues = [];
        $result = '';

        foreach ($differentFields as $differentField) {
            $columns['fields'][] = $differentField;
            $columns['assertion'][] = ($assertion[$differentField] === null ? 'NULL' : $assertion[$differentField]);
            $columns['record'][] = ($record[$differentField] === null ? 'NULL' : $record[$differentField]);
        }

        foreach ($columns as $columnIndex => $column) {
            $columnLength = null;
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
                        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
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
    protected function getDifferentFields(array $assertion, array $record)
    {
        $differentFields = [];

        foreach ($assertion as $field => $value) {
            if (strpos($value, '\\*') === 0) {
                continue;
            } elseif (strpos($value, '<?xml') === 0) {
                try {
                    $this->assertXmlStringEqualsXmlString((string)$value, (string)$record[$field]);
                } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
                    $differentFields[] = $field;
                }
            } elseif ($value === null && $record[$field] !== $value) {
                $differentFields[] = $field;
            } elseif ((string)$record[$field] !== (string)$value) {
                $differentFields[] = $field;
            }
        }

        return $differentFields;
    }

    /**
     * @return \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\HasRecordConstraint
     */
    protected function getRequestSectionHasRecordConstraint()
    {
        return new \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\HasRecordConstraint();
    }

    /**
     * @return \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint
     */
    protected function getRequestSectionDoesNotHaveRecordConstraint()
    {
        return new \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\DoesNotHaveRecordConstraint();
    }

    /**
     * @return \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint
     */
    protected function getRequestSectionStructureHasRecordConstraint()
    {
        return new \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\StructureHasRecordConstraint();
    }

    /**
     * @return \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint
     */
    protected function getRequestSectionStructureDoesNotHaveRecordConstraint()
    {
        return new \TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection\StructureDoesNotHaveRecordConstraint();
    }
}
