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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Page;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;
use TYPO3\TestingFramework\Core\Acceptance\Helper\Topbar;

class WorkspaceModuleCest
{
    public static string $topBarModuleSelector = '#typo3-cms-workspaces-backend-toolbaritems-workspaceselectortoolbaritem';
    public static string $currentPageTitle = 'styleguide TCA demo';
    public static string $newPageTitle = 'styleguide TCA demo workspace';

    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     */
    public function switchToWorkspace(BackendTester $I)
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Test Workspace', self::$topBarModuleSelector);
        $I->click('Test Workspace', self::$topBarModuleSelector);
        $I->canSeeElement('body.typo3-in-workspace');
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->see('Test Workspace', '#typo3-cms-workspaces-backend-toolbaritems-workspaceselectortoolbaritem .selected');
    }

    /**
     * @depends switchToWorkspace
     * @param BackendTester $I
     * @param PageTree $pageTree
     */
    public function editPageTitleAndSeeChangeInWorkspaceModule(BackendTester $I, PageTree $pageTree): void
    {
        $currentPageTitle = 'styleguide TCA demo';
        $newPageTitle = 'styleguide TCA demo workspace';

        $I->click('Page');
        $pageTree->openPath([$currentPageTitle]);
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);

        $I->comment('Rename page');
        $I->switchToContentFrame();
        $I->click('button[data-action="edit"]');
        $I->fillField('input[class*="t3js-title-edit-input"]', $newPageTitle);
        $I->click('button[data-action="submit"]');

        $I->switchToMainFrame();
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Go to Workspace Module', self::$topBarModuleSelector);
        $I->click('Go to Workspace Module', self::$topBarModuleSelector);

        $I->comment('See the new page title in Workspace module');
        $I->switchToContentFrame();
        $I->see($newPageTitle, '#workspace-panel');
    }

    /**
     * @depends editPageTitleAndSeeChangeInWorkspaceModule
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     */
    public function chooseMassActionPublish(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Workspaces');
        $I->switchToContentFrame();
        $I->selectOption('select[name=mass-action]', 'Publish');

        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Next');

        $I->dontSee(self::$newPageTitle, '#workspace-panel');
    }
}
