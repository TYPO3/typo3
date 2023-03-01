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

namespace TYPO3\CMS\Core\Tests\Unit\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ScopeTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        Scope::reset();
    }

    /**
     * @test
     */
    public function backendSingletonIsCreated(): void
    {
        $scope = Scope::backend();
        self::assertSame($scope, Scope::backend());
    }

    /**
     * @test
     */
    public function frontendSingletonIsCreated(): void
    {
        $scope = Scope::frontend();
        self::assertSame($scope, Scope::frontend());
    }

    /**
     * @test
     */
    public function frontendSiteIsCreated(): void
    {
        $site = new Site('my-site', 1, []);
        $scope = Scope::frontendSite($site);
        self::assertSame($scope, Scope::frontendSite($site));
    }

    public static function frontendSingletonIsUsedForInvalidSiteDataProvider(): \Generator
    {
        yield [null];
        yield [new NullSite()];
    }

    /**
     * @test
     * @dataProvider frontendSingletonIsUsedForInvalidSiteDataProvider
     */
    public function frontendSingletonIsUsedForInvalidSite(?SiteInterface $site): void
    {
        self::assertSame(Scope::frontend(), Scope::frontendSite($site));
    }

    /**
     * @test
     */
    public function frontendSiteIdentifierSingletonIsCreated(): void
    {
        $scope = Scope::frontendSiteIdentifier('my-site');
        self::assertSame($scope, Scope::frontendSiteIdentifier('my-site'));
    }

    /**
     * @test
     */
    public function scopeIsCreatedFromString(): void
    {
        self::assertSame(
            Scope::frontend(),
            Scope::from(ApplicationType::FRONTEND->value)
        );
        self::assertSame(
            Scope::backend(),
            Scope::from(ApplicationType::BACKEND->value)
        );
        self::assertSame(
            Scope::frontendSiteIdentifier('my-site'),
            Scope::from(ApplicationType::FRONTEND->value . '.my-site')
        );
    }
}
