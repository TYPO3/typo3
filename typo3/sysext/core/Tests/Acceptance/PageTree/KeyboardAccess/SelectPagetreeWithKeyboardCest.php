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

namespace TYPO3\CMS\Core\Tests\Acceptance\PageTree\KeyboardAccess;

use Exception;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Page and page tree related tests.
 */
class SelectPagetreeWithKeyboardCest
{
    /**
     * Open list module of styleguide elements basic page
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     * @throws Exception
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $pageTree->openPath(['Root']);
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
    }

    /**
     * check selecting the next key in the page tree and open it using Enter
     *
     * @param BackendTester $I
     */
    public function focusPageWithDownKeyAndOpenItWithEnter(BackendTester $I)
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->assertEquals(
            'identifier-0_2',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::ENTER);
        $I->switchToContentFrame();
        $I->see('Dummy 1-2');
    }

    /**
     * check selecting the next key in the page tree and open it using Enter
     *
     * @param BackendTester $I
     */
    public function focusPageWithDownAndUpKey(BackendTester $I)
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->assertEquals(
            'identifier-0_3',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::UP);
        $I->assertEquals(
            'identifier-0_2',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
    }

    /**
     * Expand a subtree using keyboard keys
     *
     * @param BackendTester $I
     */
    public function expandSubtreeWithRightArrow(BackendTester $I)
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->amGoingTo('use keyboard to navigate through the tree');
        for ($times = 0; $times < 3; $times++) {
            $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        }
        $I->amGoingTo('check if the parent key is selected and child is not visible');
        $I->assertEquals(
            'identifier-0_4',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::RIGHT);
        $I->amGoingTo('check if parent is still selected and child is visible');
        $I->assertEquals(
            'identifier-0_4',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->seeElement('#identifier-0_5');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::RIGHT);
        $I->amGoingTo('check if first childnode is selected');
        $I->assertEquals(
            'identifier-0_5',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::RIGHT);
        $I->amGoingTo('check if first childnode is still selected');
        $I->assertEquals(
            'identifier-0_5',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        $I->amGoingTo('check if second childnode is still selected');
        $I->assertEquals(
            'identifier-0_6',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
    }

    /**
     * Expand a subtree using keyboard keys
     *
     * @param BackendTester $I
     */
    public function collapseSubtreeWithLeftArrow(BackendTester $I)
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->assertEquals(
            'identifier-0_1',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->seeElement('#identifier-0_2');
        $I->amGoingTo('collapse the current tree using left key');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::LEFT);
        $I->assertEquals(
            'identifier-0_1',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->cantSeeElement('#identifier-0_2');
        $I->amGoingTo('go to parent of the current collapsed node using left key');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::LEFT);
        $I->amGoingTo('check if parent (root) is selected and child is visible');
        $I->assertEquals(
            'identifier-0_0',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
        $I->canSeeElement('#identifier-0_1');
    }

    /**
     * Check if the END key is working
     *
     * @param BackendTester $I
     */
    public function focusLastPageTreeItemWithEndKey(BackendTester $I)
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::END);
        $I->assertEquals(
            'identifier-0_50',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
    }

    /**
     * Check if the Home key is working
     *
     * @param BackendTester $I
     */
    public function focusFirstPageTreeItemWithHomeKey(BackendTester $I)
    {
        $I->seeElement('#typo3-pagetree-tree [tabindex="0"]');
        for ($times = 0; $times < 15; $times++) {
            $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::DOWN);
        }
        $I->assertEquals(
            'identifier-0_21',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );

        $I->pressKey('#typo3-pagetree-tree [tabindex="0"]', WebDriverKeys::HOME);
        $I->assertEquals(
            'identifier-0_0',
            $I->grabAttributeFrom('#typo3-pagetree-tree [tabindex="0"]', 'id')
        );
    }
}
