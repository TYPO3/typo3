<?php
namespace TYPO3\CMS\Install\Service;

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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Verify TYPO3 DB table structure. Mainly used in install tool
 * compare wizard and extension manager.
 */
class SqlSchemaMigrationService
{
    /**
     * @constant Maximum field width of MySQL
     */
    const MYSQL_MAXIMUM_FIELD_WIDTH = 64;

    /**
     * @var string Prefix of deleted tables
     */
    protected $deletedPrefixKey = 'zzz_deleted_';

    /**
     * @var array Caching output "SHOW CHARACTER SET"
     */
    protected $character_sets = [];

    /**
     * Set prefix of deleted tables
     *
     * @param string $prefix Prefix string
     */
    public function setDeletedPrefixKey($prefix)
    {
        $this->deletedPrefixKey = $prefix;
    }

    /**
     * Get prefix of deleted tables
     *
     * @return string
     */
    public function getDeletedPrefixKey()
    {
        return $this->deletedPrefixKey;
    }

    /**
     * Reads the field definitions for the input SQL-file string
     *
     * @param string $fileContent Should be a string read from an SQL-file made with 'mysqldump [database_name] -d'
     * @return array Array with information about table.
     */
    public function getFieldDefinitions_fileContent($fileContent)
    {
        $lines = GeneralUtility::trimExplode(LF, $fileContent, true);
        $table = '';
        $total = [];
        foreach ($lines as $value) {
            if ($value[0] === '#') {
                // Ignore comments
                continue;
            }
            if ($table === '') {
                $parts = GeneralUtility::trimExplode(' ', $value, true);
                if (strtoupper($parts[0]) === 'CREATE' && strtoupper($parts[1]) === 'TABLE') {
                    $table = str_replace('`', '', $parts[2]);
                    // tablenames are always lowercase on windows!
                    if (TYPO3_OS === 'WIN') {
                        $table = strtolower($table);
                    }
                }
            } else {
                if ($value[0] === ')' && substr($value, -1) === ';') {
                    $ttype = [];
                    if (preg_match('/(ENGINE|TYPE)[ ]*=[ ]*([a-zA-Z]*)/', $value, $ttype)) {
                        $total[$table]['extra']['ENGINE'] = $ttype[2];
                    }
                    // Otherwise, just do nothing: If table engine is not defined, just accept the system default.
                    // Set the collation, if specified
                    if (preg_match('/(COLLATE)[ ]*=[ ]*([a-zA-z0-9_-]+)/', $value, $tcollation)) {
                        $total[$table]['extra']['COLLATE'] = $tcollation[2];
                    } else {
                        // Otherwise, get the CHARACTER SET and try to find the default collation for it as returned by "SHOW CHARACTER SET" query (for details, see http://dev.mysql.com/doc/refman/5.1/en/charset-table.html)
                        if (preg_match('/(CHARSET|CHARACTER SET)[ ]*=[ ]*([a-zA-z0-9_-]+)/', $value, $tcharset)) {
                            // Note: Keywords "DEFAULT CHARSET" and "CHARSET" are the same, so "DEFAULT" can just be ignored
                            $charset = $tcharset[2];
                        } else {
                            $charset = 'utf8';
                        }
                        $total[$table]['extra']['COLLATE'] = $this->getCollationForCharset($charset);
                    }
                    // Remove table marker and start looking for the next "CREATE TABLE" statement
                    $table = '';
                } else {
                    // Strip trailing commas
                    $lineV = preg_replace('/,$/', '', $value);
                    $lineV = str_replace('`', '', $lineV);
                    // Reduce multiple blanks and tabs except newline
                    $lineV = preg_replace('/\h+/', ' ', $lineV);
                    $parts = explode(' ', $lineV, 2);
                    // Field definition
                    if (!preg_match('/(PRIMARY|UNIQUE|FULLTEXT|SPATIAL|INDEX|KEY)/', $parts[0])) {
                        // Make sure there is no default value when auto_increment is set
                        if (stristr($parts[1], 'auto_increment')) {
                            $parts[1] = preg_replace('/ default \'0\'/i', '', $parts[1]);
                        }
                        // "default" is always lower-case
                        if (stristr($parts[1], ' DEFAULT ')) {
                            $parts[1] = str_ireplace(' DEFAULT ', ' default ', $parts[1]);
                        }
                        // Change order of "default" and "NULL" statements
                        $parts[1] = preg_replace('/(.*) (default .*) (NOT NULL)/', '$1 $3 $2', $parts[1]);
                        $parts[1] = preg_replace('/(.*) (default .*) (NULL)/', '$1 $3 $2', $parts[1]);
                        $key = $parts[0];
                        $total[$table]['fields'][$key] = $parts[1];
                    } else {
                        // Key definition
                        $search = ['/UNIQUE (INDEX|KEY)/', '/FULLTEXT (INDEX|KEY)/', '/SPATIAL (INDEX|KEY)/', '/INDEX/'];
                        $replace = ['UNIQUE', 'FULLTEXT', 'SPATIAL', 'KEY'];
                        $lineV = preg_replace($search, $replace, $lineV);
                        if (preg_match('/PRIMARY|UNIQUE|FULLTEXT|SPATIAL/', $parts[0])) {
                            $parts[1] = preg_replace('/^(KEY|INDEX) /', '', $parts[1]);
                        }
                        $newParts = explode(' ', $parts[1], 2);
                        $key = $parts[0] === 'PRIMARY' ? $parts[0] : $newParts[0];
                        $total[$table]['keys'][$key] = $lineV;
                        // This is a protection against doing something stupid: Only allow clearing of cache_* and index_* tables.
                        if (preg_match('/^(cache|index)_/', $table)) {
                            // Suggest to truncate (clear) this table
                            $total[$table]['extra']['CLEAR'] = 1;
                        }
                    }
                }
            }
        }
        return $total;
    }

    /**
     * Look up the default collation for specified character set based on "SHOW CHARACTER SET" output
     *
     * @param string $charset Character set
     * @return string Corresponding default collation
     */
    public function getCollationForCharset($charset)
    {
        // Load character sets, if not cached already
        if (empty($this->character_sets)) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
            $statement = $connection->query('SHOW CHARACTER SET');
            $this->character_sets = [];
            while ($row = $statement->fetch()) {
                $this->character_sets[$row['Charset']] = $row;
            }
        }
        $collation = '';
        if (isset($this->character_sets[$charset]['Default collation'])) {
            $collation = $this->character_sets[$charset]['Default collation'];
        }
        return $collation;
    }

    /**
     * Reads the field definitions for the current database
     *
     * @return array Array with information about table.
     */
    public function getFieldDefinitions_database()
    {
        $total = [];
        $tempKeys = [];
        $tempKeysPrefix = [];
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        $statement = $connection->query('SHOW TABLE STATUS FROM `' . $connection->getDatabase() . '`');
        $tables = [];
        while ($theTable = $statement->fetch()) {
            $tables[$theTable['Name']] = $theTable;
        }
        foreach ($tables as $tableName => $tableStatus) {
            // Fields
            $statement = $connection->query('SHOW FULL COLUMNS FROM `' . $tableName . '`');
            $fieldInformation = [];
            while ($fieldRow = $statement->fetch()) {
                $fieldInformation[$fieldRow['Field']] = $fieldRow;
            }
            foreach ($fieldInformation as $fN => $fieldRow) {
                $total[$tableName]['fields'][$fN] = $this->assembleFieldDefinition($fieldRow);
            }
            // Keys
            $statement = $connection->query('SHOW KEYS FROM `' . $tableName . '`');
            $keyInformation = [];
            while ($keyRow = $statement->fetch()) {
                $keyInformation[] = $keyRow;
            }
            foreach ($keyInformation as $keyRow) {
                $keyName = $keyRow['Key_name'];
                $colName = $keyRow['Column_name'];
                if ($keyRow['Sub_part'] && $keyRow['Index_type'] !== 'SPATIAL') {
                    $colName .= '(' . $keyRow['Sub_part'] . ')';
                }
                $tempKeys[$tableName][$keyName][$keyRow['Seq_in_index']] = $colName;
                if ($keyName === 'PRIMARY') {
                    $prefix = 'PRIMARY KEY';
                } else {
                    if ($keyRow['Index_type'] === 'FULLTEXT') {
                        $prefix = 'FULLTEXT';
                    } elseif ($keyRow['Index_type'] === 'SPATIAL') {
                        $prefix = 'SPATIAL';
                    } elseif ($keyRow['Non_unique']) {
                        $prefix = 'KEY';
                    } else {
                        $prefix = 'UNIQUE';
                    }
                    $prefix .= ' ' . $keyName;
                }
                $tempKeysPrefix[$tableName][$keyName] = $prefix;
            }
            // Table status (storage engine, collaction, etc.)
            if (is_array($tableStatus)) {
                $tableExtraFields = [
                    'Engine' => 'ENGINE',
                    'Collation' => 'COLLATE'
                ];
                foreach ($tableExtraFields as $mysqlKey => $internalKey) {
                    if (isset($tableStatus[$mysqlKey])) {
                        $total[$tableName]['extra'][$internalKey] = $tableStatus[$mysqlKey];
                    }
                }
            }
        }
        // Compile key information:
        if (!empty($tempKeys)) {
            foreach ($tempKeys as $table => $keyInf) {
                foreach ($keyInf as $kName => $index) {
                    ksort($index);
                    $total[$table]['keys'][$kName] = $tempKeysPrefix[$table][$kName] . ' (' . implode(',', $index) . ')';
                }
            }
        }
        return $total;
    }

    /**
     * Compares two arrays with field information and returns information about fields that are MISSING and fields that have CHANGED.
     * FDsrc and FDcomp can be switched if you want the list of stuff to remove rather than update.
     *
     * @param array $FDsrc Field definitions, source (from getFieldDefinitions_fileContent())
     * @param array $FDcomp Field definitions, comparison. (from getFieldDefinitions_database())
     * @param string $onlyTableList Table names (in list) which is the ONLY one observed.
     * @param bool $ignoreNotNullWhenComparing If set, this function ignores NOT NULL statements of the SQL file field definition when comparing current field definition from database with field definition from SQL file. This way, NOT NULL statements will be executed when the field is initially created, but the SQL parser will never complain about missing NOT NULL statements afterwards.
     * @return array Returns an array with 1) all elements from $FDsrc that is not in $FDcomp (in key 'extra') and 2) all elements from $FDsrc that is different from the ones in $FDcomp
     */
    public function getDatabaseExtra($FDsrc, $FDcomp, $onlyTableList = '', $ignoreNotNullWhenComparing = false)
    {
        $extraArr = [];
        $diffArr = [];
        if (is_array($FDsrc)) {
            foreach ($FDsrc as $table => $info) {
                if ($onlyTableList === '' || GeneralUtility::inList($onlyTableList, $table)) {
                    if (!isset($FDcomp[$table])) {
                        // If the table was not in the FDcomp-array, the result array is loaded with that table.
                        $extraArr[$table] = $info;
                        $extraArr[$table]['whole_table'] = 1;
                    } else {
                        $keyTypes = explode(',', 'extra,fields,keys');
                        foreach ($keyTypes as $theKey) {
                            if (is_array($info[$theKey])) {
                                foreach ($info[$theKey] as $fieldN => $fieldC) {
                                    $fieldN = str_replace('`', '', $fieldN);
                                    if ($fieldN === 'COLLATE') {
                                        // @todo collation support is currently disabled (needs more testing)
                                        continue;
                                    }
                                    if (!isset($FDcomp[$table][$theKey][$fieldN])) {
                                        $extraArr[$table][$theKey][$fieldN] = $fieldC;
                                    } else {
                                        $fieldC = trim($fieldC);

                                        // Lowercase the field type to surround false-positive schema changes to be
                                        // reported just because of different caseing of characters
                                        // The regex does just trigger for the first word followed by parentheses
                                        // that contain a length. It does not trigger for e.g. "PRIMARY KEY" because
                                        // "PRIMARY KEY" is being returned from the DB in upper case.
                                        $fieldC = preg_replace_callback(
                                            '/^([a-zA-Z0-9]+)(\([^)]*\)\s.*)/',
                                            function ($matches) {
                                                return strtolower($matches[1]) . $matches[2];
                                            },
                                            $fieldC
                                        );

                                        if ($ignoreNotNullWhenComparing) {
                                            $fieldC = str_replace(' NOT NULL', '', $fieldC);
                                            $FDcomp[$table][$theKey][$fieldN] = str_replace(' NOT NULL', '', $FDcomp[$table][$theKey][$fieldN]);
                                        }
                                        if ($fieldC !== $FDcomp[$table][$theKey][$fieldN]) {
                                            $diffArr[$table][$theKey][$fieldN] = $fieldC;
                                            $diffArr_cur[$table][$theKey][$fieldN] = $FDcomp[$table][$theKey][$fieldN];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $output = [
            'extra' => $extraArr,
            'diff' => $diffArr,
            'diff_currentValues' => $diffArr_cur
        ];
        return $output;
    }

    /**
     * Returns an array with SQL-statements that is needed to update according to the diff-array
     *
     * @param array $diffArr Array with differences of current and needed DB settings. (from getDatabaseExtra())
     * @param string $keyList List of fields in diff array to take notice of.
     * @return array Array of SQL statements (organized in keys depending on type)
     */
    public function getUpdateSuggestions($diffArr, $keyList = 'extra,diff')
    {
        $statements = [];
        $deletedPrefixKey = $this->deletedPrefixKey;
        $deletedPrefixLength = strlen($deletedPrefixKey);
        $remove = 0;
        if ($keyList === 'remove') {
            $remove = 1;
            $keyList = 'extra';
        }
        $keyList = explode(',', $keyList);
        foreach ($keyList as $theKey) {
            if (is_array($diffArr[$theKey])) {
                foreach ($diffArr[$theKey] as $table => $info) {
                    $whole_table = [];
                    if (isset($info['keys']) && is_array($info['keys'])) {
                        foreach ($info['keys'] as $fN => $fV) {
                            if (!$info['whole_table'] && $theKey === 'extra' && $remove) {
                                $statement = 'ALTER TABLE ' . $table . ($fN === 'PRIMARY' ? ' DROP PRIMARY KEY' : ' DROP KEY ' . $fN) . ';';
                                $statements['drop'][md5($statement)] = $statement;
                            }
                        }
                    }
                    if (is_array($info['fields'])) {
                        foreach ($info['fields'] as $fN => $fV) {
                            if ($info['whole_table']) {
                                $whole_table[] = $fN . ' ' . $fV;
                            } else {
                                // Special case to work around MySQL problems when adding auto_increment fields:
                                if (stristr($fV, 'auto_increment')) {
                                    // The field can only be set "auto_increment" if there exists a PRIMARY key of that field already.
                                    // The check does not look up which field is primary but just assumes it must be the field with the auto_increment value...
                                    if (isset($info['keys']['PRIMARY'])) {
                                        // Combine adding the field and the primary key into a single statement
                                        $fV .= ', ADD PRIMARY KEY (' . $fN . ')';
                                        unset($info['keys']['PRIMARY']);
                                    } else {
                                        // In the next step, attempt to clear the table once again (2 = force)
                                        $info['extra']['CLEAR'] = 2;
                                    }
                                }
                                if ($theKey === 'extra') {
                                    if ($remove) {
                                        if (substr($fN, 0, $deletedPrefixLength) !== $deletedPrefixKey) {
                                            // we've to make sure we don't exceed the maximal length
                                            $prefixedFieldName = $deletedPrefixKey . substr($fN, ($deletedPrefixLength - self::MYSQL_MAXIMUM_FIELD_WIDTH));
                                            $statement = 'ALTER TABLE ' . $table . ' CHANGE ' . $fN . ' ' . $prefixedFieldName . ' ' . $fV . ';';
                                            $statements['change'][md5($statement)] = $statement;
                                        } else {
                                            $statement = 'ALTER TABLE ' . $table . ' DROP ' . $fN . ';';
                                            $statements['drop'][md5($statement)] = $statement;
                                        }
                                    } else {
                                        $statement = 'ALTER TABLE ' . $table . ' ADD ' . $fN . ' ' . $fV . ';';
                                        $statements['add'][md5($statement)] = $statement;
                                    }
                                } elseif ($theKey === 'diff') {
                                    $statement = 'ALTER TABLE ' . $table . ' CHANGE ' . $fN . ' ' . $fN . ' ' . $fV . ';';
                                    $statements['change'][md5($statement)] = $statement;
                                    $statements['change_currentValue'][md5($statement)] = $diffArr['diff_currentValues'][$table]['fields'][$fN];
                                }
                            }
                        }
                    }
                    if (is_array($info['keys'])) {
                        foreach ($info['keys'] as $fN => $fV) {
                            if ($info['whole_table']) {
                                $whole_table[] = $fV;
                            } else {
                                if ($theKey === 'extra') {
                                    if (!$remove) {
                                        $statement = 'ALTER TABLE ' . $table . ' ADD ' . $fV . ';';
                                        $statements['add'][md5($statement)] = $statement;
                                    }
                                } elseif ($theKey === 'diff') {
                                    $statement = 'ALTER TABLE ' . $table . ($fN === 'PRIMARY' ? ' DROP PRIMARY KEY' : ' DROP KEY ' . $fN) . ';';
                                    $statements['change'][md5($statement)] = $statement;
                                    $statement = 'ALTER TABLE ' . $table . ' ADD ' . $fV . ';';
                                    $statements['change'][md5($statement)] = $statement;
                                }
                            }
                        }
                    }
                    if (is_array($info['extra'])) {
                        $extras = [];
                        $extras_currentValue = [];
                        $clear_table = false;
                        foreach ($info['extra'] as $fN => $fV) {
                            // Only consider statements which are missing in the database but don't remove existing properties
                            if (!$remove) {
                                if (!$info['whole_table']) {
                                    // If the whole table is created at once, we take care of this later by imploding all elements of $info['extra']
                                    if ($fN === 'CLEAR') {
                                        // Truncate table must happen later, not now
                                        // Valid values for CLEAR: 1=only clear if keys are missing, 2=clear anyway (force)
                                        if (!empty($info['keys']) || $fV == 2) {
                                            $clear_table = true;
                                        }
                                        continue;
                                    }
                                    $extras[] = $fN . '=' . $fV;
                                    $extras_currentValue[] = $fN . '=' . $diffArr['diff_currentValues'][$table]['extra'][$fN];
                                }
                            }
                        }
                        if ($clear_table) {
                            $statement = 'TRUNCATE TABLE ' . $table . ';';
                            $statements['clear_table'][md5($statement)] = $statement;
                        }
                        if (!empty($extras)) {
                            $statement = 'ALTER TABLE ' . $table . ' ' . implode(' ', $extras) . ';';
                            $statements['change'][md5($statement)] = $statement;
                            $statements['change_currentValue'][md5($statement)] = implode(' ', $extras_currentValue);
                        }
                    }
                    if ($info['whole_table']) {
                        if ($remove) {
                            if (substr($table, 0, $deletedPrefixLength) !== $deletedPrefixKey) {
                                // we've to make sure we don't exceed the maximal length
                                $prefixedTableName = $deletedPrefixKey . substr($table, ($deletedPrefixLength - self::MYSQL_MAXIMUM_FIELD_WIDTH));
                                $statement = 'ALTER TABLE ' . $table . ' RENAME ' . $prefixedTableName . ';';
                                $statements['change_table'][md5($statement)] = $statement;
                            } else {
                                $statement = 'DROP TABLE ' . $table . ';';
                                $statements['drop_table'][md5($statement)] = $statement;
                            }
                            // Count
                            $count = GeneralUtility::makeInstance(ConnectionPool::class)
                                ->getConnectionByName('Default')
                                ->count('*', $table, []);
                            $statements['tables_count'][md5($statement)] = $count ? 'Records in table: ' . $count : '';
                        } else {
                            $statement = 'CREATE TABLE ' . $table . ' (
' . implode(',
', $whole_table) . '
)';
                            if ($info['extra']) {
                                foreach ($info['extra'] as $k => $v) {
                                    if ($k === 'COLLATE' || $k === 'CLEAR') {
                                        // Skip these special statements.
                                        // @todo collation support is currently disabled (needs more testing)
                                        continue;
                                    }
                                    // Add extra attributes like ENGINE, CHARSET, etc.
                                    $statement .= ' ' . $k . '=' . $v;
                                }
                            }
                            $statement .= ';';
                            $statements['create_table'][md5($statement)] = $statement;
                        }
                    }
                }
            }
        }

        return $statements;
    }

    /**
     * Converts a result row with field information into the SQL field definition string
     *
     * @param array $row MySQL result row
     * @return string Field definition
     */
    public function assembleFieldDefinition($row)
    {
        $field = [$row['Type']];
        if ($row['Null'] === 'NO') {
            $field[] = 'NOT NULL';
        }
        if (!strstr($row['Type'], 'blob') && !strstr($row['Type'], 'text')) {
            // Add a default value if the field is not auto-incremented (these fields never have a default definition)
            if (!stristr($row['Extra'], 'auto_increment')) {
                if ($row['Default'] === null) {
                    $field[] = 'default NULL';
                } else {
                    $field[] = 'default \'' . addslashes($row['Default']) . '\'';
                }
            }
        }
        if ($row['Extra']) {
            $field[] = $row['Extra'];
        }
        if (trim($row['Comment']) !== '') {
            $field[] = "COMMENT '" . $row['Comment'] . "'";
        }
        return implode(' ', $field);
    }

    /**
     * Returns an array where every entry is a single SQL-statement. Input must be formatted like an ordinary MySQL-dump files.
     *
     * @param string $sqlcode The SQL-file content. Provided that 1) every query in the input is ended with ';' and that a line in the file contains only one query or a part of a query.
     * @param bool $removeNonSQL If set, non-SQL content (like comments and blank lines) is not included in the final output
     * @param string $query_regex Regex to filter SQL lines to include
     * @return array Array of SQL statements
     */
    public function getStatementArray($sqlcode, $removeNonSQL = false, $query_regex = '')
    {
        $sqlcodeArr = explode(LF, $sqlcode);
        // Based on the assumption that the sql-dump has
        $statementArray = [];
        $statementArrayPointer = 0;
        foreach ($sqlcodeArr as $line => $lineContent) {
            $lineContent = trim($lineContent);
            $is_set = 0;
            // Auto_increment fields cannot have a default value!
            if (stristr($lineContent, 'auto_increment')) {
                $lineContent = preg_replace('/ default \'0\'/i', '', $lineContent);
            }
            if (!$removeNonSQL || $lineContent !== '' && $lineContent[0] !== '#' && substr($lineContent, 0, 2) !== '--') {
                // '--' is seen as mysqldump comments from server version 3.23.49
                $statementArray[$statementArrayPointer] .= $lineContent;
                $is_set = 1;
            }
            if (substr($lineContent, -1) === ';') {
                if (isset($statementArray[$statementArrayPointer])) {
                    if (!trim($statementArray[$statementArrayPointer]) || $query_regex && !preg_match(('/' . $query_regex . '/i'), trim($statementArray[$statementArrayPointer]))) {
                        unset($statementArray[$statementArrayPointer]);
                    }
                }
                $statementArrayPointer++;
            } elseif ($is_set) {
                $statementArray[$statementArrayPointer] .= LF;
            }
        }
        return $statementArray;
    }

    /**
     * Returns tables to create and how many records in each
     *
     * @param array $statements Array of SQL statements to analyse.
     * @param bool $insertCountFlag If set, will count number of INSERT INTO statements following that table definition
     * @return array Array with table definitions in index 0 and count in index 1
     */
    public function getCreateTables($statements, $insertCountFlag = false)
    {
        $crTables = [];
        $insertCount = [];
        foreach ($statements as $line => $lineContent) {
            $reg = [];
            if (preg_match('/^create[[:space:]]*table[[:space:]]*[`]?([[:alnum:]_]*)[`]?/i', substr($lineContent, 0, 100), $reg)) {
                $table = trim($reg[1]);
                if ($table) {
                    // Table names are always lowercase on Windows!
                    if (TYPO3_OS === 'WIN') {
                        $table = strtolower($table);
                    }
                    $sqlLines = explode(LF, $lineContent);
                    foreach ($sqlLines as $k => $v) {
                        if (stristr($v, 'auto_increment')) {
                            $sqlLines[$k] = preg_replace('/ default \'0\'/i', '', $v);
                        }
                    }
                    $lineContent = implode(LF, $sqlLines);
                    $crTables[$table] = $lineContent;
                }
            } elseif ($insertCountFlag && preg_match('/^insert[[:space:]]*into[[:space:]]*[`]?([[:alnum:]_]*)[`]?/i', substr($lineContent, 0, 100), $reg)) {
                $nTable = trim($reg[1]);
                $insertCount[$nTable]++;
            }
        }
        return [$crTables, $insertCount];
    }

    /**
     * Extracts all insert statements from $statement array where content is inserted into $table
     *
     * @param array $statements Array of SQL statements
     * @param string $table Table name
     * @return array Array of INSERT INTO statements where table match $table
     */
    public function getTableInsertStatements($statements, $table)
    {
        $outStatements = [];
        foreach ($statements as $line => $lineContent) {
            $reg = [];
            if (preg_match('/^insert[[:space:]]*into[[:space:]]*[`]?([[:alnum:]_]*)[`]?/i', substr($lineContent, 0, 100), $reg)) {
                $nTable = trim($reg[1]);
                if ($nTable && $table === $nTable) {
                    $outStatements[] = $lineContent;
                }
            }
        }
        return $outStatements;
    }

    /**
     * Performs the queries passed from the input array.
     *
     * @param array $arr Array of SQL queries to execute.
     * @param array $keyArr Array with keys that must match keys in $arr. Only where a key in this array is set and TRUE will the query be executed (meant to be passed from a form checkbox)
     * @return mixed Array with error message from database if any occurred. Otherwise TRUE if everything was executed successfully.
     */
    public function performUpdateQueries($arr, $keyArr)
    {
        $result = [];
        if (is_array($arr)) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
            foreach ($arr as $key => $string) {
                if (isset($keyArr[$key]) && $keyArr[$key]) {
                    try {
                        $connection->query($string);
                    } catch (DBALException $e) {
                        $result[$key] = $e->getMessage();
                    }
                }
            }
        }
        if (!empty($result)) {
            return $result;
        }
        return true;
    }

    /**
     * Returns list of tables in the database
     *
     * @return array List of tables.
     * @see \TYPO3\CMS\Core\Database\DatabaseConnection::admin_get_tables()
     */
    public function getListOfTables()
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionByName('Default');
        $statement = $connection->query('SHOW TABLE STATUS FROM `' . $connection->getDatabase() . '`');
        $tables = [];
        while ($theTable = $statement->fetch()) {
            $tables[$theTable['Name']] = $theTable;
        }
        foreach ($tables as $key => &$value) {
            $value = $key;
        }
        unset($value);
        return $tables;
    }
}
