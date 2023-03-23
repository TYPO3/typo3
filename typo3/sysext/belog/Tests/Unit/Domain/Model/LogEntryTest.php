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

namespace TYPO3\CMS\Belog\Tests\Unit\Domain\Model;

use TYPO3\CMS\Belog\Domain\Model\LogEntry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LogEntryTest extends UnitTestCase
{
    protected LogEntry $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new LogEntry();
    }

    /**
     * @test
     */
    public function getLogDataInitiallyReturnsEmptyArray(): void
    {
        self::assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForEmptyStringLogDataReturnsEmptyArray(): void
    {
        $this->subject->setLogData('');
        self::assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForGarbageStringLogDataReturnsEmptyArray(): void
    {
        $this->subject->setLogData('foo bar');
        self::assertSame([], $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForSerializedArrayReturnsThatArray(): void
    {
        $logData = ['foo', 'bar'];
        $this->subject->setLogData(serialize($logData));
        self::assertSame($logData, $this->subject->getLogData());
    }

    /**
     * @test
     */
    public function getLogDataForSerializedObjectReturnsEmptyArray(): void
    {
        $this->subject->setLogData(serialize(new \stdClass()));
        self::assertSame([], $this->subject->getLogData());
    }

    public static function getErrorIconReturnsCorrespondingClassDataProvider(): array
    {
        return [
            'empty' => [
                0,
                'empty-empty',
            ],
            'warning' => [
                1,
                'status-dialog-warning',
            ],
            'error 2' => [
                2,
                'status-dialog-error',
            ],
            'error 3' => [
                3,
                'status-dialog-error',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getErrorIconReturnsCorrespondingClassDataProvider
     */
    public function getErrorIconReturnsCorrespondingClass(int $error, string $expectedClass): void
    {
        $this->subject->setError($error);
        self::assertSame($expectedClass, $this->subject->getErrorIconClass());
    }
}
