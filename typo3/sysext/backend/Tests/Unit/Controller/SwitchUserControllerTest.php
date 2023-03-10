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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Backend\Controller\SwitchUserController;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SwitchUserControllerTest extends UnitTestCase
{
    /**
     * Same as in SwitchUserController
     */
    protected const RECENT_USERS_LIMIT = 3;

    protected SwitchUserController&MockObject&AccessibleObjectInterface $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->uc = [
            'recentSwitchedToUsers' => [],
        ];
        $this->subject = $this->getAccessibleMock(SwitchUserController::class, null, [], '', false);
    }

    /**
     * @test
     */
    public function generateListOfLatestSwitchedUsersReturnsCorrectAmountAndOrder(): void
    {
        $items = range(1, self::RECENT_USERS_LIMIT + 5);
        $expected = array_reverse(array_slice($items, -self::RECENT_USERS_LIMIT));
        foreach ($items as $id) {
            $GLOBALS['BE_USER']->uc['recentSwitchedToUsers'] = $this->subject->_call('generateListOfMostRecentSwitchedUsers', $id);
        }

        self::assertCount(self::RECENT_USERS_LIMIT, $GLOBALS['BE_USER']->uc['recentSwitchedToUsers']);
        self::assertSame($expected, $GLOBALS['BE_USER']->uc['recentSwitchedToUsers']);
    }

    /**
     * @test
     */
    public function listOfLatestSwitchedUsersDoesNotContainTheSameUserTwice(): void
    {
        $GLOBALS['BE_USER']->uc['recentSwitchedToUsers'] = $this->subject->_call('generateListOfMostRecentSwitchedUsers', 100);

        self::assertCount(1, $GLOBALS['BE_USER']->uc['recentSwitchedToUsers']);
    }
}
