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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Domain\Repository\PageDescendantsRepository;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageDescendantsRepositoryTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageDescendants.csv');
    }

    public static function descendantsAreFoundDepthFirstDataProvider(): iterable
    {
        yield 'live' => [0, [3, 6, 7, 2, 5, 4, 10, 11]];
        yield 'workspace with moved, new and deleted pages' => [1, [3, 4, 6, 7, 2, 13, 10, 11]];
        yield 'workspace with a new page only' => [2, [3, 6, 7, 2, 16, 5, 4, 10, 11]];
    }

    #[Test]
    #[DataProvider('descendantsAreFoundDepthFirstDataProvider')]
    public function descendantsAreFoundDepthFirst(int $workspaceId, array $expectedPageIds): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([1], 99, $workspaceId);
        self::assertSame($expectedPageIds, array_column($rows, 'uid'));
    }

    #[Test]
    public function descendantsHoldTheirLevel(): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([1], 99, 0);
        self::assertSame([1, 2, 3, 1, 2, 2, 1, 2], array_column($rows, '_LEVEL'));
    }

    #[Test]
    public function depthLimitsTheLevels(): void
    {
        $subject = $this->get(PageDescendantsRepository::class);
        self::assertSame([3, 2, 10], array_column($subject->findDescendants([1], 1, 0), 'uid'));
        self::assertSame([3, 6, 2, 5, 4, 10, 11], array_column($subject->findDescendants([1], 2, 0), 'uid'));
        self::assertSame([], $subject->findDescendants([1], 0, 0));
    }

    #[Test]
    public function descendantsOfSeveralPagesAreFoundInTheGivenOrderWithoutDuplicates(): void
    {
        $subject = $this->get(PageDescendantsRepository::class);
        self::assertSame([5, 4, 6, 7], array_column($subject->findDescendants([2, 3], 99, 0), 'uid'));
        self::assertSame([6, 7, 2, 5, 4, 10, 11], array_column($subject->findDescendants([3, 1], 99, 0), 'uid'));
    }

    #[Test]
    public function deletedPagesAreIncludedOnRequest(): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([1], 99, 0, includeDeleted: true);
        self::assertSame([3, 6, 7, 2, 5, 4, 8, 9, 10, 11], array_column($rows, 'uid'));
    }

    #[Test]
    public function pagesDeletedInTheWorkspaceAreIncludedOnRequest(): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([2], 99, 1, includeDeletedInWorkspace: true);
        self::assertSame([13, 5], array_column($rows, 'uid'));
        self::assertSame(14, $rows[1]['_ORIG_uid']);
    }

    #[Test]
    public function workspaceVersionIsReturnedForPagesWithVersion(): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([3], 99, 1, fields: ['title']);
        self::assertSame(
            [
                ['uid' => 4, 'pid' => 3, 'sorting' => 64, 'title' => 'Second B moved', '_ORIG_uid' => 12, '_LEVEL' => 1],
                ['uid' => 6, 'pid' => 3, 'sorting' => 256, 'title' => 'First visible', '_ORIG_uid' => 15, '_LEVEL' => 1],
                ['uid' => 7, 'pid' => 6, 'sorting' => 256, 'title' => 'Below hidden', '_LEVEL' => 2],
            ],
            $rows
        );
    }

    #[Test]
    public function allFieldsAreReturnedOnRequest(): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([2], 1, 0, fields: ['*']);
        self::assertSame('Second A', $rows[0]['title']);
        self::assertArrayHasKey('perms_everybody', $rows[0]);
        self::assertArrayHasKey('doktype', $rows[0]);
    }

    public static function restrictionsAreEvaluatedOnTheWorkspaceVersionDataProvider(): iterable
    {
        yield 'live' => [0, [3, 2, 5, 4, 10, 11]];
        yield 'workspace with a visible version of a hidden page' => [1, [3, 4, 6, 7, 2, 13, 10, 11]];
    }

    #[Test]
    #[DataProvider('restrictionsAreEvaluatedOnTheWorkspaceVersionDataProvider')]
    public function restrictionsAreEvaluatedOnTheWorkspaceVersion(int $workspaceId, array $expectedPageIds): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([1], 99, $workspaceId, restrictions: [new HiddenRestriction()]);
        self::assertSame($expectedPageIds, array_column($rows, 'uid'));
    }

    public static function liveConstraintIsEvaluatedOnTheLivePageDataProvider(): iterable
    {
        yield 'live' => [0, [3, 6, 7, 2, 5, 4]];
        yield 'workspace with a version without permissions' => [1, [3, 4, 6, 7, 2, 13]];
    }

    #[Test]
    #[DataProvider('liveConstraintIsEvaluatedOnTheLivePageDataProvider')]
    public function liveConstraintIsEvaluatedOnTheLivePage(int $workspaceId, array $expectedPageIds): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants(
            [1],
            99,
            $workspaceId,
            liveConstraint: static fn(ExpressionBuilder $expr, string $alias): string => (string)$expr->neq($alias . '.perms_everybody', 0),
        );
        self::assertSame($expectedPageIds, array_column($rows, 'uid'));
    }

    #[Test]
    #[DataProvider('liveConstraintIsEvaluatedOnTheLivePageDataProvider')]
    public function pagePermissionClauseOfBackendUserIsUsableAsLiveConstraint(int $workspaceId, array $expectedPageIds): void
    {
        $clause = $this->setUpBackendUser(2)->getPagePermsClause(Permission::PAGE_SHOW);
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants(
            [1],
            99,
            $workspaceId,
            liveConstraint: static fn(): string => $clause,
        );
        self::assertSame($expectedPageIds, array_column($rows, 'uid'));
    }

    public static function unqualifiedLiveConstraintIsEvaluatedOnTheLivePageDataProvider(): iterable
    {
        yield 'live' => [0, [3, 6, 7]];
        yield 'workspace' => [1, [3, 4, 6, 7]];
    }

    #[Test]
    #[DataProvider('unqualifiedLiveConstraintIsEvaluatedOnTheLivePageDataProvider')]
    public function unqualifiedLiveConstraintIsEvaluatedOnTheLivePage(int $workspaceId, array $expectedPageIds): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants(
            [1],
            99,
            $workspaceId,
            liveConstraint: static fn(): string => 'perms_everybody <> 0 AND uid <> 2 AND pid > 0',
        );
        self::assertSame($expectedPageIds, array_column($rows, 'uid'));
    }

    #[Test]
    public function cyclingPageTreeEnds(): void
    {
        $rows = $this->get(PageDescendantsRepository::class)->findDescendants([20], 99, 0);
        self::assertSame([21], array_column($rows, 'uid'));
    }

    #[Test]
    public function descendantsMatchPageRepository(): void
    {
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        self::assertSame(
            $pageRepository->getDescendantPageIdsRecursive(1, 99, 0, [], true),
            array_column($this->get(PageDescendantsRepository::class)->findDescendants([1], 99, 0), 'uid')
        );
    }

    public static function descendantsMatchPageTreeRepositoryDataProvider(): iterable
    {
        yield 'live' => [0];
        yield 'workspace 1' => [1];
        yield 'workspace 2' => [2];
    }

    #[Test]
    #[DataProvider('descendantsMatchPageTreeRepositoryDataProvider')]
    public function descendantsMatchPageTreeRepository(int $workspaceId): void
    {
        $pageTreeRepository = GeneralUtility::makeInstance(PageTreeRepository::class, $workspaceId);
        $expected = array_values(array_diff(array_map(intval(...), array_column($pageTreeRepository->getFlattenedPages([1], 99), 'uid')), [1]));
        $actual = array_column($this->get(PageDescendantsRepository::class)->findDescendants([1], 99, $workspaceId), 'uid');
        sort($expected);
        sort($actual);
        self::assertSame($expected, $actual);
    }
}
