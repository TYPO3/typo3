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

namespace TYPO3\CMS\Core\Tests\Unit\Database;

use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for \TYPO3\CMS\Core\Database\RelationHandler
 */
class RelationHandlerTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Database\RelationHandler
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder(RelationHandler::class)
            ->setMethods(['purgeVersionedIds', 'purgeLiveVersionedIds'])
            ->getMock();
    }

    /**
     * @test
     */
    public function purgeItemArrayReturnsFalseIfVersioningForTableIsDisabled()
    {
        $GLOBALS['TCA']['sys_category']['ctrl']['versioningWS'] = false;

        $this->subject->tableArray = [
            'sys_category' => [1, 2, 3],
        ];

        self::assertFalse($this->subject->purgeItemArray(0));
    }

    /**
     * @test
     */
    public function purgeItemArrayReturnsTrueIfItemsHaveBeenPurged()
    {
        $GLOBALS['TCA']['sys_category']['ctrl']['versioningWS'] = true;

        $this->subject->tableArray = [
            'sys_category' => [1, 2, 3],
        ];

        $this->subject->expects(self::once())
            ->method('purgeVersionedIds')
            ->with('sys_category', [1, 2, 3])
            ->willReturn([2]);

        self::assertTrue($this->subject->purgeItemArray(0));
    }
}
