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

namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

use Facebook\WebDriver\Remote\RemoteWebElement;

/**
 * Helper class to interact with the page tree
 */
abstract class AbstractTree
{
    // Selectors
    public static $treeSelector = '';
    public static $treeItemSelector = '.nodes-list > [role="treeitem"]';
    public static $treeItemAnchorSelector = '.node-contentlabel';

    /**
     * @var \AcceptanceTester
     */
    protected $tester;

    /**
     * Waits until tree nodes are rendered
     */
    public function waitForNodes(): void
    {
        $this->tester->waitForElement(static::$treeSelector . ' ' . static::$treeItemSelector, 5);
    }

    /**
     * Open the given hierarchical path in the pagetree and click the last page.
     *
     * Example to open "styleguide -> elements basic" page:
     * [
     *    'styleguide TCA demo',
     *    'elements basic',
     * ]
     *
     * @param string[] $path
     */
    public function openPath(array $path)
    {
        $context = $this->getPageTreeElement();

        $this->waitForNodes();

        // Collapse all opened paths (might be opened due to localstorage)
        do {
            $toggled = false;
            try {
                // collapse last opened node element, that is not the root (=first node)
                $context->findElement(\Facebook\WebDriver\WebDriverBy::xpath('(.//*[position()>1 and @role="treeitem" and */typo3-backend-icon/@identifier="actions-chevron-down"])[last()]/*[@class="node-toggle"]'))->click();
                $toggled = true;
            } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
                // element not found so it may be already opened...
            } catch (\Facebook\WebDriver\Exception\ElementNotVisibleException $e) {
                // element not found so it may be already opened...
            } catch (\Facebook\WebDriver\Exception\ElementNotInteractableException $e) {
                // another possible exception if the chevron isn't there ... depends on facebook driver version
            }
        } while ($toggled);

        foreach ($path as $pageName) {
            $context = $this->ensureTreeNodeIsOpen($pageName, $context);
        }
        $context->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector(static::$treeItemAnchorSelector))->click();
    }

    /**
     * Check if the pagetree is visible end return the web element object
     *
     * @return RemoteWebElement
     */
    public function getPageTreeElement()
    {
        $I = $this->tester;
        $I->switchToIFrame();
        return $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            return $webdriver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector(static::$treeSelector));
        });
    }

    /**
     * Search for an element with the given link text in the provided context.
     *
     * @param string $nodeText
     * @param RemoteWebElement $context
     * @return RemoteWebElement
     */
    protected function ensureTreeNodeIsOpen(string $nodeText, RemoteWebElement $context)
    {
        $I = $this->tester;
        $I->wait(0.1);
        $I->see($nodeText, static::$treeItemSelector);

        /** @var RemoteWebElement $context */
        $context = $I->executeInSelenium(function () use (
            $nodeText,
            $context
        ) {
            return $context->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//*[@class=\'node-name\'][text()=\'' . $nodeText . '\']/../../..'));
        });

        try {
            $context->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.node-toggle > typo3-backend-icon[identifier=\'actions-chevron-right\']'))->click();
        } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
            // element not found so it may be already opened...
        } catch (\Facebook\WebDriver\Exception\ElementNotVisibleException $e) {
            // element not found so it may be already opened...
        } catch (\Facebook\WebDriver\Exception\ElementNotInteractableException $e) {
            // another possible exception if the chevron isn't there ... depends on facebook driver version
        }

        return $context;
    }
}
