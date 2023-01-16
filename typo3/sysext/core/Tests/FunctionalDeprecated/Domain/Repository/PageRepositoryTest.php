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

namespace TYPO3\CMS\Core\Tests\FunctionalDeprecated\Domain\Repository;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageRepositoryTest extends \TYPO3\CMS\Core\Tests\Functional\Domain\Repository\PageRepositoryTest
{
    /**
     * @test
     */
    public function initSetsPublicPropertyCorrectlyForWorkspacePreview(): void
    {
        $workspaceId = 2;
        $subject = new PageRepository(new Context([
            'workspace' => new WorkspaceAspect($workspaceId),
        ]));

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');

        $expectedSQL = sprintf(
            ' AND ((%s = 0) AND (((%s = 0) OR (%s = 2))) AND (%s <> 255))',
            $connection->quoteIdentifier('pages.deleted'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.doktype')
        );

        self::assertSame($expectedSQL, $subject->where_hid_del);
    }

    /**
     * @test
     */
    public function initSetsEnableFieldsCorrectlyForLive(): void
    {
        $subject = new PageRepository(new Context([
            'date' => new DateTimeAspect(new \DateTimeImmutable('@1451779200')),
        ]));

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $expectedSQL = sprintf(
            ' AND ((((%s = 0) AND (%s <= 0) AND (%s = 0) AND (((%s = 0) OR (%s = 4))) AND (%s = 0) AND (%s <= 1451779200) AND (((%s = 0) OR (%s > 1451779200))))) AND (%s <> 255))',
            $connection->quoteIdentifier('pages.deleted'),
            $connection->quoteIdentifier('pages.t3ver_state'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.t3ver_oid'),
            $connection->quoteIdentifier('pages.t3ver_state'),
            $connection->quoteIdentifier('pages.hidden'),
            $connection->quoteIdentifier('pages.starttime'),
            $connection->quoteIdentifier('pages.endtime'),
            $connection->quoteIdentifier('pages.endtime'),
            $connection->quoteIdentifier('pages.doktype')
        );

        self::assertSame($expectedSQL, $subject->where_hid_del);
    }
}
