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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\CMS\Redirects\Repository\RedirectRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RedirectRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

    public function demandProvider(): array
    {
        $allRecordCount = 6;
        return [
            'default demand' => [$this->getDemand(), $allRecordCount, $allRecordCount - 4],
            'configuration with hitCount' => [$this->getDemand(2), $allRecordCount, $allRecordCount - 3],
            'configuration with statusCode 302' => [
                $this->getDemand(0, [302]),
                $allRecordCount,
                $allRecordCount - 1,
            ],
            'demand with statusCode 302, 303' => [
                $this->getDemand(0, [302, 303]),
                $allRecordCount,
                $allRecordCount - 2,
            ],
            'demand with domain' => [
                $this->getDemand(0, [], ['foo.com']),
                $allRecordCount,
                $allRecordCount - 2,
            ],
            'demand with domains' => [
                $this->getDemand(0, [], ['foo.com', 'bar.com']),
                $allRecordCount,
                $allRecordCount - 3,
            ],
            'demand with path' => [
                $this->getDemand(0, [], [], '/foo'),
                $allRecordCount,
                $allRecordCount - 1,
            ],
            'demand with path starts with' => [
                $this->getDemand(0, [], [], '/foo%'),
                $allRecordCount,
                $allRecordCount - 3,
            ],
            'demand with path ends with' => [
                $this->getDemand(0, [], [], '%/foo'),
                $allRecordCount,
                $allRecordCount - 1,
            ],
            'demand with path in the middle' => [
                $this->getDemand(0, [], [], '%foo%'),
                $allRecordCount,
                $allRecordCount - 3,
            ],
        ];
    }

    /**
     * @dataProvider demandProvider
     * @test
     */
    public function removeByDemandWorks(
        Demand $demand,
        int $redirectBeforeCleanup,
        int $redirectAfterCleanup
    ): void {
        self::assertSame(0, $this->getRedirectCount());
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectRepositoryTest_redirects.csv');

        self::assertSame($redirectBeforeCleanup, $this->getRedirectCount());
        $repository = new RedirectRepository();
        $repository->removeByDemand($demand);
        self::assertSame($redirectAfterCleanup, $this->getRedirectCount());
    }

    protected function getRedirectCount(): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_redirect');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_redirect')
            ->executeQuery()
            ->fetchOne();
    }

    private function getDemand(
        int $hitCount = 0,
        array $statusCodes = [],
        array $domains = [],
        string $path = ''
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
            new \DateTimeImmutable('90 days ago')
        );
    }
}
