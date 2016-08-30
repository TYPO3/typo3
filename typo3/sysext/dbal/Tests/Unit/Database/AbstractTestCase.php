<?php
namespace TYPO3\CMS\Dbal\Tests\Unit\Database;

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

require_once __DIR__ . '/../../../../adodb/adodb/adodb.inc.php';
require_once __DIR__ . '/../../../../adodb/adodb/drivers/adodb-mssql.inc.php';
require_once __DIR__ . '/../../../../adodb/adodb/drivers/adodb-oci8.inc.php';
require_once __DIR__ . '/../../../../adodb/adodb/drivers/adodb-postgres7.inc.php';

/**
 * Base test case for dbal database tests.
 */
abstract class AbstractTestCase extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * Prepare a DatabaseConnection subject.
     * Used by driver specific test cases.
     *
     * @param string $driver Driver to use like "mssql", "oci8" and "postgres7"
     * @param array $configuration Dbal configuration array
     * @return \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected function prepareSubject($driver, array $configuration)
    {
        /** @var \TYPO3\CMS\Dbal\Database\DatabaseConnection|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\DatabaseConnection::class, ['getFieldInfoCache'], [], '', false);

        $subject->conf = $configuration;

        // Disable caching
        $mockCacheFrontend = $this->getMock(\TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class, [], [], '', false);
        $subject->expects($this->any())->method('getFieldInfoCache')->will($this->returnValue($mockCacheFrontend));

        // Inject SqlParser - Its logic is tested with the tests, too.
        $sqlParser = $this->getAccessibleMock(\TYPO3\CMS\Dbal\Database\SqlParser::class, ['dummy'], [], '', false);
        $sqlParser->_set('databaseConnection', $subject);
        $sqlParser->_set('sqlCompiler', GeneralUtility::makeInstance(\TYPO3\CMS\Dbal\Database\SqlCompilers\Adodb::class, $subject));
        $sqlParser->_set('nativeSqlCompiler', GeneralUtility::makeInstance(\TYPO3\CMS\Dbal\Database\SqlCompilers\Mysql::class, $subject));
        $subject->SQLparser = $sqlParser;

        // Mock away schema migration service from install tool
        $installerSqlMock = $this->getMock(\TYPO3\CMS\Install\Service\SqlSchemaMigrationService::class, ['getFieldDefinitions_fileContent'], [], '', false);
        $installerSqlMock->expects($this->any())->method('getFieldDefinitions_fileContent')->will($this->returnValue([]));
        $subject->_set('installerSql', $installerSqlMock);

        $subject->initialize();

        // Fake a working connection
        $handlerKey = '_DEFAULT';
        $subject->lastHandlerKey = $handlerKey;
        $adodbDriverClass = '\ADODB_' . $driver;
        $subject->handlerInstance[$handlerKey] = new $adodbDriverClass();
        $subject->handlerInstance[$handlerKey]->DataDictionary = NewDataDictionary($subject->handlerInstance[$handlerKey]);
        $subject->handlerInstance[$handlerKey]->_connectionID = rand(1, 1000);

        return $subject;
    }

    /**
     * Clean a parsed SQL query for easier comparison with expected SQL.
     *
     * @param mixed $sql
     * @return mixed (string or array)
     */
    protected function cleanSql($sql)
    {
        if (!is_string($sql)) {
            return $sql;
        }
        $sql = str_replace("\n", ' ', $sql);
        $sql = preg_replace('/\\s+/', ' ', $sql);
        return trim($sql);
    }
}
