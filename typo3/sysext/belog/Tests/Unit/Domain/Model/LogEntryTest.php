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

namespace TYPO3\CMS\Belog\Tests\Unit\Domain\Model;

use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LogEntryTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Belog\Domain\Model\LogEntry
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new LogEntry();
    }

    /**
     * @test
     */
    public function getLogDataInitiallyReturnsEmptyArray()
    {
        self::assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForEmptyStringLogDataReturnsEmptyArray()
    {
        $this->subject->setLogData('');
        self::assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForGarbageStringLogDataReturnsEmptyArray()
    {
        $this->subject->setLogData('foo bar');
        self::assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForSerializedArrayReturnsThatArray()
    {
        $logData = ['foo', 'bar'];
        $this->subject->setLogData(serialize($logData));
        self::assertSame($logData, $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForSerializedObjectReturnsEmptyArray()
    {
        $this->subject->setLogData(new \stdClass());
        self::assertSame([], $this->subject->getLogData());
    }
}
