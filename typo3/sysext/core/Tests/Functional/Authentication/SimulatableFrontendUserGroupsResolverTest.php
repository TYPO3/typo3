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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\SimulatableFrontendUserGroupsResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SimulatableFrontendUserGroupsResolverTest extends FunctionalTestCase
{
    public static function resolveReturnsGroupsOnPagesTheBackendUserMayShowDataProvider(): array
    {
        return [
            'groups on pages the backend user may show, ordered by title' => [
                2,
                [['uid' => 2, 'title' => 'Authors'], ['uid' => 1, 'title' => 'Members']],
            ],
            'no groups if the backend user may show no page with groups' => [
                3,
                [],
            ],
            'admin gets visible groups on visible pages, but not on root level' => [
                1,
                [['uid' => 2, 'title' => 'Authors'], ['uid' => 1, 'title' => 'Members'], ['uid' => 3, 'title' => 'Staff']],
            ],
        ];
    }

    #[Test]
    #[DataProvider('resolveReturnsGroupsOnPagesTheBackendUserMayShowDataProvider')]
    public function resolveReturnsGroupsOnPagesTheBackendUserMayShow(int $backendUserUid, array $expectedGroups): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SimulatableFrontendUserGroups.csv');
        $backendUser = $this->setUpBackendUser($backendUserUid);

        self::assertSame($expectedGroups, $this->get(SimulatableFrontendUserGroupsResolver::class)->resolve($backendUser));
    }
}
