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
use TYPO3\CMS\Backend\Search\LiveSearch\DatabaseRecordProvider;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandProperty;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\DemandPropertyName;
use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseRecordProviderTest extends FunctionalTestCase
{
    #[Test]
    public function liveSearchFindsRecordForNonAdminWithPageMount(): void
    {
        self::assertGreaterThan(0, $this->countForBackendUser(2, 'SecretHeader'));
    }

    private function countForBackendUser(int $userUid, string $query): int
    {
        $backendUser = $this->setUpBackendUser($userUid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $searchDemand = new SearchDemand([
            new DemandProperty(DemandPropertyName::query, $query),
        ]);
        return $this->get(DatabaseRecordProvider::class)->count($searchDemand);
    }

    #[Test]
    public function liveSearchExcludesRecordForNonAdminWithoutPageMounts(): void
    {
        self::assertSame(0, $this->countForBackendUser(3, 'SecretHeader'));
    }

    #[Test]
    public function liveSearchExcludesRecordOnShowablePageForNonAdminWithoutPageMounts(): void
    {
        self::assertSame(0, $this->countForBackendUser(3, 'UnmountedHeader'));
    }

    #[Test]
    public function liveSearchFindsRecordForAdmin(): void
    {
        self::assertGreaterThan(0, $this->countForBackendUser(1, 'SecretHeader'));
    }

    /**
     * tt_content:1 ("SecretHeader") lives on page 10. Editor "with mount" (uid 2) has page 10 as
     * a DB mount, editor "without mount" (uid 3) has no page mounts at all (e.g. a file-only
     * editor). Both may select tt_content, so page access is the only differentiator.
     * tt_content:2 ("UnmountedHeader") lives on page 20, which grants SHOW to everybody but is
     * not part of any page mount.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DatabaseRecordProviderAccess.csv');
    }
}
