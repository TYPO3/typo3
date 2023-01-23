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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Workspace;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;
use TYPO3\TestingFramework\Core\Acceptance\Helper\Topbar;

final class WorkspaceModuleCest
{
    private static string $topBarModuleSelector = '#typo3-cms-workspaces-backend-toolbaritems-workspaceselectortoolbaritem';
    private static string $currentPageTitle = 'styleguide TCA demo';
    private static string $newPageTitle = 'styleguide TCA demo workspace';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function switchToWorkspace(ApplicationTester $I): void
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->waitForText('Test Workspace', 5, self::$topBarModuleSelector);
        $I->click('Test Workspace', self::$topBarModuleSelector);
        $I->waitForElement('.topbar-header.typo3-in-workspace');
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->waitForText('Test Workspace', 5, '#typo3-cms-workspaces-backend-toolbaritems-workspaceselectortoolbaritem .active');
    }

    /**
     * @depends switchToWorkspace
     */
    public function editPageTitleAndSeeChangeInWorkspaceModule(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->click('Page');
        $pageTree->openPath([self::$currentPageTitle]);
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);

        $I->comment('Rename page');
        $I->switchToContentFrame();
        $I->click('typo3-backend-editable-page-title button');
        $I->fillField('typo3-backend-editable-page-title input[name="newPageTitle"]', self::$newPageTitle);
        $I->click('typo3-backend-editable-page-title button[type="submit"]');

        $I->switchToMainFrame();
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Go to Workspace Module', self::$topBarModuleSelector);
        $I->click('Go to Workspace Module', self::$topBarModuleSelector);

        $I->comment('See the new page title in Workspace module');
        $I->switchToContentFrame();
        $I->see(self::$newPageTitle, '#workspace-panel');
    }

    /**
     * @depends editPageTitleAndSeeChangeInWorkspaceModule
     */
    public function chooseMassActionPublish(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('Workspaces');
        $I->switchToContentFrame();
        $I->selectOption('select[name=mass-action]', 'Publish');

        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Next');

        $I->dontSee(self::$newPageTitle, '#workspace-panel');
    }
}
