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
class PageModuleCest
{
    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param ApplicationTester $I
     */
    public function checkThatPageModuleHasAHeadline(ApplicationTester $I)
    {
        // Select the root page
        $I->switchToMainFrame();
        $I->click('Page');
        $I->waitForElement('svg .nodes .node');
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_0 text.node-name');
        $I->switchToContentFrame();
        $I->canSee('Web>Page module', 'h4');
    }

    /**
     * @param ApplicationTester $I
     * @param PageTree $pageTree
     */
    public function editPageTitle(ApplicationTester $I, PageTree $pageTree): void
    {
        $currentPageTitle = 'styleguide TCA demo';
        $newPageTitle = 'styleguide TCA demo page';

        $I->switchToMainFrame();
        $I->click('Page');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath([$currentPageTitle]);
        $I->switchToContentFrame();

        // Rename the page
        $this->renamePage($I, $currentPageTitle, $newPageTitle);

        // Now recover the old page title
        $this->renamePage($I, $newPageTitle, $currentPageTitle);
    }

    /**
     * @param ApplicationTester $I
     * @param string $oldTitle
     * @param string $newTitle
     */
    private function renamePage(ApplicationTester $I, string $oldTitle, string $newTitle)
    {
        $editLinkSelector = 'button[data-action="edit"]';
        $inputFieldSelector = 'input[class*="t3js-title-edit-input"]';

        $I->canSee($oldTitle, 'h1');
        $I->moveMouseOver('.t3js-title-inlineedit');

        $I->comment('Activate inline edit of page title');
        $I->seeElement($editLinkSelector);
        $I->click($editLinkSelector);
        $I->seeElement($inputFieldSelector);

        $I->comment('Set new value and save');
        $I->fillField($inputFieldSelector, $newTitle);
        $I->click('button[data-action="submit"]');

        $I->comment('See the new page title');
        $I->waitForElementNotVisible($inputFieldSelector);
        $I->canSee($newTitle, 'h1');
    }
}
