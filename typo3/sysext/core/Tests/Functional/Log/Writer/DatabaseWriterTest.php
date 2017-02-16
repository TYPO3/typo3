<?php
namespace TYPO3\CMS\Core\Tests\Functional\Log\Writer;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;

/**
 * Test case
 */
class DatabaseWriterTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @test
     */
    public function writeLogInsertsLogRecordWithGivenProperties()
    {
        $logRecordData = [
            'request_id' => Bootstrap::getInstance()->getRequestId(),
            'time_micro' => 1469740000.0,
            'component' => 'aComponent',
            'level' => LogLevel::DEBUG,
            'message' => 'aMessage',
            'data' => ''
        ];
        $logRecord = new LogRecord(
            $logRecordData['component'],
            $logRecordData['level'],
            $logRecordData['message'],
            []
        );
        $logRecord->setCreated($logRecordData['time_micro']);

        (new DatabaseWriter())->writeLog($logRecord);

        $rowInDatabase = (new ConnectionPool())->getConnectionForTable('sys_log')
            ->select(
                array_keys($logRecordData),
                'sys_log',
                ['request_id' => $logRecordData['request_id']]
            )
            ->fetch();

        $this->assertEquals($logRecordData, $rowInDatabase);
    }
}
