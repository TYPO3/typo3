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

namespace TYPO3\CMS\Redirects\Tests\Functional\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;
use TYPO3\CMS\Redirects\Tests\Functional\Repository\Fixtures\DemandFixture;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RedirectRepositoryTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
    ];

    protected array $coreExtensionsToLoad = ['redirects'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/pages.csv');

        $this->setUpBackendUser(1);

        $this->writeSiteConfiguration(
            'bar',
            $this->buildSiteConfiguration(13, 'https://bar.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://bar.com/'),
            ],
        );

        $this->writeSiteConfiguration(
            'foo',
            $this->buildSiteConfiguration(14, 'https://foo.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://foo.com/'),
            ],
        );
    }

    public static function demandProvider(): array
    {
        $allRecordCount = 6;
        return [
            'default demand' => [
                self::getDemand(),
                $allRecordCount,
                $allRecordCount - 4,
            ],
            'configuration with hitCount' => [
                self::getDemand(2),
                $allRecordCount,
                $allRecordCount - 3,
            ],
            'configuration with statusCode 302' => [
                self::getDemand(0, [302]),
                $allRecordCount,
                $allRecordCount - 1,
            ],
            'demand with statusCode 302, 303' => [
                self::getDemand(0, [302, 303]),
                $allRecordCount,
                $allRecordCount - 2,
            ],
            'demand with domain' => [
                self::getDemand(0, [], ['foo.com']),
                $allRecordCount,
                $allRecordCount - 2,
            ],
            'demand with domains' => [
                self::getDemand(0, [], ['foo.com', 'bar.com']),
                $allRecordCount,
                $allRecordCount - 3,
            ],
            'demand with path' => [
                self::getDemand(0, [], [], '/foo'),
                $allRecordCount,
                $allRecordCount - 1,
            ],
            'demand with path starts with' => [
                self::getDemand(0, [], [], '/foo%'),
                $allRecordCount,
                $allRecordCount - 3,
            ],
            'demand with path ends with' => [
                self::getDemand(0, [], [], '%/foo'),
                $allRecordCount,
                $allRecordCount - 1,
            ],
            'demand with path in the middle' => [
                self::getDemand(0, [], [], '%foo%'),
                $allRecordCount,
                $allRecordCount - 3,
            ],
            'demand with creation type "manually created"' => [
                self::getDemand(0, [], [], '', 1),
                $allRecordCount,
                $allRecordCount - 2,
            ],
        ];
    }

    #[DataProvider('demandProvider')]
    #[Test]
    public function removeByDemandWorks(Demand $demand, int $redirectBeforeCleanup, int $redirectAfterCleanup): void
    {
        self::assertSame(0, $this->getRedirectCount());
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        self::assertSame($redirectBeforeCleanup, $this->getRedirectCount());
        $repository = $this->get(RedirectRepository::class);
        $repository->removeByDemand($demand);
        self::assertSame($redirectAfterCleanup, $this->getRedirectCount());
    }

    public static function countRedirectsByDemandCountsCorrectlyDataProvider(): iterable
    {
        yield 'default demand' => [
            new Demand(),
            6,
        ];

        yield 'configuration with hitCount' => [
            new Demand(maxHits: 2),
            5,
        ];

        yield 'configuration with statusCode 302' => [
            new Demand(statusCodes: [302]),
            1,
        ];

        yield 'demand with statusCode 302, 303' => [
            new Demand(statusCodes: [302, 303]),
            2,
        ];

        yield 'demand with domain' => [
            new Demand(sourceHosts: ['foo.com']),
            2,
        ];

        yield 'demand with domains' => [
            new Demand(sourceHosts: ['foo.com', 'bar.com']),
            4,
        ];

        yield 'demand with path' => [
            new Demand(sourcePath: '/foo'),
            5,
        ];

        yield 'demand with target' => [
            new Demand(target: 'https://example.com/bar'),
            4,
        ];
        yield 'demand with creation type "manually created"' => [
            new Demand(creationType: 1),
            2,
        ];
        yield 'demand with protected state' => [
            new Demand(protected: 1),
            1,
        ];
    }

    #[DataProvider('countRedirectsByDemandCountsCorrectlyDataProvider')]
    #[Test]
    public function countRedirectsByDemandCountsCorrectly(Demand $demand, int $expectedCount): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $repository = $this->get(RedirectRepository::class);
        $redirectsCount = $repository->countRedirectsByDemand($demand);

        self::assertSame($expectedCount, $redirectsCount);
    }

    public static function countRedirectsByDemandRespectsUserPermissionsDataProvider(): iterable
    {
        yield 'default demand' => [
            new Demand(),
            4,
        ];

        yield 'configuration with hitCount' => [
            new Demand(maxHits: 2),
            3,
        ];

        yield 'configuration with statusCode 302' => [
            new Demand(statusCodes: [302]),
            1,
        ];

        yield 'demand with statusCode 302, 303' => [
            new Demand(statusCodes: [302, 303]),
            1,
        ];

        yield 'demand with domain' => [
            new Demand(sourceHosts: ['bar.com']),
            2,
        ];

        yield 'demand with domains' => [
            new Demand(sourceHosts: ['foo.com', 'bar.com']),
            2,
        ];

        yield 'demand with path' => [
            new Demand(sourcePath: '/foo'),
            3,
        ];

        yield 'demand with target' => [
            new Demand(target: 'https://example.com/bar'),
            2,
        ];
        yield 'demand with creation type "manually created"' => [
            new Demand(creationType: 1),
            1,
        ];
        yield 'demand with protected state' => [
            new Demand(protected: 1),
            1,
        ];
    }

    #[DataProvider('countRedirectsByDemandRespectsUserPermissionsDataProvider')]
    #[Test]
    public function countRedirectsByDemandRespectsUserPermissions(Demand $demand, int $expectedCount): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $backendUser = $this->setUpBackendUser(2);
        $backendUser->userGroupsUID = [1];
        $backendUser->groupData['webmounts'] = '13';

        $repository = $this->get(RedirectRepository::class);
        $redirectsCount = $repository->countRedirectsByDemand($demand);

        self::assertSame($expectedCount, $redirectsCount);
    }

    public static function filteredRedirectsArePaginatedCorrectlyDataProvider(): iterable
    {
        yield 'first page' => [
            (new DemandFixture(page: 1))->setLimit(2),
            [1, 2],
        ];
        // the second page skips uids 3 and 4, as they are not in web-mount 13
        yield 'second page' => [
            (new DemandFixture(page: 2))->setLimit(2),
            [5, 6],
        ];
        // the third page does not have any more redirects in web-mount 13
        yield 'third page' => [
            (new DemandFixture(page: 3))->setLimit(2),
            [],
        ];
    }

    #[DataProvider('filteredRedirectsArePaginatedCorrectlyDataProvider')]
    #[Test]
    public function filteredRedirectsArePaginatedCorrectly(Demand $demand, array $expectation): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_redirect.csv');

        $backendUser = $this->setUpBackendUser(2);
        $backendUser->userGroupsUID = [1];
        $backendUser->groupData['webmounts'] = '13';

        $repository = $this->get(RedirectRepository::class);
        $redirects = $repository->findRedirectsByDemand($demand);
        $redirectUids = array_column($redirects, 'uid');
        self::assertSame($expectation, $redirectUids);
    }

    private function getRedirectCount(): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_redirect')
            ->executeQuery()
            ->fetchOne();
    }

    private static function getDemand(
        int $hitCount = 0,
        array $statusCodes = [],
        array $domains = [],
        string $path = '',
        int $creationType = -1
    ): Demand {
        return new Demand(
            1,
            '',
            '',
            $domains,
            $path,
            '',
            $statusCodes,
            $hitCount,
            new \DateTimeImmutable('90 days ago'),
            $creationType
        );
    }
}
