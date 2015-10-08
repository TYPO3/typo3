<?php
namespace TYPO3\CMS\Core\Database;

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

/**
 * A helper class for handling PDO databases
 * Backport of FLOW3 class PdoHelper, last synced version: 3528
 */
class PdoHelper
{
    /**
     * Pumps the SQL into the database. Use for DDL only.
     *
     * Important: key definitions with length specifiers (needed for MySQL) must
     * be given as "field"(xyz) - no space between double quote and parenthesis -
     * so they can be removed automatically.
     *
     * @param PDO $databaseHandle
     * @param string $pdoDriver
     * @param string $pathAndFilename
     * @return void
     */
    public static function importSql(\PDO $databaseHandle, $pdoDriver, $pathAndFilename)
    {
        $sql = file($pathAndFilename, FILE_IGNORE_NEW_LINES & FILE_SKIP_EMPTY_LINES);
        // Remove MySQL style key length delimiters (yuck!) if we are not setting up a MySQL db
        if (substr($pdoDriver, 0, 5) !== 'mysql') {
            $sql = preg_replace('/"\\([0-9]+\\)/', '"', $sql);
        }
        $statement = '';
        foreach ($sql as $line) {
            $statement .= ' ' . trim($line);
            if (substr($statement, -1) === ';') {
                $databaseHandle->exec($statement);
                $statement = '';
            }
        }
    }
}
