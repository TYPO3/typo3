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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseUniqueUidNewRowTest extends UnitTestCase
{
    /**
     * @var DatabaseUniqueUidNewRow
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DatabaseUniqueUidNewRow();
    }

    /**
     * @test
     */
    public function addDataReturnSameDataIfCommandIsEdit()
    {
        $input = [
            'command' => 'edit',
            'databaseRow' => [
                'uid' => 42,
            ],
        ];
        self::assertSame($input, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsGivenUidIfAlreadySet()
    {
        $input = [
            'command' => 'new',
            'databaseRow' => [
                'uid' => 'NEW1234',
            ],
        ];
        $expected = $input;
        self::assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfUidIsAlreadySetButDoesNotStartWithNewKeyword()
    {
        $input = [
            'command' => 'new',
            'databaseRow' => [
                'uid' => 'FOO',
            ],
        ];
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1437991120);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsUniqueId()
    {
        $input = [
            'command' => 'new',
            'databaseRow' => [],
        ];
        $result = $this->subject->addData($input);
        $result = substr($result['databaseRow']['uid'], 0, 3);
        self::assertSame('NEW', $result);
    }
}
