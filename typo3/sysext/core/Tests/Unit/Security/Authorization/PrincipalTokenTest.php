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

namespace TYPO3\CMS\Core\Tests\Unit\Security\Authorization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\PrincipalToken;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PrincipalTokenTest extends UnitTestCase
{
    public static function rolesDataProvider(): \Generator
    {
        yield 'backend user' => [PrincipalRole::USER, 'backend', ['ROLE_USER', 'ROLE_BACKEND_USER']];
        yield 'backend admin' => [PrincipalRole::ADMIN, 'backend', ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_BACKEND_USER']];
        yield 'system maintainer' => [PrincipalRole::MAINTAINER, 'backend', ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MAINTAINER', 'ROLE_BACKEND_USER']];
        yield 'system' => [PrincipalRole::SYSTEM, 'cli', ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_MAINTAINER', 'ROLE_SYSTEM', 'ROLE_BACKEND_USER']];
        yield 'frontend user' => [PrincipalRole::USER, 'frontend', ['ROLE_USER', 'ROLE_FRONTEND_USER']];
        yield 'frontend user of a site' => [PrincipalRole::USER, 'frontend:main', ['ROLE_USER', 'ROLE_FRONTEND_USER']];
        yield 'backend user with groups' => [PrincipalRole::USER, 'backend', ['ROLE_USER', 'ROLE_BACKEND_USER', 'ROLE_BACKEND_GROUP_3', 'ROLE_BACKEND_GROUP_1'], [3, 1, 3]];
        yield 'frontend user with groups' => [PrincipalRole::USER, 'frontend', ['ROLE_USER', 'ROLE_FRONTEND_USER', 'ROLE_FRONTEND_GROUP_2'], [2]];
    }

    #[Test]
    #[DataProvider('rolesDataProvider')]
    public function rolesAreDerivedFromThePrincipal(PrincipalRole $role, string $realmIdentifier, array $expectedRoles, array $groupIds = []): void
    {
        $subject = new PrincipalToken(new Principal(new PrincipalContext($role, 7, $groupIds), $realmIdentifier, 'jane'));

        self::assertSame($expectedRoles, $subject->getRoleNames());
        self::assertSame($expectedRoles, $subject->getUser()->getRoles());
    }

    #[Test]
    #[DataProvider('rolesDataProvider')]
    public function hasRoleMatchesTheRoles(PrincipalRole $role, string $realmIdentifier, array $expectedRoles, array $groupIds = []): void
    {
        $subject = new PrincipalUser(new Principal(new PrincipalContext($role, 7, $groupIds), $realmIdentifier, 'jane'));

        foreach (SecurityRole::cases() as $securityRole) {
            self::assertSame(in_array($securityRole->value, $expectedRoles, true), $subject->hasRole($securityRole), $securityRole->name);
        }
    }

    #[Test]
    public function tokenProvidesThePrincipal(): void
    {
        $principal = new Principal(new PrincipalContext(PrincipalRole::USER, 7), 'backend', 'jane');

        $subject = new PrincipalToken($principal);

        self::assertSame($principal, $subject->getPrincipal());
        self::assertInstanceOf(PrincipalUser::class, $subject->getUser());
    }

    #[Test]
    public function userIdentifierIsUniqueAcrossRealms(): void
    {
        $backend = new PrincipalToken(new Principal(new PrincipalContext(PrincipalRole::USER, 7), 'backend', 'jane'));
        $frontend = new PrincipalToken(new Principal(new PrincipalContext(PrincipalRole::USER, 7), 'frontend', 'jane'));

        self::assertSame('backend:7', $backend->getUserIdentifier());
        self::assertSame('frontend:7', $frontend->getUserIdentifier());
    }

    #[Test]
    public function principalCannotBeReplaced(): void
    {
        $subject = new PrincipalToken(new Principal(new PrincipalContext(PrincipalRole::USER, 7), 'backend', 'jane'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1792768549);
        $subject->setUser(new PrincipalUser(new Principal(new PrincipalContext(PrincipalRole::ADMIN, 1), 'backend', 'admin')));
    }

    #[Test]
    public function tokenCannotBeSerialized(): void
    {
        $subject = new PrincipalToken(new Principal(new PrincipalContext(PrincipalRole::USER, 7), 'backend', 'jane'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1792768550);
        serialize($subject);
    }
}
