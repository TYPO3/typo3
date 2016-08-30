<?php
namespace TYPO3\CMS\Belog\Tests\Unit\Domain\Model;

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
 * Test case
 *
 */
class LogEntryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Belog\Domain\Model\LogEntry
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new \TYPO3\CMS\Belog\Domain\Model\LogEntry();
    }

    /**
     * @test
     */
    public function getLogDataInitiallyReturnsEmptyArray()
    {
        $this->assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForEmptyStringLogDataReturnsEmptyArray()
    {
        $this->subject->setLogData('');
        $this->assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForGarbageStringLogDataReturnsEmptyArray()
    {
        $this->subject->setLogData('foo bar');
        $this->assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForSerializedArrayReturnsThatArray()
    {
        $logData = ['foo', 'bar'];
        $this->subject->setLogData(serialize($logData));
        $this->assertSame($logData, $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForSerializedObjectReturnsEmptyArray()
    {
        $this->subject->setLogData(new \stdClass());
        $this->assertSame([], $this->subject->getLogData());
    }
}
