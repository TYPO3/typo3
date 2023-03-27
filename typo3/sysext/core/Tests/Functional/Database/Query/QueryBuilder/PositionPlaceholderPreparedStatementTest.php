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

namespace TYPO3\CMS\Core\Tests\Functional\Database\Query\QueryBuilder;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Statement;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\UnsupportedPreparedStatementParameterTypeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PositionPlaceholderPreparedStatementTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/queryBuilder_preparedStatement.csv');
    }

    /**
     * @test
     */
    public function canBeInstantiated(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');

        self::assertIsObject($queryBuilder);
        self::assertInstanceOf(QueryBuilder::class, $queryBuilder);
    }

    /**
     * @test
     */
    public function preparedStatementWithPositionPlaceholderAndBindValueWorks(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select(...['*'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createPositionalParameter(10, ParameterType::INTEGER)
                )
            )
            ->orderBy('sorting', 'ASC')
            // add deterministic sort order as last sorting information, which many dbms
            // and version does it by itself, but not all.
            ->addOrderBy('uid', 'ASC')
            ->prepare();

        // first execution of prepared statement
        $result1 = $statement->executeQuery();
        $rows1 = $result1->fetchAllAssociative();
        self::assertSame(2, count($rows1));
        self::assertSame(11, (int)($rows1[0]['uid'] ?? 0));
        self::assertSame(12, (int)($rows1[1]['uid'] ?? 0));

        // second execution of prepared statement with changed placeholder value
        $statement->bindValue(1, 20, ParameterType::INTEGER);
        $result2 = $statement->executeQuery();
        $rows2 = $result2->fetchAllAssociative();
        self::assertSame(2, count($rows2));
        self::assertSame(21, (int)($rows2[0]['uid'] ?? 0));
        self::assertSame(22, (int)($rows2[1]['uid'] ?? 0));
    }

    /**
     * @test
     */
    public function preparedStatementWithPositionPlaceholderAndBindValueWithWileLoopWorks(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select(...['*'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createPositionalParameter(10, ParameterType::INTEGER)
                )
            )
            ->orderBy('sorting', 'ASC')
            // add deterministic sort order as last sorting information, which many dbms
            // and version does it by itself, but not all.
            ->addOrderBy('uid', 'ASC')
            ->prepare();

        // first execution of prepared statement
        $result1 = $statement->executeQuery();
        $rows1 = [];
        while ($row = $result1->fetchAssociative()) {
            $rows1[] = $row;
        }

        self::assertSame(2, count($rows1));
        self::assertSame(11, (int)($rows1[0]['uid'] ?? 0));
        self::assertSame(12, (int)($rows1[1]['uid'] ?? 0));

        // second execution of prepared statement with changed placeholder value
        $statement->bindValue(1, 20, ParameterType::INTEGER);
        $result2 = $statement->executeQuery();
        $rows2 = [];
        while ($row = $result2->fetchAssociative()) {
            $rows2[] = $row;
        }
        self::assertSame(2, count($rows2));
        self::assertSame(21, (int)($rows2[0]['uid'] ?? 0));
        self::assertSame(22, (int)($rows2[1]['uid'] ?? 0));
    }

    /**
     * @test
     */
    public function preparedStatementWithoutRetrievingFullResultSetAndWithoutFreeingPriorResultSetWorks(): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select(...['*'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createPositionalParameter(10, ParameterType::INTEGER)
                )
            )
            ->orderBy('sorting', 'ASC')
            // add deterministic sort order as last sorting information, which many dbms
            // and version does it by itself, but not all.
            ->addOrderBy('uid', 'ASC')
            ->prepare();

        // first execution of prepared statement
        $statement->bindValue(1, 10, ParameterType::INTEGER);
        $result1 = $statement->executeQuery();
        self::assertSame(11, (int)($result1->fetchAssociative()['uid'] ?? 0));

        // second execution of prepared statement with changed placeholder value
        $statement->bindValue(1, 20, ParameterType::INTEGER);
        $result2 = $statement->executeQuery();
        self::assertSame(21, (int)($result2->fetchAssociative()['uid'] ?? 0));
    }

    /**
     * @test
     */
    public function preparedStatementWorksIfRetrievedThroughRuntimeCacheAndPriorResultSetNotFreedAfterIncompleteDataRetrieval(): void
    {
        $runtimeCache = GeneralUtility::makeInstance(CacheManager::class)->getCache('runtime');
        $cacheIdentifier = 'prepared-statement-through-runtime-cache';
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $statement = $queryBuilder
            ->select(...['*'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createPositionalParameter(10, ParameterType::INTEGER)
                )
            )
            ->orderBy('sorting', 'ASC')
            // add deterministic sort order as last sorting information, which many dbms
            // and version does it by itself, but not all.
            ->addOrderBy('uid', 'ASC')
            ->prepare();
        $runtimeCache->set($cacheIdentifier, $statement);

        // first execution of prepared statement
        $statement->bindValue(1, 10, ParameterType::INTEGER);
        $result1 = $statement->executeQuery();
        self::assertSame(11, (int)($result1->fetchAssociative()['uid'] ?? 0));
        unset($statement);

        // retrieve statement from runtime cache
        $statement2 = $runtimeCache->get($cacheIdentifier);
        self::assertInstanceOf(Statement::class, $statement2);

        // second execution of prepared statement with changed placeholder value
        $statement2->bindValue(1, 20, ParameterType::INTEGER);
        $result2 = $statement2->executeQuery();
        self::assertSame(21, (int)($result2->fetchAssociative()['uid'] ?? 0));
        unset($statement2);

        // We need to free used resultsets here to avoid a test-setup related issue with sqlite resulting in
        // a locked db, using old database data because of os in-memory usage of overridden sqlite db file.
        $result1->free();
        $result2->free();
        unset($result1, $result2);
    }

    public static function invalidParameterTypesForPreparedStatements(): array
    {
        return [
            'PARAM_INT_ARRAY' => [Connection::PARAM_INT_ARRAY, 'PARAM_INT_ARRAY', [10, 20]],
            'PARAM_STR_ARRAY' => [Connection::PARAM_STR_ARRAY, 'PARAM_STR_ARRAY', [10, 20]],
        ];
    }

    /**
     * @test
     * @dataProvider invalidParameterTypesForPreparedStatements
     */
    public function preparedStatementThrowsExceptionForInvalidParameterType(int $arrayParameterType, string $arrayParameterName, array $arrayValues): void
    {
        // expected exception
        $this->expectExceptionObject(UnsupportedPreparedStatementParameterTypeException::new($arrayParameterName));

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        // prepare should result in exception, thus no further execution with it
        $queryBuilder
            ->select(...['*'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createPositionalParameter($arrayValues, $arrayParameterType)
                )
            )
            ->orderBy('sorting', 'ASC')
            // add deterministic sort order as last sorting information, which many dbms
            // and version does it by itself, but not all.
            ->addOrderBy('uid', 'ASC')
            ->prepare();
    }
}
