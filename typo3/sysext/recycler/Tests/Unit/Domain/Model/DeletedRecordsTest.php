<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Recycler\Tests\Unit\Domain\Model;

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

use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DeletedRecordsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function recordsOfMultipleTablesAreSortedByPid()
    {
        $deletedRowsData = [
            'pages' => [
                ['uid' => 1, 'pid' => 1],
                ['uid' => 2, 'pid' => 2],
                ['uid' => 3, 'pid' => 4],
                ['uid' => 4, 'pid' => 7],
            ],
            'sys_template' => [
                ['uid' => 1, 'pid' => 9],
                ['uid' => 2, 'pid' => 10],
                ['uid' => 3, 'pid' => 1],
            ],
            'tt_content' => [
                ['uid' => 1, 'pid' => 7],
                ['uid' => 2, 'pid' => 1],
            ]
        ];

        $expectedRows = [
            'pages' => [
                ['uid' => 1, 'pid' => 1],
                ['uid' => 2, 'pid' => 2],
                ['uid' => 4, 'pid' => 7],
                ['uid' => 3, 'pid' => 4],
            ],
            'sys_template' => [
                ['uid' => 3, 'pid' => 1],
                ['uid' => 2, 'pid' => 10],
                ['uid' => 1, 'pid' => 9],
            ],
            'tt_content' => [
                ['uid' => 2, 'pid' => 1],
                ['uid' => 1, 'pid' => 7],
            ]
        ];

        /** @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|DeletedRecords $subject */
        $subject = $this->getAccessibleMock(DeletedRecords::class, ['dummy']);
        $subject->_set('deletedRows', $deletedRowsData);
        $subject->_call('sortDeletedRowsByPidList', [1, 2, 7, 4, 10, 9]);
        static::assertEquals($expectedRows, $subject->getDeletedRows());
    }
}
