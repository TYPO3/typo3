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

namespace TYPO3\CMS\Core\Tests\Functional\Domain;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\PageAncestryResolver;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageAncestryResolverTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageAncestry.csv');
    }

    #[Test]
    public function ancestryIsOrderedFromPageToRootLevel(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(3, 0);
        self::assertSame([3, 2, 1], $subject->getPageIds());
        self::assertTrue($subject->isComplete());
        self::assertCount(3, $subject);
        self::assertSame('SubSub', $subject->getPage()->get('title'));
        self::assertSame(['SubSub', 'Sub', 'Root'], array_map(static fn(Page $page): string => $page->get('title'), iterator_to_array($subject, false)));
    }

    #[Test]
    public function ancestryOfNonExistingPageIsEmptyAndIncomplete(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(4711, 0);
        self::assertNull($subject->getPage());
        self::assertFalse($subject->isComplete());
        self::assertSame([], $subject->toLegacyRootLine());
    }

    #[Test]
    public function deletedAncestorEndsIncompleteAncestry(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(5, 0);
        self::assertSame([5], $subject->getPageIds());
        self::assertFalse($subject->isComplete());
    }

    #[Test]
    public function deletedAncestorIsIncludedOnRequest(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(5, 0, true);
        self::assertSame([5, 4, 1], $subject->getPageIds());
        self::assertTrue($subject->isComplete());
    }

    #[Test]
    public function circularPageTreeResultsInIncompleteAncestry(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(20, 0);
        self::assertSame([20, 21], $subject->getPageIds());
        self::assertFalse($subject->isComplete());
    }

    #[Test]
    public function workspaceAncestryFollowsMovedPage(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(3, 1);
        self::assertSame([3, 6, 1], $subject->getPageIds());
        self::assertSame(10, $subject->getPage()->getComputedProperties()->getVersionedUid());
    }

    #[Test]
    public function pageDeletedInWorkspaceIsPartOfAncestry(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(9, 1);
        self::assertSame([9, 2, 1], $subject->getPageIds());
        self::assertTrue($subject->isComplete());
    }

    #[Test]
    public function permissionsOfPageWithWorkspaceVersionAreTakenFromLivePage(): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(7, 1);
        self::assertSame([7, 6, 1], $subject->getPageIds());
        self::assertTrue($subject->getPermissions(6)->getEverybodyPermissions()->nothingIsGranted());
        self::assertTrue($subject->getPermissions(7)->getEverybodyPermissions()->showPagePermissionIsGranted());
    }

    public static function pageNewInWorkspaceIsResolvedInItsWorkspaceDataProvider(): iterable
    {
        yield 'from live' => [0];
        yield 'from its own workspace' => [1];
        yield 'from another workspace' => [2];
    }

    #[Test]
    #[DataProvider('pageNewInWorkspaceIsResolvedInItsWorkspaceDataProvider')]
    public function pageNewInWorkspaceIsResolvedInItsWorkspace(int $workspaceId): void
    {
        $subject = $this->get(PageAncestryResolver::class)->resolve(12, $workspaceId);
        self::assertSame([12, 3, 6, 1], $subject->getPageIds());
        self::assertTrue($subject->isComplete());
    }

    #[Test]
    public function pagesTsConfigOfPageNewInWorkspaceIsResolvedFromLive(): void
    {
        $this->setUpBackendUser(1);
        self::assertSame('root', BackendUtility::getPagesTSconfig(12)['mod.']['ancestry'] ?? null);
    }

    #[Test]
    public function formEngineRootlineOfPageNewInWorkspaceIsResolvedFromLive(): void
    {
        $this->setUpBackendUser(1);
        $result = $this->get(DatabasePageRootline::class)->addData(['effectivePid' => 12]);
        self::assertSame([12, 3, 6, 1, 0], array_map(intval(...), array_column($result['rootline'], 'uid')));
    }
    #[Test]
    public function ancestryIsCachedForTheRequest(): void
    {
        $resolver = $this->get(PageAncestryResolver::class);
        self::assertSame($resolver->resolve(3, 0), $resolver->resolve(3, 0));
        self::assertNotSame($resolver->resolve(3, 0), $resolver->resolve(3, 1));
    }

    public static function legacyRootLineMatchesBackendUtilityDataProvider(): iterable
    {
        yield 'live page' => [3, 0];
        yield 'live page below deleted page' => [5, 0];
        yield 'page moved in workspace' => [3, 1];
        yield 'page below page deleted in workspace' => [9, 1];
        yield 'page with workspace version of ancestor' => [7, 1];
    }

    #[Test]
    #[DataProvider('legacyRootLineMatchesBackendUtilityDataProvider')]
    public function legacyRootLineMatchesBackendUtility(int $pageId, int $workspaceId): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $workspaceId;
        $fields = ['uid', 'pid', 'title', 't3ver_state', '_ORIG_pid'];
        $reduce = static fn(array $rootLine): array => array_map(
            static function (array $record) use ($fields): array {
                $record = array_intersect_key($record, array_flip($fields));
                ksort($record);
                return array_map(static fn(mixed $value): ?string => $value === null ? null : (string)$value, $record);
            },
            $rootLine
        );
        self::assertSame(
            $reduce(BackendUtility::BEgetRootLine($pageId, '', true)),
            $reduce($this->get(PageAncestryResolver::class)->resolve($pageId, $workspaceId)->toLegacyRootLine())
        );
    }
}
