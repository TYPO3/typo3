<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Database\Schema;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Database\Event\AlterTableDefinitionStatementsEvent;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Helper methods to handle raw SQL input and transform it into individual statements
 * for further processing.
 *
 * @internal
 */
class SqlReader
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var PackageManager
     */
    protected $packageManager;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param PackageManager $packageManager
     * @throws \InvalidArgumentException
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, PackageManager $packageManager)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->packageManager = $packageManager;
    }

    /**
     * Cycle through all loaded extensions and get full table definitions as concatenated string
     *
     * @param bool $withStatic TRUE if sql from ext_tables_static+adt.sql should be loaded, too.
     * @return string Concatenated SQL of loaded extensions ext_tables.sql
     */
    public function getTablesDefinitionString(bool $withStatic = false): string
    {
        $sqlString = [];

        // Find all ext_tables.sql of loaded extensions
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packagePath = $package->getPackagePath();
            if (@file_exists($packagePath . 'ext_tables.sql')) {
                $sqlString[] = (string)file_get_contents($packagePath . 'ext_tables.sql');
            }
            if ($withStatic && @file_exists($packagePath . 'ext_tables_static+adt.sql')) {
                $sqlString[] = (string)file_get_contents($packagePath . 'ext_tables_static+adt.sql');
            }
        }

        /** @var AlterTableDefinitionStatementsEvent $event */
        $event = $this->eventDispatcher->dispatch(new AlterTableDefinitionStatementsEvent($sqlString));
        $sqlString = $event->getSqlData();

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
        $isInMultilineComment = false;
        foreach (explode(LF, $dumpContent) as $lineContent) {
            $lineContent = trim($lineContent);

            // Skip empty lines and comments
            if ($lineContent === '' || $lineContent[0] === '#' || strpos($lineContent, '--') === 0 ||
                strpos($lineContent, '/*') === 0 || substr($lineContent, -2) === '*/' || $isInMultilineComment
            ) {
                // skip c style multiline comments
                if (strpos($lineContent, '/*') === 0 && substr($lineContent, -2) !== '*/') {
                    $isInMultilineComment = true;
                }
                if (substr($lineContent, -2) === '*/') {
                    $isInMultilineComment = false;
                }
                continue;
            }

            $statementArray[$statementArrayPointer] = ($statementArray[$statementArrayPointer] ?? '') . $lineContent;

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
}
