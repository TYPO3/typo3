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

namespace TYPO3\CMS\Backend\Tests\Functional\Tree\View;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\Domain\Repository\Fixtures\QueryCountingMiddleware;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageTreeViewTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $configurationToUseInTestInstance = [
        'DB' => [
            'globalDriverMiddlewares' => [
                'typo3-tests/query-counting' => [
                    'target' => QueryCountingMiddleware::class,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageTree.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function treeContainsSubpagesInPageTreeOrder(): void
    {
        $this->setUpBackendUser(1);
        $subject = GeneralUtility::makeInstance(PageTreeView::class);
        $subject->init();
        $count = $subject->getTree(1, 2);

        self::assertSame(3, $count);
        self::assertSame(
            [
                [3, 2, true, true, false],
                [6, 1, true, true, true],
                [2, 2, true, false, false],
                [5, 1, false, true, false],
                [4, 1, false, false, true],
                [9, 2, false, false, true],
            ],
            array_map(
                static fn(array $item): array => [(int)$item['row']['uid'], $item['invertedDepth'], (bool)$item['hasSub'], $item['isFirst'], $item['isLast']],
                $subject->tree
            )
        );
        self::assertSame([2 => [3, 2, 9], 1 => [6, 5, 4]], $subject->orig_ids_hierarchy);
    }

    #[Test]
    public function treeContainsPagesMovedAndDeletedInTheWorkspace(): void
    {
        $this->setUpBackendUser(1)->workspace = 1;
        $subject = GeneralUtility::makeInstance(PageTreeView::class);
        $subject->init();
        $subject->getTree(1, 2);

        self::assertSame([3, 4, 6, 2, 5, 9], array_map(static fn(array $item): int => (int)$item['row']['uid'], $subject->tree));
        self::assertSame([2 => [3, 2, 9], 1 => [11, 6, 12]], $subject->orig_ids_hierarchy);
        self::assertSame('Second B moved', $subject->tree[1]['row']['title']);
    }

    #[Test]
    public function treeContainsOnlyPagesMatchingTheClause(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $subject = GeneralUtility::makeInstance(PageTreeView::class);
        $subject->init('AND ' . $backendUser->getPagePermsClause(Permission::PAGE_SHOW));
        $subject->getTree(1, 99);

        self::assertSame([3, 6, 7, 2, 5, 4], array_map(static fn(array $item): int => (int)$item['row']['uid'], $subject->tree));
    }

    #[Test]
    public function treeContainsOnlyPagesMatchingAnUnqualifiedClauseInTheWorkspace(): void
    {
        $this->setUpBackendUser(1)->workspace = 1;
        $subject = GeneralUtility::makeInstance(PageTreeView::class);
        $subject->init(' AND perms_everybody <> 0 AND uid <> 2');
        $subject->getTree(1, 99);

        self::assertSame([3, 4, 6, 7], array_map(static fn(array $item): int => (int)$item['row']['uid'], $subject->tree));
    }

    #[Test]
    public function treeIsOrderedByTitleIgnoringCase(): void
    {
        $this->setUpBackendUser(1);
        $subject = GeneralUtility::makeInstance(PageTreeView::class);
        $subject->init('', 'title');
        $subject->getTree(2, 1);

        self::assertSame([5, 4], array_map(static fn(array $item): int => (int)$item['row']['uid'], $subject->tree));
    }

    #[Test]
    public function treeIsFetchedWithOneQuery(): void
    {
        $this->setUpBackendUser(1);
        $subject = GeneralUtility::makeInstance(PageTreeView::class);
        $subject->init();
        QueryCountingMiddleware::$count = 0;
        $subject->getTree(1, 99);

        self::assertSame(1, QueryCountingMiddleware::$count);
    }
}
