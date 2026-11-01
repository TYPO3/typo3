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

namespace TYPO3\CMS\Backend\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Pins the behaviour of BackendUtility::BEgetRootLine() and the rootline based
 * BackendUserAuthentication::isInWebMount() a replacement has to match.
 */
final class BackendUtilityRootLineTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RootLine.csv');
    }

    #[Test]
    public function rootLineIsOrderedFromPageButKeyedFromRootIncludingVirtualTreeRoot(): void
    {
        $this->setUpBackendUser(1);
        self::assertSame([3 => 3, 2 => 2, 1 => 1, 0 => 0], $this->uids(BackendUtility::BEgetRootLine(3)));
    }

    #[Test]
    public function deletedAncestorEndsRootLineWithoutVirtualTreeRoot(): void
    {
        $this->setUpBackendUser(1);
        self::assertSame([0 => 5], $this->uids(BackendUtility::BEgetRootLine(5)));
    }

    #[Test]
    public function deletedAncestorIsIncludedWithoutDeleteClause(): void
    {
        $this->setUpBackendUser(1);
        self::assertSame([3 => 5, 2 => 4, 1 => 1, 0 => 0], $this->uids(BackendUtility::BEgetRootLine(5, '', false, [], false)));
    }

    #[Test]
    public function clauseEndsRootLineAtFirstNonMatchingAncestor(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $clause = ' AND ' . $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        self::assertSame([0 => 7], $this->uids(BackendUtility::BEgetRootLine(7, $clause)));
    }

    #[Test]
    public function pageIsInWebMountIfAllPagesUpToTheMountAreReadable(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        self::assertSame(1, $backendUser->isInWebMount(3));
    }

    #[Test]
    public function readablePageIsNotInWebMountIfAnAncestorIsUnreadable(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        self::assertNull($backendUser->isInWebMount(7));
    }

    #[Test]
    public function workspaceOverlayFollowsMovedPage(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;
        $rootLine = BackendUtility::BEgetRootLine(3, '', true);
        self::assertSame([3 => 3, 2 => 6, 1 => 1, 0 => 0], $this->uids($rootLine));
        self::assertSame(6, (int)$rootLine[3]['pid']);
        self::assertSame(2, (int)$rootLine[3]['_ORIG_pid']);
        self::assertSame(4, (int)$rootLine[3]['t3ver_state']);
    }

    #[Test]
    public function withoutWorkspaceOverlayLivePositionIsUsed(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;
        self::assertSame([3 => 3, 2 => 2, 1 => 1, 0 => 0], $this->uids(BackendUtility::BEgetRootLine(3)));
    }

    #[Test]
    public function workspaceDeletePlaceholderAncestorStaysInRootLine(): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;
        $rootLine = BackendUtility::BEgetRootLine(9, '', true);
        self::assertSame([3 => 9, 2 => 2, 1 => 1, 0 => 0], $this->uids($rootLine));
        self::assertSame(2, (int)$rootLine[2]['t3ver_state']);
    }

    #[Test]
    public function pageCreatedInWorkspaceIsResolvedFromLive(): void
    {
        $this->setUpBackendUser(1);
        self::assertSame([4 => 12, 3 => 3, 2 => 2, 1 => 1, 0 => 0], $this->uids(BackendUtility::BEgetRootLine(12)));
    }

    #[Test]
    public function permissionsAreEvaluatedOnLiveAncestorInWorkspace(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $backendUser->workspace = 1;
        $clause = ' AND ' . $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        self::assertSame([0 => 7], $this->uids(BackendUtility::BEgetRootLine(7, $clause, true)));
        self::assertNull($backendUser->isInWebMount(7));
    }

    /**
     * @return array<int, int>
     */
    private function uids(array $rootLine): array
    {
        return array_map(static fn(array $page): int => (int)$page['uid'], $rootLine);
    }
}
