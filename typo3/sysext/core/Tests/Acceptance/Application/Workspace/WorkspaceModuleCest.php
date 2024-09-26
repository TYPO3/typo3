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
        $I->waitForElement('.scaffold.scaffold-in-workspace');
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

        $I->comment('Rename page');
        $I->switchToContentFrame();

        $I->waitForElementVisible('typo3-backend-editable-page-title');
        $I->wait(1);
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('[data-action=\"edit\"]').click()");
        $I->wait(1);
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('input').value = '" . self::$newPageTitle . "'");
        $I->wait(1);
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('[data-action=\"save\"]').click()");
        $I->wait(1);

        $I->switchToMainFrame();
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Workspaces Module', self::$topBarModuleSelector);
        $I->click('Workspaces Module', self::$topBarModuleSelector);

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
        $I->waitForElementVisible('select[name=mass-action]');
        $I->selectOption('select[name=mass-action]', 'Publish');

        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('Publish');

        $I->dontSee(self::$newPageTitle, '#workspace-panel');
    }
}
