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

use TYPO3\CMS\Reactions\Repository\ReactionDemand;
use TYPO3\CMS\Reactions\Repository\ReactionRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ReactionsRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reactions'];

    public function demandProvider(): array
    {
        return [
            'default demand' => [$this->getDemand(), 2],
            'filter by name: Test' => [$this->getDemand('Test'), 2],
            'filter by name: Random' => [$this->getDemand('Random'), 1],
            'filter by reaction type: CreateRecordReaction' => [$this->getDemand('', 'create-record'), 1],
            'filter by name and reaction type: CreateRecordReaction' => [$this->getDemand('Test', 'create-record'), 1],
        ];
    }

    /**
     * @dataProvider demandProvider
     * @test
     * @param ReactionDemand $demand
     * @param int $resultCount
     */
    public function findByDemandWorks(
        ReactionDemand $demand,
        int $resultCount,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $results = (new ReactionRepository())->findByDemand($demand);
        self::assertCount($resultCount, $results);
    }

    /**
     * @test
     */
    public function findAllWorks(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $results = (new ReactionRepository())->findAll();
        self::assertCount(2, $results);
    }

    /**
     * @test
     */
    public function getReactionRecordsWithoutDemand(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $reactions = (new ReactionRepository())->getReactionRecords();
        self::assertCount(2, $reactions);
    }

    private function getDemand(
        string $name = '',
        string $reactionType = ''
    ): ReactionDemand {
        return new ReactionDemand(
            1,
            '',
            '',
            $name,
            $reactionType,
        );
    }
}
