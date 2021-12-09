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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\NamedParameterNotSupportedForPreparedStatementException;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class NamedPlaceholderPreparedStatementTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(function () {
            $this->setUpDatabase();
        });
    }

    protected function setUpDatabase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSet/queryBuilder_preparedStatement.csv');
    }

    /**
     * @test
     */
    public function throwsNamedParameterNotSupportedForPreparedStatementExceptionIfNamedPlacholderAreSetOnPrepare(): void
    {
        $this->expectExceptionObject(
            NamedParameterNotSupportedForPreparedStatementException::new('dcValue1')
        );

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->select(...['*'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(10, ParameterType::INTEGER)
                )
            )
            ->orderBy('sorting', 'ASC')
            // add deterministic sort order as last sorting information, which many dbms
            // and version does it by itself, but not all.
            ->addOrderBy('uid', 'ASC')
            ->prepare();
    }
}
