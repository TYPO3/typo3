<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\Log\Writer;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class DatabaseWriterTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function writeLogInsertsLogRecordWithGivenProperties()
    {
        $logRecordData = [
            'request_id' => '5862c0e7838ac',
            'time_micro' => 1469740000.0,
            'component' => 'aComponent',
            'level' => LogLevel::normalizeLevel(LogLevel::DEBUG),
            'message' => 'aMessage',
            'data' => ''
        ];
        $logRecord = new LogRecord(
            $logRecordData['component'],
            LogLevel::DEBUG,
            $logRecordData['message'],
            [],
            $logRecordData['request_id']
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

        self::assertEquals($logRecordData, $rowInDatabase);
    }
}
