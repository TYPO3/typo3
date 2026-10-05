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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication\Pipeline;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Pipeline\LegacyUserPrincipalFactory;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LegacyUserPrincipalFactoryTest extends FunctionalTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        parent::tearDown();
    }

    #[Test]
    public function backendUserThatIsNotTheGlobalBackendUserIsRefused(): void
    {
        $user = $this->createBackendUser(['uid' => 5, 'username' => 'editor']);
        $GLOBALS['BE_USER'] = $this->createBackendUser(['uid' => 6, 'username' => 'other']);

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1791530201);
        $this->get(LegacyUserPrincipalFactory::class)->fromUser($user, Realm::backend());
    }

    #[Test]
    public function groupNamesOfBackendUserFollowTheOrderOfGroupIds(): void
    {
        $user = $this->createBackendUser(['uid' => 5, 'username' => 'editor']);
        $user->userGroupsUID = [3, 1, 2];
        $user->userGroups = [
            1 => ['uid' => 1, 'title' => 'One'],
            2 => ['uid' => 2, 'title' => 'Two'],
            3 => ['uid' => 3, 'title' => 'Three'],
        ];
        $GLOBALS['BE_USER'] = $user;

        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($user, Realm::backend());

        self::assertSame([3, 1, 2], $principal?->context->groupIds);
        self::assertSame(['Three', 'One', 'Two'], $principal->groupNames);
    }

    #[Test]
    public function groupNamesOfFrontendUserFollowTheOrderOfGroupIds(): void
    {
        $user = new FrontendUserAuthentication();
        $user->initializeUserSessionManager();
        $user->user = ['uid' => 10, 'username' => 'feuser'];
        $user->userGroups = [
            7 => ['uid' => 7, 'title' => 'Seven'],
            4 => ['uid' => 4, 'title' => 'Four'],
        ];

        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($user, Realm::frontend());

        self::assertSame(PrincipalRole::USER, $principal?->context->role);
        self::assertSame([7, 4], $principal->context->groupIds);
        self::assertSame(['Seven', 'Four'], $principal->groupNames);
    }

    #[Test]
    public function switchedUserIsImpersonatedByTheOriginalUser(): void
    {
        $this->get(ConnectionPool::class)->getConnectionForTable('be_users')->insert('be_users', [
            'uid' => 1,
            'username' => 'admin',
            'admin' => 1,
        ]);
        $user = $this->createBackendUser(['uid' => 5, 'username' => 'editor']);
        $user->setSessionData('backuserid', 1);
        $GLOBALS['BE_USER'] = $user;

        $principal = $this->get(LegacyUserPrincipalFactory::class)->fromUser($user, Realm::backend());

        self::assertSame(5, $principal?->context->id);
        self::assertSame(PrincipalRole::USER, $principal->context->role);
        self::assertSame(1, $principal->context->impersonatedBy?->id);
        self::assertSame(PrincipalRole::ADMIN, $principal->context->impersonatedBy->role);
    }

    #[Test]
    public function switchedUserWithoutResolvableOriginalUserYieldsNoPrincipal(): void
    {
        $user = $this->createBackendUser(['uid' => 5, 'username' => 'editor']);
        $user->setSessionData('backuserid', 99);
        $GLOBALS['BE_USER'] = $user;

        self::assertNull($this->get(LegacyUserPrincipalFactory::class)->fromUser($user, Realm::backend()));
    }

    private function createBackendUser(array $record): BackendUserAuthentication
    {
        $user = new BackendUserAuthentication();
        $user->initializeUserSessionManager();
        $user->user = $record + ['admin' => 0];
        return $user;
    }
}
