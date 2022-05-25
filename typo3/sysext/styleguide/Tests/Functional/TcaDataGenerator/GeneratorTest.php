<?php

declare(strict_types=1);

namespace TYPO3\CMS\Styleguide\Tests\Functional\TcaDataGenerator;

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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Styleguide\TcaDataGenerator\Generator;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class GeneratorTest extends FunctionalTestCase
{
    /**
     * @var non-empty-string[] Have styleguide loaded
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/styleguide',
    ];

    /**
     * @test
     */
    public function generatorCreatesBasicRecord(): void
    {
        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        // Verify there is no tx_styleguide_elements_basic yet
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_styleguide_elements_basic');
        $queryBuilder->getRestrictions()->removeAll();
        $count = (int)$queryBuilder->count('uid')
            ->from('tx_styleguide_elements_basic')
            ->executeQuery()
            ->fetchOne();
        self::assertEquals(0, $count);

        $generator = new Generator();
        $generator->create();

        // Verify there is at least one tx_styleguide_elements_basic record now
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_styleguide_elements_basic');
        $queryBuilder->getRestrictions()->removeAll();
        $count = (int)$queryBuilder->count('uid')
            ->from('tx_styleguide_elements_basic')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($this->getPageUidFor('tx_styleguide_elements_basic'), \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
        self::assertGreaterThan(0, $count);
    }

    protected function getPageUidFor(string $dataTable): ?int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages')
            ->createQueryBuilder();

        $row = $queryBuilder
            ->select(...['uid'])
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_styleguide_containsdemo',
                    $queryBuilder->createNamedParameter($dataTable, \PDO::PARAM_STR)
                ),
                // only default language pages needed
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->orderBy('pid', 'DESC')
            // add uid as deterministic last sorting, as not all dbms in all versions do that
            ->addOrderBy('uid', 'ASC')
            ->executeQuery()
            ->fetchAssociative();

        if ($row['uid'] ?? false) {
            return (int)$row['uid'];
        }
        return null;
    }
}
