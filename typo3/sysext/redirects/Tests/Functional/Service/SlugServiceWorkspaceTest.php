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

namespace TYPO3\CMS\Redirects\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItemFactory;
use TYPO3\CMS\Redirects\Service\SlugService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SlugServiceWorkspaceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects', 'workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SlugServiceTest_workspace.csv');
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->get(SiteWriter::class)->write('testing', ['rootPageId' => 1, 'base' => '/']);
        $this->get(SiteMatcher::class)->refresh();
    }

    #[Test]
    public function slugsOfSubPagesAreUpdatedFromTheirStateInTheWorkspace(): void
    {
        $changeItem = $this->get(SlugRedirectChangeItemFactory::class)->create(2);
        $changeItem = $changeItem->withChanged(array_merge($changeItem->getOriginal(), ['slug' => '/new-parent']));
        $this->get(SlugService::class)->rebuildSlugsForSlugChange(
            2,
            $changeItem,
            CorrelationId::forScope($this->get(Random::class)->generateRandomBase64String(32))
        );

        $slugs = [];
        foreach ($this->getAllRecords('pages') as $page) {
            $slugs[(int)$page['uid']] = $page['slug'];
        }
        // Live pages are not changed
        self::assertSame('/parent/child', $slugs[3]);
        self::assertSame('/other/moved', $slugs[5]);
        // The workspace version of the sub page keeps its own slug segment
        self::assertSame('/new-parent/child-in-workspace', $slugs[10]);
        // A page moved below the changed page in the workspace is updated as well
        self::assertSame('/new-parent/moved', $slugs[11]);
    }
}
