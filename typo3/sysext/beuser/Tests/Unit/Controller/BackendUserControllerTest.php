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

namespace TYPO3\CMS\Beuser\Tests\Unit\Controller;

use TYPO3\CMS\Beuser\Controller\BackendUserController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUserControllerTest extends UnitTestCase
{
    /**
     * @var BackendUserController|\PHPUnit\Framework\MockObject\MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->uc = [
            'recentSwitchedToUsers' => []
        ];

        $this->subject = $this->getAccessibleMock(BackendUserController::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     */
    public function generateListOfLatestSwitchedUsersReturnsCorrectAmountAndOrder()
    {
        $items = range(1, BackendUserController::RECENT_USERS_LIMIT + 5);
        $expected = array_reverse(array_slice($items, -BackendUserController::RECENT_USERS_LIMIT));
        foreach ($items as $id) {
            $GLOBALS['BE_USER']->uc['recentSwitchedToUsers'] = $this->subject->_call('generateListOfMostRecentSwitchedUsers', $id);
        }

        self::assertCount(BackendUserController::RECENT_USERS_LIMIT, $GLOBALS['BE_USER']->uc['recentSwitchedToUsers']);
        self::assertSame($expected, $GLOBALS['BE_USER']->uc['recentSwitchedToUsers']);
    }

    /**
     * @test
     */
    public function listOfLatestSwitchedUsersDoesNotContainTheSameUserTwice()
    {
        $GLOBALS['BE_USER']->uc['recentSwitchedToUsers'] = $this->subject->_call('generateListOfMostRecentSwitchedUsers', 100);
        $GLOBALS['BE_USER']->uc['recentSwitchedToUsers'] = $this->subject->_call('generateListOfMostRecentSwitchedUsers', 100);

        self::assertCount(1, $GLOBALS['BE_USER']->uc['recentSwitchedToUsers']);
    }
}
