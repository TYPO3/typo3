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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PrincipalTest extends UnitTestCase
{
    #[Test]
    public function groupNamesDefaultToEmptyList(): void
    {
        $principal = new Principal(
            context: new PrincipalContext(role: PrincipalRole::USER, id: 42),
            realmIdentifier: 'frontend:main',
            username: 'member',
        );
        self::assertSame([], $principal->groupNames);
    }

    public static function nonPositiveUserIdDataProvider(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
        ];
    }

    /**
     * A principal always denotes an authenticated user, the user aspect
     * reports every principal as logged in.
     */
    #[DataProvider('nonPositiveUserIdDataProvider')]
    #[Test]
    public function constructorThrowsForNonPositiveUserId(int $userId): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791465463);
        new Principal(
            context: new PrincipalContext(role: PrincipalRole::USER, id: $userId),
            realmIdentifier: 'backend',
            username: 'editor',
        );
    }

    #[Test]
    public function constructorThrowsForEmptyRealmIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791466989);
        new Principal(
            context: new PrincipalContext(role: PrincipalRole::USER, id: 1),
            realmIdentifier: '',
            username: 'editor',
        );
    }

    public static function elevatedRoleDataProvider(): array
    {
        return [
            'admin' => [PrincipalRole::ADMIN],
            'maintainer' => [PrincipalRole::MAINTAINER],
            'system' => [PrincipalRole::SYSTEM],
        ];
    }

    #[DataProvider('elevatedRoleDataProvider')]
    #[Test]
    public function constructorThrowsForFrontendPrincipalWithElevatedRole(PrincipalRole $role): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791466458);
        new Principal(
            context: new PrincipalContext(role: $role, id: 42),
            realmIdentifier: 'frontend:main',
            username: 'member',
        );
    }

    #[Test]
    public function constructorThrowsForImpersonatedFrontendPrincipal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791466458);
        new Principal(
            context: new PrincipalContext(
                role: PrincipalRole::USER,
                id: 42,
                impersonatedBy: new PrincipalContext(role: PrincipalRole::ADMIN, id: 1),
            ),
            realmIdentifier: 'frontend',
            username: 'member',
        );
    }

    public static function realmDecidesTheKindOfUserDataProvider(): array
    {
        return [
            'frontend' => ['frontend', true],
            'frontend of a site' => ['frontend:main', true],
            'backend' => ['backend', false],
            'prefix without separator' => ['frontendish', false],
            'token based realm' => ['api', false],
        ];
    }

    #[DataProvider('realmDecidesTheKindOfUserDataProvider')]
    #[Test]
    public function realmDecidesTheKindOfUser(string $realmIdentifier, bool $expected): void
    {
        $principal = new Principal(
            context: new PrincipalContext(role: PrincipalRole::USER, id: 42),
            realmIdentifier: $realmIdentifier,
            username: 'member',
        );
        self::assertSame($expected, $principal->belongsToFrontendRealm());
        self::assertSame(!$expected, $principal->isBackendUser());
    }
}
