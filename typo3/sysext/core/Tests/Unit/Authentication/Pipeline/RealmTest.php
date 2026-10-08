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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication\Pipeline;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RealmTest extends UnitTestCase
{
    #[Test]
    public function backendRealmIsBackendOnly(): void
    {
        $subject = Realm::backend();
        self::assertSame('backend', $subject->identifier);
        self::assertTrue($subject->isBackend());
        self::assertFalse($subject->isFrontend());
    }

    #[Test]
    public function frontendRealmIsFrontendOnly(): void
    {
        $subject = Realm::frontend();
        self::assertSame('frontend', $subject->identifier);
        self::assertTrue($subject->isFrontend());
        self::assertFalse($subject->isBackend());
    }

    #[Test]
    public function frontendRealmOfSiteIsFrontend(): void
    {
        $subject = Realm::frontend(new Site('main', 1, []));
        self::assertSame('frontend:main', $subject->identifier);
        self::assertTrue($subject->isFrontend());
    }

    #[Test]
    public function frontendRealmWithoutSiteConfigurationIsGeneralFrontendRealm(): void
    {
        self::assertSame('frontend', Realm::frontend(new NullSite())->identifier);
    }

    #[Test]
    public function customRealmIsNeitherBackendNorFrontend(): void
    {
        $subject = Realm::custom('mcp');
        self::assertSame('mcp', $subject->identifier);
        self::assertFalse($subject->isBackend());
        self::assertFalse($subject->isFrontend());
    }

    public static function invalidCustomRealmIdentifierDataProvider(): array
    {
        return [
            'empty' => [''],
            'uppercase' => ['Api'],
            'leading digit' => ['1api'],
            'colon' => ['frontend:main'],
            'space' => ['my api'],
        ];
    }

    #[Test]
    #[DataProvider('invalidCustomRealmIdentifierDataProvider')]
    public function customRealmRejectsInvalidIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791491201);
        Realm::custom($identifier);
    }

    public static function reservedRealmIdentifierDataProvider(): array
    {
        return [
            'backend' => ['backend'],
            'frontend' => ['frontend'],
        ];
    }

    #[Test]
    #[DataProvider('reservedRealmIdentifierDataProvider')]
    public function customRealmRejectsReservedIdentifier(string $identifier): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791491202);
        Realm::custom($identifier);
    }

    public static function registrableRealmIdentifierDataProvider(): array
    {
        return [
            'backend' => ['backend'],
            'frontend' => ['frontend'],
            'custom' => ['api'],
        ];
    }

    #[Test]
    #[DataProvider('registrableRealmIdentifierDataProvider')]
    public function realmOfRegistrationIsResolved(string $identifier): void
    {
        self::assertSame($identifier, Realm::fromRegistration($identifier)->identifier);
    }

    #[Test]
    public function realmOfRegistrationRejectsFrontendRealmOfSite(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1792942004);
        Realm::fromRegistration('frontend:main');
    }

    #[Test]
    public function realmOfRegistrationRejectsInvalidIdentifier(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1791491201);
        Realm::fromRegistration('Not a realm');
    }

    #[Test]
    public function frontendRealmOfSiteIsServedByFrontendRegistration(): void
    {
        self::assertSame('frontend', Realm::frontend(new Site('main', 1, []))->getRegistrationIdentifier());
        self::assertSame('frontend', Realm::frontend()->getRegistrationIdentifier());
        self::assertSame('backend', Realm::backend()->getRegistrationIdentifier());
        self::assertSame('api', Realm::custom('api')->getRegistrationIdentifier());
    }
}
