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

namespace TYPO3\CMS\Core\Tests\Functional\Security\ContentSecurityPolicy\Reporting;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\ReportRepository;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ReportRepositoryTest extends FunctionalTestCase
{
    #[Test]
    public function removeAllForScopeUsesReportType(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_http_report.csv');
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_http_report');
        $connection->insert('sys_http_report', [
            'uuid' => '2fdb81aa-c74f-4aa6-b21f-a52d5564f51d',
            'status' => 0,
            'type' => 'other',
            'scope' => 'backend',
            'summary' => sha1('other'),
            'created' => 0,
            'changed' => 0,
            'request_time' => 0,
        ]);

        self::assertSame(4, $this->get(ReportRepository::class)->removeAll(Scope::backend()));
        self::assertSame(1, $connection->count('*', 'sys_http_report', []));
    }

    #[Test]
    public function findAllSummarizedReturnsAllSummaries(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_http_report.csv');
        $reportRepository = $this->get(ReportRepository::class);
        $summaries = $reportRepository->findAllSummarized();

        self::assertCount(2, $summaries);
        self::assertSame('006462e9-fdc3-446d-a5b8-ba782deb6e02', (string)$summaries[0]->uuid);
        self::assertSame('0bced4d7-03df-4707-b306-d13da6739874', (string)$summaries[1]->uuid);
    }
}
