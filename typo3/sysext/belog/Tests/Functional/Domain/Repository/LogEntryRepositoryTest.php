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

namespace TYPO3\CMS\Belog\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class LogEntryRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['belog'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../../core/Tests/Functional/Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $this->setUpBackendUser(1);
    }

    public static function pageTreeConstraintMatchesLogEntriesOfThePagesDataProvider(): iterable
    {
        yield 'page without depth' => [2, 0, [3]];
        yield 'page with one level' => [1, 1, [2, 3]];
        yield 'page with all levels' => [1, 99, [2, 3, 4]];
        yield 'page tree root with all levels' => [0, 99, [2, 3, 4, 5]];
        yield 'page tree root without depth' => [0, 0, [1, 2, 3, 4, 5]];
    }

    #[DataProvider('pageTreeConstraintMatchesLogEntriesOfThePagesDataProvider')]
    #[Test]
    public function pageTreeConstraintMatchesLogEntriesOfThePages(int $pageId, int $depth, array $expectedUids): void
    {
        $constraint = new Constraint();
        $constraint->setPageId($pageId);
        $constraint->setDepth($depth);
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $queryBuilder->select('uid')->from('sys_log')->orderBy('uid');
        $subject = $this->get(LogEntryRepository::class);
        $pageTreeConstraint = new \ReflectionMethod($subject, 'addPageTreeConstraintsToQuery')->invoke($subject, $constraint, $queryBuilder);
        if ($pageTreeConstraint !== null) {
            $queryBuilder->where($pageTreeConstraint);
        }
        self::assertSame($expectedUids, array_map(intval(...), $queryBuilder->executeQuery()->fetchFirstColumn()));
    }
}
