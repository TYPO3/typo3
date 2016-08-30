<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\IRRE;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generic test helpers.
 *
 */
abstract class AbstractTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    const VALUE_LanguageId = 2;

    const TABLE_Pages = 'pages';

    const COMMAND_Copy = 'copy';
    const COMMAND_Localize = 'localize';
    const COMMAND_Delete = 'delete';

    const PROPERTY_LocalizeReferencesAtParentLocalization = 'localizeReferencesAtParentLocalization';
    const BEHAVIOUR_LocalizeChildrenAtParentLocalization = 'localizeChildrenAtParentLocalization';
    const BEHAVIOUR_LocalizationMode = 'localizationMode';

    protected $testExtensionsToLoad = ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/irre_tutorial'];

    /**
     * @var int
     */
    private $expectedLogEntries = 0;

    /**
     * Sets up this test case.
     *
     * @return void
     */
    protected function setUp()
    {
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
    protected function tearDown()
    {
        $this->assertNoLogEntries();

        $this->expectedLogEntries = 0;

        parent::tearDown();
    }

    /**
     * Sets the number of expected log entries.
     *
     * @param int $count
     * @return void
     */
    protected function setExpectedLogEntries($count)
    {
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
    protected function getElementStructureForCommands($command, $value, array $tables)
    {
        $commandStructure = [];

        foreach ($tables as $tableName => $idList) {
            $ids = GeneralUtility::trimExplode(',', $idList, true);
            foreach ($ids as $id) {
                $commandStructure[$tableName][$id] = [
                    $command => $value
                ];
            }
        }

        return $commandStructure;
    }

    /**
     * Simulates executing commands by using DataHandler.
     *
     * @param array $elements The cmdmap to be delivered to DataHandler
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function simulateCommandByStructure(array $elements)
    {
        $tceMain = $this->getTceMain();
        $tceMain->start([], $elements);
        $tceMain->process_cmdmap();

        return $tceMain;
    }

    /**
     * @param string $command
     * @param mixed $value
     * @param array $tables Table names with list of ids to be edited
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function simulateCommand($command, $value, array $tables)
    {
        return $this->simulateCommandByStructure(
            $this->getElementStructureForCommands($command, $value, $tables)
        );
    }

    /**
     * Gets the last log entry.
     *
     * @return array
     */
    protected function getLastLogEntryMessage()
    {
        $message = '';

        $logEntries = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'sys_log', 'error IN (1,2)', '', '', 1);

        if (is_array($logEntries) && !empty($logEntries)) {
            $message = $logEntries[0]['details'];
        }

        return $message;
    }

    /**
     * @param array $itemArray
     * @return array
     */
    protected function getElementsByItemArray(array $itemArray)
    {
        $elements = [];

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
    protected function getAllRecords($table, $indexField = 'uid')
    {
        return $this->getDatabaseConnection()->exec_SELECTgetRows('*', $table, '1=1', '', '', '', $indexField);
    }

    /**
     * Gets the TCE configuration of a field.
     *
     * @param  $tableName
     * @param  $fieldName
     * @return array
     */
    protected function getTcaFieldConfiguration($tableName, $fieldName)
    {
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
    protected function setTcaFieldConfiguration($tableName, $fieldName, $propertyName, $value)
    {
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
    protected function setTcaFieldConfigurationBehaviour($tableName, $fieldName, $behaviourName, $value)
    {
        if (isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'])) {
            if (!isset($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['behaviour'])) {
                $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config']['behaviour'] = [];
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
    protected function getFieldValue($tableName, $id, $fieldName)
    {
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
    protected function getLoadDbGroup()
    {
        $loadDbGroup = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\RelationHandler::class);

        return $loadDbGroup;
    }

    /**
     * Gets an instance of \TYPO3\CMS\Core\DataHandling\DataHandler.
     *
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getTceMain()
    {
        $tceMain = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        return $tceMain;
    }

    /**
     * Assert that no sys_log entries had been written.
     *
     * @return void
     */
    protected function assertNoLogEntries()
    {
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
    protected function assertSortingOrder($table, $field, $expectedOrderOfIds, $message)
    {
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
     * @param bool $expected
     */
    protected function assertReferenceIndex(array $assertions, $expected = true)
    {
        $references = $this->getAllRecords('sys_refindex', 'hash');

        foreach ($assertions as $parent => $children) {
            foreach ($children as $child) {
                $parentItems = explode(':', $parent);
                $childItems = explode(':', $child);

                $assertion = [
                    'tablename' => $parentItems[0],
                    'recuid' => $parentItems[1],
                    'field' => $parentItems[2],
                    'ref_table' => $childItems[0],
                    'ref_uid' => $childItems[1],
                ];

                $this->assertTrue(
                    ($expected === $this->executeAssertionOnElements($assertion, $references)),
                    'Expected reference index element for ' . $parent . ' -> ' . $child
                );
            }
        }
    }

    /**
     * @param string $parentTableName
     * @param int $parentId
     * @param string $parentFieldName
     * @param array $assertions
     * @param string $mmTable
     * @param bool $expected
     * @return void
     */
    protected function assertChildren($parentTableName, $parentId, $parentFieldName, array $assertions, $mmTable = '', $expected = true)
    {
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
    protected function getLogEntries()
    {
        return $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'sys_log', 'error IN (1,2)');
    }

    /**
     * @param array $assertion
     * @param array $elements
     * @return bool
     */
    protected function executeAssertionOnElements(array $assertion, array $elements)
    {
        if (!empty($assertion['tableName'])) {
            $tableName = $assertion['tableName'];
            unset($assertion['tableName']);
            $elements = (array)$elements[$tableName];
        }

        foreach ($elements as $element) {
            $result = false;

            foreach ($assertion as $field => $value) {
                if ($element[$field] == $value) {
                    $result = true;
                } else {
                    $result = false;
                    break;
                }
            }

            if ($result === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $element
     * @return string
     */
    protected function elementToString($element)
    {
        $result = preg_replace(
            '#\n+#',
            ' ',
            var_export($element, true)
        );

        return $result;
    }

    /**
     * @return string
     */
    protected function combine()
    {
        return implode(':', func_get_args());
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
