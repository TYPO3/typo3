<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Page;

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

use AcceptanceTester;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;

/**
 * Helper class to interact with the page tree
 */
class PageTree
{
    // Selectors
    public static $pageTreeFrameSelector = '#typo3-pagetree';
    public static $pageTreeSelector = '#typo3-pagetree-treeContainer';
    public static $treeItemSelector = '.x-tree-node-ct > .x-tree-node';
    public static $treeItemAnchorSelector = '.x-tree-node-anchor';

    /**
     * @var AcceptanceTester
     */
    protected $tester;

    /**
     * @param AcceptanceTester $I
     */
    public function __construct(\AcceptanceTester $I)
    {
        $this->tester = $I;
    }

    /**
     * Open the given hierarchical path in the pagetree and click the last page.
     *
     * Example to open "styleuide -> elements basic" page:
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
        foreach ($path as $pageName) {
            $context = $this->ensureTreeNodeIsOpen($pageName, $context);
        }
        $context->findElement(\WebDriverBy::cssSelector(self::$treeItemAnchorSelector))->click();
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
            return $webdriver->findElement(\WebDriverBy::cssSelector(self::$pageTreeSelector));
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
        $I->see($nodeText, self::$treeItemSelector);

        /** @var RemoteWebElement $context */
        $context = $I->executeInSelenium(function () use ($nodeText, $context
        ) {
            return $context->findElement(\WebDriverBy::linkText($nodeText))->findElement(
                WebDriverBy::xpath('ancestor::li[@class="x-tree-node"][1]')
            );
        });

        try {
            $context->findElement(\WebDriverBy::cssSelector('.x-tree-elbow-end-plus'))->click();
        } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
            // element not found so it may be already opened...
        }

        return $context;
    }
}
