<?php

declare(strict_types=1);

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\PageTree\DragAndDrop;

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

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\Mouse;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Page tree related tests for page creation using drag and drop.
 */
class PageCreationWithDragAndDropCest
{
    protected static string $treeNode = '#typo3-pagetree-tree .nodes .node';
    protected static string $dragNode = '#typo3-pagetree-toolbar .svg-toolbar__drag-node';
    protected static string $nodeEditInput = '.node-edit';

    protected PageTree $pageTree;

    /**
     * Open list module of styleguide elements basic page
     *
     * @param ApplicationTester $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $this->pageTree = $pageTree;
        $I->useExistingSession('admin');
        $I->click('List');
        $I->waitForElement(static::$treeNode, 5);
        $I->waitForElement(static::$dragNode, 5);
        $this->pageTree->openPath(['styleguide TCA demo']);
        // Wait until DOM actually rendered everything
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
    }

    /**
     * Check drag and drop for new pages into nodes without children.
     *
     * @param ApplicationTester $I
     */
    public function dragAndDropNewPageInNodeWithoutChildren(ApplicationTester $I): void
    {
        $I->amGoingTo('create a new page below page without child pages using drag and drop');
        $this->pageTree->dragAndDropNewPage('staticdata', static::$dragNode, static::$nodeEditInput);
    }

    /**
     * Check drag and drop for new pages into nodes with children.
     *
     * @param ApplicationTester $I
     * @param PageTree $pageTree
     */
    public function dragAndDropNewPageInNodeWithChildren(ApplicationTester $I): void
    {
        $I->amGoingTo('create a new page below page with child pages using drag and drop');
        $this->pageTree->dragAndDropNewPage('styleguide TCA demo', static::$dragNode, static::$nodeEditInput);
    }

    /**
     * Check drag and drop for new pages and quit page creation using Escape key.
     *
     * @param ApplicationTester $I
     * @param Mouse $mouse
     * @throws \Exception
     */
    public function dragAndDropNewPageAndQuitPageCreation(ApplicationTester $I, Mouse $mouse): void
    {
        $mouse->dragAndDrop(static::$dragNode, $this->pageTree->getPageXPathByPageName('elements basic'));

        $I->seeElement(static::$nodeEditInput);
        $I->pressKey(static::$nodeEditInput, WebDriverKeys::ESCAPE);
        $I->waitForElementNotVisible(static::$nodeEditInput, 5);
    }

    /**
     * Check drag and drop for new pages and quit page creation using empty page title.
     *
     * @param ApplicationTester $I
     * @param Mouse $mouse
     * @throws \Exception
     */
    public function dragAndDropNewPageAndLeavePageTitleEmpty(ApplicationTester $I, Mouse $mouse): void
    {
        $mouse->dragAndDrop(static::$dragNode, $this->pageTree->getPageXPathByPageName('staticdata'));

        $I->seeElement(static::$nodeEditInput);

        // We can't use $I->fillField() here since this sends a clear() to the element
        // which drops the node creation in the tree. So we do it manually with selenium.
        $nodeEditInput = static::$nodeEditInput;
        $element = $I->executeInSelenium(static function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use ($nodeEditInput) {
            return $webdriver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($nodeEditInput));
        });
        $element->sendKeys('');

        $I->pressKey(static::$nodeEditInput, WebDriverKeys::ENTER);
        $I->waitForElementNotVisible(static::$nodeEditInput, 5);
    }
}
