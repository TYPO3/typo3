<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Database\Schema;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Install\Service\SqlExpectedSchemaService;

/**
 * Helper methods to handle raw SQL input and transform it into individual statements
 * for further processing.
 *
 * @internal
 */
class SqlReader
{
    /**
     * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected $signalSlotDispatcher;

    /**
     * @param Dispatcher $signalSlotDispatcher
     * @throws \InvalidArgumentException
     */
    public function __construct(Dispatcher $signalSlotDispatcher = null)
    {
        $this->signalSlotDispatcher = $signalSlotDispatcher ?: GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * Cycle through all loaded extensions and get full table definitions as concatenated string
     *
     * @param bool $withStatic TRUE if sql from ext_tables_static+adt.sql should be loaded, too.
     * @return string Concatenated SQL of loaded extensions ext_tables.sql
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     */
    public function getTablesDefinitionString(bool $withStatic = false): string
    {
        $sqlString = [];

        // Find all ext_tables.sql of loaded extensions
        foreach ((array)$GLOBALS['TYPO3_LOADED_EXT'] as $extensionConfiguration) {
            if (!is_array($extensionConfiguration) && !$extensionConfiguration instanceof \ArrayAccess) {
                continue;
            }
            if ($extensionConfiguration['ext_tables.sql']) {
                $sqlString[] = file_get_contents($extensionConfiguration['ext_tables.sql']);
            }
            if ($withStatic && $extensionConfiguration['ext_tables_static+adt.sql']) {
                $sqlString[] = file_get_contents($extensionConfiguration['ext_tables_static+adt.sql']);
            }
        }

        $sqlString = $this->emitTablesDefinitionIsBeingBuiltSignal($sqlString);

        return implode(LF . LF, $sqlString);
    }

    /**
     * Returns an array where every entry is a single SQL-statement.
     * Input must be formatted like an ordinary MySQL dump file. Every statements needs to be terminated by a ';'
     * and there may only be one statement (or partial statement) per line.
     *
     * @param string $dumpContent The SQL dump content.
     * @param string $queryRegex Regex to select which statements to return.
     * @return array Array of SQL statements
     */
    public function getStatementArray(string $dumpContent, string $queryRegex = null): array
    {
        $statementArray = [];
        $statementArrayPointer = 0;
        foreach (explode(LF, $dumpContent) as $lineContent) {
            $lineContent = trim($lineContent);

            // Skip empty lines and comments
            if ($lineContent === '' || $lineContent[0] === '#' || strpos($lineContent, '--') === 0) {
                continue;
            }

            $statementArray[$statementArrayPointer] .= $lineContent;

            if (substr($lineContent, -1) === ';') {
                $statement = trim($statementArray[$statementArrayPointer]);
                if (!$statement || ($queryRegex && !preg_match('/' . $queryRegex . '/i', $statement))) {
                    unset($statementArray[$statementArrayPointer]);
                }
                $statementArrayPointer++;
            } else {
                $statementArray[$statementArrayPointer] .= ' ';
            }
        }

        return $statementArray;
    }

    /**
     * Extract only INSERT statements from SQL dump
     *
     * @param string $dumpContent
     * @return array
     */
    public function getInsertStatementArray(string $dumpContent): array
    {
        return $this->getStatementArray($dumpContent, '^INSERT');
    }

    /**
     * Extract only CREATE TABLE statements from SQL dump
     *
     * @param string $dumpContent
     * @return array
     */
    public function getCreateTableStatementArray(string $dumpContent): array
    {
        return $this->getStatementArray($dumpContent, '^CREATE TABLE');
    }

    /**
     * Emits a signal to manipulate the tables definitions
     *
     * @param array $sqlString
     * @return array
     * @throws \TYPO3\CMS\Core\Database\Schema\Exception\UnexpectedSignalReturnValueTypeException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     */
    protected function emitTablesDefinitionIsBeingBuiltSignal(array $sqlString): array
    {
        // Using the old class name from the install tool here to keep backwards compatibility.
        $signalReturn = $this->signalSlotDispatcher->dispatch(
            SqlExpectedSchemaService::class,
            'tablesDefinitionIsBeingBuilt',
            [$sqlString]
        );

        // This is important to support old associated returns
        $signalReturn = array_values($signalReturn);
        $sqlString = $signalReturn[0];
        if (!is_array($sqlString)) {
            throw new Exception\UnexpectedSignalReturnValueTypeException(
                sprintf(
                    'The signal %s of class %s returned a value of type %s, but array was expected.',
                    'tablesDefinitionIsBeingBuilt',
                    __CLASS__,
                    gettype($sqlString)
                ),
                1382351456
            );
        }

        return $sqlString;
    }
}
