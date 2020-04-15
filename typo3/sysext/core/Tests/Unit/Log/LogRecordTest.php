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

namespace TYPO3\CMS\Core\Tests\Unit\Log;

use Psr\Log\InvalidArgumentException;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LogRecordTest extends UnitTestCase
{
    /**
     * Returns a LogRecord
     *
     * @param array $parameters Parameters to set in LogRecord constructor.
     * @return LogRecord
     */
    protected function getRecord(array $parameters = []): LogRecord
    {
        $record = new LogRecord(
            $parameters['component'] ?? 'test.core.log',
            $parameters['level'] ?? LogLevel::DEBUG,
            $parameters['message'] ?? 'test message',
            $parameters['data'] ?? []
        );
        return $record;
    }

    /**
     * @test
     */
    public function constructorSetsCorrectComponent()
    {
        $component = 'test.core.log';
        $record = $this->getRecord(['component' => $component]);
        self::assertEquals($component, $record->getComponent());
    }

    /**
     * @test
     */
    public function constructorSetsCorrectLogLevel()
    {
        $logLevel = LogLevel::CRITICAL;
        $record = $this->getRecord(['level' => $logLevel]);
        self::assertEquals($logLevel, $record->getLevel());
    }

    /**
     * @test
     */
    public function constructorSetsCorrectMessage()
    {
        $logMessage = 'test message';
        $record = $this->getRecord(['message' => $logMessage]);
        self::assertEquals($logMessage, $record->getMessage());
    }

    /**
     * @test
     */
    public function constructorSetsCorrectData()
    {
        $dataArray = [
            'foo' => 'bar'
        ];
        $record = $this->getRecord(['data' => $dataArray]);
        self::assertEquals($dataArray, $record->getData());
    }

    /**
     * @test
     */
    public function setComponentSetsComponent()
    {
        $record = $this->getRecord();
        $component = 'testcomponent';
        self::assertEquals($component, $record->setComponent($component)->getComponent());
    }

    /**
     * @test
     */
    public function setLevelSetsLevel()
    {
        $record = $this->getRecord();
        $level = LogLevel::EMERGENCY;
        self::assertEquals($level, $record->setLevel($level)->getLevel());
    }

    /**
     * @test
     */
    public function setLevelValidatesLevel()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1550247164);

        $record = $this->getRecord();
        $record->setLevel('foo');
    }

    /**
     * @test
     */
    public function setMessageSetsMessage()
    {
        $record = $this->getRecord();
        $message = 'testmessage';
        self::assertEquals($message, $record->setMessage($message)->getMessage());
    }

    /**
     * @test
     */
    public function setCreatedSetsCreated()
    {
        $record = $this->getRecord();
        $created = 123.45;
        self::assertEquals($created, $record->setCreated($created)->getCreated());
    }

    /**
     * @test
     */
    public function setRequestIdSetsRequestId()
    {
        $record = $this->getRecord();
        $requestId = 'testrequestid';
        self::assertEquals($requestId, $record->setRequestId($requestId)->getRequestId());
    }

    /**
     * @test
     */
    public function toArrayReturnsCorrectValues()
    {
        $component = 'test.core.log';
        $level = LogLevel::DEBUG;
        $message = 'test message';
        $data = ['foo' => 'bar'];
        /** @var $record LogRecord */
        $record = new LogRecord($component, $level, $message, $data);
        $recordArray = $record->toArray();
        self::assertEquals($component, $recordArray['component']);
        self::assertEquals($level, $recordArray['level']);
        self::assertEquals($message, $recordArray['message']);
        self::assertEquals($data, $recordArray['data']);
    }

    /**
     * @test
     */
    public function toStringIncludesDataAsJson()
    {
        $dataArray = ['foo' => 'bar'];
        $record = $this->getRecord(['data' => $dataArray]);
        self::assertStringContainsString(json_encode($dataArray), (string)$record);
    }

    /**
     * @test
     */
    public function toStringIncludesExceptionDataAsJson()
    {
        $dataArray = ['exception' => new \Exception('foo', 1476049451)];
        $record = $this->getRecord(['data' => $dataArray]);
        self::assertStringContainsString('Exception: foo', (string)$record);
    }
}
