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

namespace TYPO3\CMS\Backend\Tests\Functional\Search\LiveSearch;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Search\LiveSearch\PageRecordProvider;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandProperty;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageRecordProviderTest extends FunctionalTestCase
{
    #[Test]
    public function liveSearchFindsInMountPageForNonAdmin(): void
    {
        self::assertGreaterThan(0, $this->countForBackendUser(2, 'InMountPage'));
    }

    private function countForBackendUser(int $userUid, string $query): int
    {
        $backendUser = $this->setUpBackendUser($userUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, $query),
        ]);
        return $this->get(PageRecordProvider::class)->count($searchDemand);
    }

    #[Test]
    public function liveSearchExcludesOutOfMountPageForNonAdmin(): void
    {
        self::assertSame(0, $this->countForBackendUser(2, 'OutOfMountPage'));
    }

    #[Test]
    public function liveSearchExcludesShowablePageForNonAdminWithoutPageMounts(): void
    {
        self::assertSame(0, $this->countForBackendUser(3, 'OutOfMountChildPage'));
    }

    #[Test]
    public function liveSearchFindsOutOfMountPageForAdmin(): void
    {
        self::assertGreaterThan(0, $this->countForBackendUser(1, 'OutOfMountPage'));
    }

    /**
     * "InMountPage" (uid 10) lives inside the editor's page mount (page 1). "OutOfMountPage"
     * (uid 20) grants SHOW to everybody but lives outside the mount. Editor (uid 2) is a non-admin
     * with page 1 as DB mount; the out-of-mount page must not be found (the count path must apply
     * the same page-id restriction as the find path). Editor (uid 3) has no page mounts at all;
     * "OutOfMountChildPage" (uid 21, below page 20) grants SHOW to everybody but must not be found.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageRecordProviderAccess.csv');
    }
}
