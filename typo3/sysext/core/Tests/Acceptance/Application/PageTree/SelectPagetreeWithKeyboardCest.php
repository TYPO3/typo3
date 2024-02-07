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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\PageTree;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Page and page tree related tests.
 */
final class SelectPagetreeWithKeyboardCest
{
    /**
     * Open list module of styleguide elements basic page
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $pageTree->openPath(['Root']);
        $I->waitForElement('#typo3-pagetree-treeContainer [role="treeitem"][data-id="1"]', 5);
    }

    /**
     * check selecting the next key in the page tree and open it using Enter
     */
    public function focusPageWithDownKeyAndOpenItWithEnter(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [role="treeitem"].node-selected');
        $I->pressKey('#typo3-pagetree-tree [role="treeitem"].node-selected', WebDriverKeys::DOWN);
        $I->assertEquals(
            'Dummy 1-2',
            $this->grabFocussedText($I)
        );
        $this->sendKey($I, WebDriverKeys::ENTER);
        $I->switchToContentFrame();
        $I->see('Dummy 1-2');
    }

    /**
     * check selecting the next key in the page tree and open it using Enter
     */
    public function focusPageWithDownAndUpKey(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [role="treeitem"].node-selected');
        $I->pressKey('#typo3-pagetree-tree [role="treeitem"].node-selected', WebDriverKeys::DOWN);
        $this->sendKey($I, WebDriverKeys::DOWN);
        $I->assertEquals(
            'Dummy 1-3',
            $this->grabFocussedText($I)
        );
        $this->sendKey($I, WebDriverKeys::UP);
        $I->assertEquals(
            'Dummy 1-2',
            $this->grabFocussedText($I)
        );
    }

    /**
     * Expand a subtree using keyboard keys
     */
    public function expandSubtreeWithRightArrow(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [role="treeitem"].node-selected');
        $I->amGoingTo('use keyboard to navigate through the tree');
        $I->pressKey('#typo3-pagetree-tree [role="treeitem"].node-selected', WebDriverKeys::DOWN);
        $this->sendKey($I, WebDriverKeys::DOWN);
        $this->sendKey($I, WebDriverKeys::DOWN);
        $I->amGoingTo('check if the parent key is selected and child is not visible');
        $I->assertEquals(
            'Dummy 1-4',
            $this->grabFocussedText($I)
        );
        $I->amGoingTo('check if parent is still selected and child is visible');
        $this->sendKey($I, WebDriverKeys::RIGHT);
        $I->assertEquals(
            'Dummy 1-4',
            $this->grabFocussedText($I)
        );
        $I->see('Dummy 1-4-5');
        $I->amGoingTo('check if first child node is selected');
        $this->sendKey($I, WebDriverKeys::RIGHT);
        $I->assertEquals(
            'Dummy 1-4-5',
            $this->grabFocussedText($I)
        );
        $this->sendKey($I, WebDriverKeys::RIGHT);
        $I->amGoingTo('check if first child node is still selected');
        $I->assertEquals(
            'Dummy 1-4-5',
            $this->grabFocussedText($I)
        );
        $I->amGoingTo('check if second child node is selected');
        $this->sendKey($I, WebDriverKeys::DOWN);
        $I->assertEquals(
            'Dummy 6',
            $this->grabFocussedText($I)
        );
    }

    /**
     * Expand a subtree using keyboard keys
     */
    public function collapseSubtreeWithLeftArrow(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [role="treeitem"].node-selected');
        $I->assertEquals(
            'Root',
            $I->grabTextFrom('#typo3-pagetree-tree [role="treeitem"].node-selected')
        );
        $I->see('Dummy 1-2');
        $I->amGoingTo('collapse the current tree using left key');
        $I->pressKey('#typo3-pagetree-tree [role="treeitem"].node-selected', WebDriverKeys::LEFT);
        $I->assertEquals(
            'Root',
            $this->grabFocussedText($I)
        );
        $I->cantSee('Dummy 1-2');
        $I->amGoingTo('go to parent of the current collapsed node using left key');
        $this->sendKey($I, WebDriverKeys::LEFT);
        $I->amGoingTo('check if parent (root) is selected and child is visible');
        $I->assertEquals(
            'New TYPO3 site',
            $this->grabFocussedText($I)
        );
        $I->canSee('Root');
        $I->canSee('styleguide TCA demo');
    }

    /**
     * Check if the Home key is working
     */
    public function focusFirstPageTreeItemWithHomeKey(ApplicationTester $I): void
    {
        $I->seeElement('#typo3-pagetree-tree [role="treeitem"].node-selected');
        $I->pressKey('#typo3-pagetree-tree [role="treeitem"].node-selected', WebDriverKeys::DOWN);
        for ($times = 0; $times < 14; $times++) {
            $this->sendKey($I, WebDriverKeys::DOWN);
        }
        $I->assertEquals(
            'Dummy 1-21',
            $this->grabFocussedText($I)
        );

        $this->sendKey($I, WebDriverKeys::HOME);
        $I->assertEquals(
            'New TYPO3 site',
            $this->grabFocussedText($I)
        );
    }

    private function getFocusedNode(ApplicationTester $I): ?WebDriverBy
    {
        $treeId = $I->executeJS('return document.querySelector(\'#typo3-pagetree-tree [role="treeitem"]:focus\')?.getAttribute("data-tree-id")');
        if ($treeId !== null) {
            return WebDriverBy::xpath('//*[@id="typo3-pagetree-tree"]//*[@role="treeitem" and @data-tree-id="' . $treeId . '"]');
        }
        return null;
    }

    private function sendKey(ApplicationTester $I, string $key): void
    {
        $I->pressKey($this->getFocusedNode($I), $key);
    }

    private function grabFocussedText(ApplicationTester $I): string
    {
        return $I->grabTextFrom($this->getFocusedNode($I));
    }
}
