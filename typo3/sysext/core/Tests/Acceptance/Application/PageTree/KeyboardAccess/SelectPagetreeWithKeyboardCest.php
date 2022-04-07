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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\PageTree\KeyboardAccess;

use Exception;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Page and page tree related tests.
 */
class SelectPagetreeWithKeyboardCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @throws Exception
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['Root']);
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $I->waitForElement('#identifier-0_1[tabindex="0"]', 5);
    }

    /**
     * check selecting the next key in the page tree and open it using Enter
     */
    public function focusPageWithDownKeyAndOpenItWithEnter(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->assertEquals(
            'Dummy 1-2',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::ENTER);
        $I->switchToContentFrame();
        $I->see('Dummy 1-2');
    }

    /**
     * check selecting the next key in the page tree and open it using Enter
     */
    public function focusPageWithDownAndUpKey(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->assertEquals(
            'Dummy 1-3',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::UP);
        $I->assertEquals(
            'Dummy 1-2',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
    }

    /**
     * Expand a subtree using keyboard keys
     */
    public function expandSubtreeWithRightArrow(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->amGoingTo('use keyboard to navigate through the tree');
        for ($times = 0; $times < 3; $times++) {
            $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        }
        $I->amGoingTo('check if the parent key is selected and child is not visible');
        $I->assertEquals(
            'Dummy 1-4',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->amGoingTo('check if parent is still selected and child is visible');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::RIGHT);
        $I->assertEquals(
            'Dummy 1-4',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->see('Dummy 1-4-5');
        $I->amGoingTo('check if first child node is selected');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::RIGHT);
        $I->assertEquals(
            'Dummy 1-4-5',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::RIGHT);
        $I->amGoingTo('check if first child node is still selected');
        $I->assertEquals(
            'Dummy 1-4-5',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->amGoingTo('check if second child node is selected');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->assertEquals(
            'Dummy 6',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
    }

    /**
     * Expand a subtree using keyboard keys
     */
    public function collapseSubtreeWithLeftArrow(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->assertEquals(
            'Root',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->see('Dummy 1-2');
        $I->amGoingTo('collapse the current tree using left key');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::LEFT);
        $I->assertEquals(
            'Root',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->cantSee('Dummy 1-2');
        $I->amGoingTo('go to parent of the current collapsed node using left key');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::LEFT);
        $I->amGoingTo('check if parent (root) is selected and child is visible');
        $I->assertEquals(
            'New TYPO3 site',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->canSee('Root');
        $I->canSee('styleguide TCA demo');
    }

    /**
     * Check if the END key is working
     */
    public function focusLastPageTreeItemWithEndKey(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->assertEquals(
            'Root',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::END);
        $I->assertEquals(
            'Dummy 1-41',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::UP);
        $I->assertEquals(
            'Dummy 1-40',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
    }

    /**
     * Check if the Home key is working
     */
    public function focusFirstPageTreeItemWithHomeKey(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        for ($times = 0; $times < 15; $times++) {
            $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        }
        $I->assertEquals(
            'Dummy 1-21',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );

        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::HOME);
        $I->assertEquals(
            'New TYPO3 site',
            $I->grabTextFrom('#typo3-pagetree-tree [tabindex="0"]')
        );
    }
}
