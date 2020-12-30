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

namespace TYPO3\CMS\Core\Tests\Acceptance\PageTree\DragAndDrop;

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\Mouse;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Page tree related tests for page creation using drag and drop.
 */
class PageCreationWithDragAndDropCest
{
    /**
     * @var string
     */
    protected static $treeNode = '#typo3-pagetree-tree .nodes .node';

    /**
     * @var string
     */
    protected static $dragNode = '#svg-toolbar .svg-toolbar__drag-node';

    /**
     * @var string
     */
    protected static $nodeEditInput = '.node-edit';

    /**
     * Open list module of styleguide elements basic page
     *
     * @param BackendTester $I
     * @param PageTree $pageTree
     * @throws \Exception
     */
    public function _before(BackendTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('List');
        $pageTree->openPath(['Root']);
        $I->waitForElement(static::$treeNode, 5);
        $I->waitForElement(static::$dragNode, 5);
    }

    /**
     * Check drag and drop for new pages into nodes without children.
     *
     * @param BackendTester $I
     * @param Mouse $mouse
     * @throws \Exception
     */
    public function dragAndDropNewPageInNodeWithoutChildren(BackendTester $I, Mouse $mouse): void
    {
        $I->amGoingTo('create a new page below pid=21 (no child pages) using drag and drop');
        $this->dragAndDropNewPage($I, $mouse, 21);
    }

    /**
     * Check drag and drop for new pages into nodes with children.
     *
     * @param BackendTester $I
     * @param Mouse $mouse
     * @throws \Exception
     */
    public function dragAndDropNewPageInNodeWithChildren(BackendTester $I, Mouse $mouse): void
    {
        $I->amGoingTo('create a new page below pid=10 (has child pages) using drag and drop');
        $this->dragAndDropNewPage($I, $mouse, 10);
    }

    /**
     * Check drag and drop for new pages and quit page creation using Escape key.
     *
     * @param BackendTester $I
     * @param Mouse $mouse
     * @throws \Exception
     */
    public function dragAndDropNewPageAndQuitPageCreation(BackendTester $I, Mouse $mouse): void
    {
        $mouse->dragAndDrop(static::$dragNode, $this->getPageIdentifier(22));

        $I->seeElement(static::$nodeEditInput);
        $I->pressKey(static::$nodeEditInput, WebDriverKeys::ESCAPE);
        $I->waitForElementNotVisible(static::$nodeEditInput, 5);
    }

    /**
     * Check drag and drop for new pages and quit page creation using empty page title.
     *
     * @param BackendTester $I
     * @param Mouse $mouse
     * @throws \Exception
     */
    public function dragAndDropNewPageAndLeavePageTitleEmpty(BackendTester $I, Mouse $mouse): void
    {
        $mouse->dragAndDrop(static::$dragNode, $this->getPageIdentifier(22));

        $I->seeElement(static::$nodeEditInput);
        $I->fillField(static::$nodeEditInput, '');
        $I->pressKey(static::$nodeEditInput, WebDriverKeys::ENTER);
        $I->waitForElementNotVisible(static::$nodeEditInput, 5);
    }

    /**
     * Perform drag and drop for a new page into the given target page.
     *
     * @param BackendTester $I
     * @param Mouse $mouse
     * @param int $targetPageId
     * @throws \Exception
     */
    protected function dragAndDropNewPage(BackendTester $I, Mouse $mouse, int $targetPageId): void
    {
        $target = $this->getPageIdentifier($targetPageId);
        $pageTitle = sprintf('Dummy 1-%d-new', $targetPageId);

        $mouse->dragAndDrop(static::$dragNode, $target);

        $I->seeElement(static::$nodeEditInput);
        $I->fillField(static::$nodeEditInput, $pageTitle);
        $I->pressKey(static::$nodeEditInput, WebDriverKeys::ENTER);
        $I->waitForElementNotVisible(static::$nodeEditInput);
        $I->see($pageTitle);
    }

    /**
     * Get node identifier of given page.
     *
     * @param int $pageId
     * @return string
     */
    protected function getPageIdentifier(int $pageId): string
    {
        return '#identifier-0_' . $pageId;
    }
}
