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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\Restriction;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WorkspaceRestrictionTest extends FunctionalTestCase
{
    #[Test]
    public function buildExpressionAddsLiveWorkspaceWhereClause(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('aTable');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(0));
        $queryBuilder->select('*')->from('aTable');
        $createdSql = $queryBuilder->getSQL();
        // Remove the various quote chars of DB engines to end up with "universal" SQL to assert
        $createdSql = str_replace(['`', '"'], '', $createdSql);
        self::assertSame('SELECT * FROM aTable WHERE ((aTable.t3ver_wsid = 0) AND (((aTable.t3ver_oid = 0) OR (aTable.t3ver_state = 4))))', $createdSql);
    }

    #[Test]
    public function buildExpressionAddsNonLiveWorkspaceWhereClause(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('aTable');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(42));
        $queryBuilder->select('*')->from('aTable');
        $createdSql = $queryBuilder->getSQL();
        $createdSql = str_replace(['`', '"'], '', $createdSql);
        self::assertSame('SELECT * FROM aTable WHERE ((aTable.t3ver_wsid IN (0, 42)) AND (((aTable.t3ver_oid = 0) OR (aTable.t3ver_state = 4))))', $createdSql);
    }

    #[Test]
    public function buildExpressionAddsLiveWorkspaceLimitedWhereClause(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => false,
        ];
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('aTable');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(0));
        $queryBuilder->select('*')->from('aTable');
        $createdSql = $queryBuilder->getSQL();
        $createdSql = str_replace(['`', '"'], '', $createdSql);
        self::assertSame('SELECT * FROM aTable', $createdSql);
    }

    #[Test]
    public function buildExpressionAddsNonLiveWorkspaceLimitedWhereClause(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => false,
        ];
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('aTable');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(42));
        $queryBuilder->select('*')->from('aTable');
        $createdSql = $queryBuilder->getSQL();
        $createdSql = str_replace(['`', '"'], '', $createdSql);
        self::assertSame('SELECT * FROM aTable', $createdSql);
    }

    #[Test]
    public function buildExpressionQueriesAllVersionedRecordsWithinAWorkspaceAsWell(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('aTable');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(42, true));
        $queryBuilder->select('*')->from('aTable');
        $createdSql = $queryBuilder->getSQL();
        $createdSql = str_replace(['`', '"'], '', $createdSql);
        self::assertSame('SELECT * FROM aTable WHERE ((aTable.t3ver_wsid IN (0, 42)) AND (aTable.t3ver_state <> 2))', $createdSql);
    }

    #[Test]
    public function buildExpressionQueriesAllVersionedRecordsWithinLiveStillRemovesDeletedState(): void
    {
        $GLOBALS['TCA']['aTable']['ctrl'] = [
            'versioningWS' => true,
        ];
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('aTable');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(0, true));
        $queryBuilder->select('*')->from('aTable');
        $createdSql = $queryBuilder->getSQL();
        $createdSql = str_replace(['`', '"'], '', $createdSql);
        self::assertSame('SELECT * FROM aTable WHERE ((aTable.t3ver_wsid = 0) AND (aTable.t3ver_state <> 2))', $createdSql);
    }

    #[Test]
    public function restrictionCanBeCombinedWithJoinInWorkspaceAndIncludeAllVersionedRecords(): void
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(new WorkspaceRestriction(42, true));
        // The created query does not make much sense, but this test is only about creating valid SQL
        // by the restriction when a join is involved.
        $queryBuilder->select('sys_category.uid')
            ->from('sys_category')
            ->join(
                'sys_category',
                'pages',
                'pages',
                $queryBuilder->expr()->eq(
                    'sys_category.uid',
                    $queryBuilder->quoteIdentifier('pages.uid')
                )
            );
        $createdSql = $queryBuilder->getSQL();
        $createdSql = str_replace(['`', '"'], '', $createdSql);
        // Actually execute the query to verify no exception is thrown.
        $queryBuilder->executeQuery();
        self::assertSame(
            'SELECT sys_category.uid FROM sys_category INNER JOIN pages pages ON sys_category.uid = pages.uid WHERE ((((sys_category.t3ver_wsid IN (0, 42)) AND (sys_category.t3ver_state <> 2))) AND (((pages.t3ver_wsid IN (0, 42)) AND (pages.t3ver_state <> 2))))',
            $createdSql
        );
    }
}
