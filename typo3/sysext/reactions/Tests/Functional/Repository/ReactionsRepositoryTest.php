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
        ];
    }

    /**
     * @dataProvider demandProvider
     * @test
     */
    public function findByDemandWorks(ReactionDemand $demand, int $resultCount): void
    {
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
        self::assertCount(4, $results);
    }

    /**
     * @test
     */
    public function getReactionRecordsWithoutDemand(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/ReactionsRepositoryTest_reactions.csv');
        $reactions = (new ReactionRepository())->getReactionRecords();
        self::assertCount(4, $reactions);
    }
}
