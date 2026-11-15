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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Linkvalidator\Repository\PagesRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PagesRepositoryTest extends FunctionalTestCase
{
    public static function getAllSubpagesForPageReturnsSubpagesDataProvider(): iterable
    {
        yield 'hidden pages are left out, but not their subpages' => [99, false, [2, 3, 5, 10, 12, 14, 15]];
        yield 'hidden pages are included on request' => [99, true, [2, 3, 4, 5, 6, 7, 10, 11, 12, 14, 15]];
        yield 'first level only' => [1, false, [2, 10, 14]];
        yield 'no level' => [0, false, []];
    }

    #[DataProvider('getAllSubpagesForPageReturnsSubpagesDataProvider')]
    #[Test]
    public function getAllSubpagesForPageReturnsSubpages(int $depth, bool $considerHidden, array $expectedPageIds): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesRepositoryTest/subpages.csv');
        $pageIds = new PagesRepository()->getAllSubpagesForPage(1, $depth, '', $considerHidden);
        sort($pageIds);
        self::assertSame($expectedPageIds, $pageIds);
    }

    #[Test]
    public function getAllSubpagesForPageRespectsPermissionClause(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesRepositoryTest/subpages.csv');
        $permsClause = $this->setUpBackendUser(2)->getPagePermsClause(Permission::PAGE_SHOW);
        $pageIds = new PagesRepository()->getAllSubpagesForPage(1, 99, $permsClause);
        sort($pageIds);
        self::assertSame([2, 3, 5, 10, 12], $pageIds);
    }

    #[Test]
    public function doesRootLineContainHiddenPagesReturnsCorrectResultWith(): void
    {
        $pageInfo = [
            'uid' => 4,
            'pid' => 3,
            'hidden' => false,
            'extendToSubpages' => false,
        ];

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesRepositoryTest/rootline_with_hidden_and_extendToSubpages.csv');
        $pagesRepository = new PagesRepository();
        $result = $pagesRepository->doesRootLineContainHiddenPages($pageInfo);
        self::assertTrue($result);
    }

    #[Test]
    public function doesRootLineContainHiddenPagesReturnsCorrectResultWithout(): void
    {
        $pageInfo = [
            'uid' => 4,
            'pid' => 3,
            'hidden' => false,
            'extendToSubpages' => false,
        ];

        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesRepositoryTest/rootline_without_hidden_and_extendToSubpages.csv');
        $pagesRepository = new PagesRepository();
        $result = $pagesRepository->doesRootLineContainHiddenPages($pageInfo);
        self::assertFalse($result);
    }
}
