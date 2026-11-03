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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WorkspacePagesInWebMountTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspacePagesInWebMount.csv');
    }

    public static function webmountOfWorkspacePagesDataProvider(): \Generator
    {
        foreach ([0, 1] as $workspaceId) {
            $in = ' in workspace ' . $workspaceId;
            yield 'live page' . $in => [$workspaceId, 3, 2];
            // Moved into the webmount in the workspace
            yield 'live page outside the webmounts in live' . $in => [$workspaceId, 6, $workspaceId === 1 ? 2 : null];
            yield 'version of a live page' . $in => [$workspaceId, 10, 2];
            yield 'page new in the workspace' . $in => [$workspaceId, 12, 2];
            yield 'page new below a page new in the workspace' . $in => [$workspaceId, 13, 2];
            yield 'page new below a page not shown' . $in => [$workspaceId, 14, null];
            yield 'translation new in the workspace' . $in => [$workspaceId, 15, 2];
        }
    }

    #[Test]
    #[DataProvider('webmountOfWorkspacePagesDataProvider')]
    public function pagesNewInAWorkspaceAreCheckedAlongTheAncestryOfTheirWorkspace(int $workspaceId, int $pageId, ?int $expected): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $backendUser->workspace = $workspaceId;
        self::assertSame($expected, $backendUser->isInWebMount($pageId));
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertSame($expected, $backendUser->isInWebMount($pageId, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)));
    }

    #[Test]
    public function movedPageIsCheckedLikeTheWalkWithTheClause(): void
    {
        $backendUser = $this->setUpBackendUser(2);
        $alongAncestry = $backendUser->isInWebMount(11);
        $this->get(CacheManager::class)->getCache('runtime')->flush();
        self::assertSame($backendUser->isInWebMount(11, $backendUser->getPagePermsClause(Permission::PAGE_SHOW)), $alongAncestry);
    }
}
