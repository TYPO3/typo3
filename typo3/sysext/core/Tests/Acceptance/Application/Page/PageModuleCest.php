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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Page;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * This testcase is used to check if the expected information is found when
 * the page module was opened.
 */
final class PageModuleCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function checkThatPageModuleHasAHeadline(ApplicationTester $I): void
    {
        // Select the root page
        $I->switchToMainFrame();
        $I->click('Page');
        // click on PID=0
        $I->clickWithLeftButton('#typo3-pagetree-treeContainer [role="treeitem"][data-id="0"] .node-contentlabel');
        $I->switchToContentFrame();
        $I->canSee('Please select a page in the page tree to edit page content.');
    }

    public function editPageTitle(ApplicationTester $I, PageTree $pageTree): void
    {
        $oldPageTitle = 'styleguide TCA demo';
        $newPageTitle = 'styleguide TCA demo page';

        $I->switchToMainFrame();
        $I->click('Page');
        $pageTree->openPath([$oldPageTitle]);
        $I->switchToContentFrame();

        $I->canSeeElement('typo3-backend-editable-page-title');
        $I->wait(1);

        // Rename
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('[data-action=\"edit\"]').click()");
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('input').value = '" . $newPageTitle . "'");
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('[data-action=\"save\"]').click()");
        $I->wait(1);
        $changedPageTitle = $I->executeJS("return document.querySelector('typo3-backend-editable-page-title').pageTitle");
        if ($changedPageTitle !== 'styleguide TCA demo page') {
            $I->fail('The current page title "' . $changedPageTitle . '" does not match "styleguide TCA demo page"');
        }

        // Rename back
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('[data-action=\"edit\"]').click()");
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('input').value = '" . $oldPageTitle . "'");
        $I->executeJS("document.querySelector('typo3-backend-editable-page-title').shadowRoot.querySelector('[data-action=\"save\"]').click()");
        $I->wait(1);
        $changedPageTitle = $I->executeJS("return document.querySelector('typo3-backend-editable-page-title').pageTitle");
        if ($changedPageTitle !== 'styleguide TCA demo') {
            $I->fail('The current page title "' . $changedPageTitle . '" does not match "styleguide TCA demo"');
        }
    }
}
