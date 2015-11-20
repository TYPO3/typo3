<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUniqueUidNewRow;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class DatabaseUniqueUidNewRowTest extends UnitTestCase
{
    /**
     * @var DatabaseUniqueUidNewRow
     */
    protected $subject;

    protected function setUp()
    {
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
        $this->assertSame($input, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfUidIsAlreadySet()
    {
        $input = [
            'command' => 'new',
            'databaseRow' => [
                'uid' => 42,
            ],
        ];

        $this->setExpectedException(\InvalidArgumentException::class, $this->anything(), 1437991120);

        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsUniqeId()
    {
        $input = [
            'command' => 'new',
            'databaseRow' => [],
        ];
        $result = $this->subject->addData($input);
        $result = substr($result['databaseRow']['uid'], 0, 3);
        $this->assertSame('NEW', $result);
    }
}
