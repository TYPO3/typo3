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

namespace TYPO3\CMS\Core\Tests\Functional\Authentication;

use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class GroupResolverTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_groups.csv');
    }

    public function findAllUsersOfGroupsHandlesRecursiveCallsDataProvider(): array
    {
        return [
            'invalid group' => [
                [238],
                [],
            ],
            'direct group with multiple users' => [
                [1],
                [2, 3],
            ],
            'direct group with one users' => [
                [4],
                [3],
            ],
            'direct and indirect subgroup with one users' => [
                [2],
                [3],
            ],
            'subgroup with no direct reference' => [
                [5],
                [3],
            ],
            'subgroup and direct with no direct reference' => [
                [5, 2, 3],
                [3],
            ],
            'no group given' => [
                [],
                [],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider findAllUsersOfGroupsHandlesRecursiveCallsDataProvider
     * @param int[] $groupIds
     * @param array $expectedUsers
     */
    public function findAllUsersOfGroupsHandlesRecursiveCalls(array $groupIds, array $expectedUsers): void
    {
        $subject = GeneralUtility::makeInstance(GroupResolver::class);
        $users = $subject->findAllUsersInGroups($groupIds, 'be_groups', 'be_users');
        self::assertEquals($expectedUsers, array_map('intval', array_column($users, 'uid')));
    }
}
