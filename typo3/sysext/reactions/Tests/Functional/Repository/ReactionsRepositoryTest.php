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

namespace TYPO3\CMS\Reactions\Tests\Functional\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Reactions\Repository\ReactionDemand;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ReactionsRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reactions'];

    public static function demandProvider(): array
    {
        return [
            'default demand' => [
                new ReactionDemand(1, '', '', '', ''),
                4,
            ],
            'filter by name: Test' => [
                new ReactionDemand(1, '', '', 'Test', ''),
                2,
            ],
            'filter by name: Random' => [
                new ReactionDemand(1, '', '', 'Random', ''),
                1,
            ],
            'filter by name: INVALID' => [
                new ReactionDemand(1, '', '', 'INVALID', ''),
                2,
            ],
            'filter by reaction type: CreateRecordReaction' => [
                new ReactionDemand(1, '', '', '', 'create-record'),
                1,
            ],
            'filter by name and reaction type: CreateRecordReaction' => [
                new ReactionDemand(1, '', '', 'Test', 'create-record'),
                1,
            ],
            'filter by only enabled' => [
                new ReactionDemand(1, '', '', '', '', true),
                4,
            ],
            'filter by impersonated user: admin' => [
                new ReactionDemand(1, '', '', '', '', false, 1),
                4,
            ],
            'filter by impersonated user: nobody impersonates this one' => [
                new ReactionDemand(1, '', '', '', '', false, 3),
                0,
            ],
            'filter by call: never called' => [
                new ReactionDemand(1, '', '', '', '', false, 0, 'never'),
                4,
            ],
            'filter by call: called at least once' => [
                new ReactionDemand(1, '', '', '', '', false, 0, 'ever'),
                0,
            ],
            'sort by last call with a filter' => [
                new ReactionDemand(1, 'last_called', 'desc', 'Test', '', false, 0, 'never'),
                2,
            ],
            'sort by impersonated user with a filter' => [
                new ReactionDemand(1, 'impersonate_user', 'desc', 'Test', '', true, 1),
                2,
            ],
        ];
    }

    #[DataProvider('demandProvider')]
    #[Test]
    public function findByDemandWorks(ReactionDemand $demand, int $resultCount): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $results = $this->get(ReactionRepository::class)->findByDemand($demand);
        self::assertCount($resultCount, $results);
        self::assertSame($resultCount, $this->get(ReactionRepository::class)->countAll($demand));
    }

    #[Test]
    public function findImpersonatedUsersReturnsTheUsersOfReactionsByName(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_reaction');
        $connection->update('sys_reaction', ['impersonate_user' => 9], ['uid' => 2]);
        $connection->update('sys_reaction', ['impersonate_user' => 3], ['uid' => 3]);
        $connection->update('sys_reaction', ['impersonate_user' => 2], ['uid' => 4]);

        self::assertSame([1 => 'admin', 9 => 'editor', 3 => 'test1'], $this->get(ReactionRepository::class)->findImpersonatedUsers());
    }

    #[Test]
    public function findAllWorks(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $results = $this->get(ReactionRepository::class)->findAll();
        self::assertCount(4, $results);
    }

    #[Test]
    public function getReactionRecordsWithoutDemand(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $reactions = $this->get(ReactionRepository::class)->getReactionRecords();
        self::assertCount(4, $reactions);
    }

    #[Test]
    public function updateLastCallWritesTimeStatusAndReason(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $this->get(Context::class)->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@1699963200')));

        $this->get(ReactionRepository::class)->updateLastCall(2, 400, 'No fields given.');

        $this->assertCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_lastCall.csv');
    }
}
