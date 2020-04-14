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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UserAspectTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getterReturnsProperDefaultValues()
    {
        $subject = new UserAspect(null, null);
        self::assertEquals(0, $subject->get('id'));
        self::assertEquals('', $subject->get('username'));
        self::assertFalse($subject->get('isLoggedIn'));
        self::assertEquals([], $subject->get('groupIds'));
        self::assertEquals([], $subject->get('groupNames'));
    }

    /**
     * @test
     */
    public function getterReturnsValidUserId()
    {
        $user = new FrontendUserAuthentication();
        $user->user = [
            'uid' => 13
        ];
        $subject = new UserAspect($user);
        self::assertEquals(13, $subject->get('id'));
    }

    /**
     * @test
     */
    public function getterReturnsValidUsername()
    {
        $user = new FrontendUserAuthentication();
        $user->user = [
            'uid' => 13,
            'username' => 'Teddy'
        ];
        $subject = new UserAspect($user);
        self::assertEquals('Teddy', $subject->get('username'));
    }

    /**
     * @test
     */
    public function isLoggedInReturnsFalseOnFrontendUserWithoutUserGroup()
    {
        $user = new FrontendUserAuthentication();
        $user->user = [
            'uid' => 13
        ];
        $subject = new UserAspect($user);
        self::assertFalse($subject->isLoggedIn());
    }

    /**
     * @test
     */
    public function isLoggedInReturnsTrueOnFrontendUserWithUserGroup()
    {
        $user = new FrontendUserAuthentication();
        $user->user = [
            'uid' => 13
        ];
        $user->groupData['uid'] = [1, 5, 7];
        $subject = new UserAspect($user);
        self::assertTrue($subject->isLoggedIn());
    }

    /**
     * @test
     */
    public function isLoggedInReturnsTrueOnBackendUserWithId()
    {
        $user = new BackendUserAuthentication();
        $user->user = [
            'uid' => 13
        ];
        $subject = new UserAspect($user);
        self::assertTrue($subject->isLoggedIn());
    }

    /**
     * @test
     */
    public function getGroupIdsReturnsFrontendUserGroups()
    {
        $user = new FrontendUserAuthentication();
        $user->user = [
            'uid' => 13
        ];
        $user->groupData['uid'] = [23, 54];
        $subject = new UserAspect($user);
        self::assertEquals([0, -2, 23, 54], $subject->getGroupIds());
    }

    /**
     * @test
     */
    public function getGroupIdsReturnsOverriddenGroups()
    {
        $user = new FrontendUserAuthentication();
        // Not used, because overridden with 33
        $user->groupData['uid'] = [23, 54];
        $subject = new UserAspect($user, [33]);
        self::assertEquals([33], $subject->getGroupIds());
    }

    public function isUserOrGroupSetDataProvider()
    {
        return [
            'Not logged in: no id or group set' => [
                0,
                null,
                null,
                false
            ],
            'only valid user id' => [
                13,
                null,
                null,
                true
            ],
            'valid user and overridden group' => [
                13,
                null,
                [33],
                true
            ],
            'no user and overridden group' => [
                0,
                null,
                [33],
                true
            ],
            'valid user, default groups and overridden group' => [
                13,
                [23],
                [33],
                true
            ],
            'no user, default groups and overridden group' => [
                0,
                [23],
                [33],
                true
            ],
            'Not logged in: no user, and classic group structure' => [
                0,
                null,
                [0, -1],
                false
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isUserOrGroupSetDataProvider
     * @param $userId
     * @param $userGroups
     * @param $overriddenGroups
     * @param bool $expectedResult
     */
    public function isUserOrGroupSetChecksForValidUser($userId, $userGroups, $overriddenGroups, $expectedResult)
    {
        $user = new FrontendUserAuthentication();
        if ($userId) {
            $user->user['uid'] = $userId;
        }
        $user->groupData['uid'] = $userGroups;
        $subject = new UserAspect($user, $overriddenGroups);
        self::assertEquals($expectedResult, $subject->isUserOrGroupSet());
    }

    /**
     * @test
     */
    public function getThrowsExceptionOnInvalidArgument()
    {
        $this->expectException(AspectPropertyNotFoundException::class);
        $this->expectExceptionCode(1529996567);
        $subject = new UserAspect();
        $subject->get('football');
    }
}
