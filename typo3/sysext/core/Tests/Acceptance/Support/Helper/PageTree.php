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

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\TestingFramework\Core\Acceptance\Helper\AbstractPageTree;

/**
 * @see AbstractPageTree
 */
class PageTree extends AbstractPageTree
{
    private Mouse $mouse;

    /**
     * Inject our core AcceptanceTester actor into PageTree
     *
     * @param ApplicationTester $I
     */
    public function __construct(ApplicationTester $I, Mouse $mouse)
    {
        $this->tester = $I;
        $this->mouse = $mouse;
    }

    /**
     * Waits until tree nodes are rendered
     */
    public function waitForNodes(): void
    {
        $this->tester->waitForElement(static::$pageTreeSelector . ' ' . static::$treeItemSelector, 5);
    }

    public function closeSecondLevelPaths(): void
    {
        $context = $this->getPageTreeElement();

        $this->waitForNodes();

        // Collapse all opened paths (might be opened due to localstorage)
        do {
            $toggled = false;
            try {
                // collapse last opened node element, that is not the root (=first node)
                $context->findElement(\Facebook\WebDriver\WebDriverBy::xpath('(.//*[position()>1 and @role="treeitem" and count(*/*[@class="chevron expanded"]) > 0])[last()]/*[@class="toggle"]'))->click();
                $toggled = true;
            } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
                // element not found so it may be already opened...
            } catch (\Facebook\WebDriver\Exception\ElementNotVisibleException $e) {
                // element not found so it may be already opened...
            } catch (\Facebook\WebDriver\Exception\ElementNotInteractableException $e) {
                // another possible exception if the chevron isn't there ... depends on facebook driver version
            }
        } while ($toggled);
    }

    public function openPath(array $path): void
    {
        $this->closeSecondLevelPaths();
        parent::openPath($path);

        // pagetree has 300ms timeout for double click detection, wait 350ms to wait for the click to have happened
        $this->tester->wait(0.35);
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
        $context = $I->executeInSelenium(function () use (
            $nodeText,
            $context
        ) {
            return $context->findElement(\Facebook\WebDriver\WebDriverBy::xpath('//*[text()=\'' . $nodeText . '\']/..'));
        });

        try {
            if ($context->getAttribute('aria-expanded') === 'false') {
                $context->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector('.toggle[visibility="visible"]'))->click();
            }
        } catch (\Facebook\WebDriver\Exception\NoSuchElementException $e) {
            // element not found so it may be already opened...
        } catch (\Facebook\WebDriver\Exception\ElementNotVisibleException $e) {
            // element not found so it may be already opened...
        } catch (\Facebook\WebDriver\Exception\ElementNotInteractableException $e) {
            // another possible exception if the chevron isn't there ... depends on facebook driver version
        }

        return $context;
    }

    /**
     * Perform drag and drop for a new page into the given target page.
     */
    public function dragAndDropNewPage(string $pageName, string $dragNode, string $nodeEditInput): void
    {
        $target = $this->getPageXPathByPageName($pageName);
        $pageTitle = sprintf('Dummy 1-%s-new', $pageName);

        $this->mouse->dragAndDrop($dragNode, $target);

        $this->tester->seeElement($nodeEditInput);

        // Change the new page title.
        // We can't use $I->fillField() here since this sends a clear() to the element
        // which drops the node creation in the tree. So we do it manually with selenium.
        $element = $this->tester->executeInSelenium(static function (RemoteWebDriver $webdriver) use ($nodeEditInput) {
            return $webdriver->findElement(WebDriverBy::cssSelector($nodeEditInput));
        });
        $element->sendKeys($pageTitle);

        $this->tester->pressKey($nodeEditInput, WebDriverKeys::ENTER);
        $this->tester->waitForElementNotVisible($nodeEditInput);
        $this->tester->see($pageTitle);
    }

    /**
     * Get node identifier of given page.
     *
     * @param string $pageName
     * @return string
     */
    public function getPageXPathByPageName(string $pageName): string
    {
        return '//*[text()=\'' . $pageName . '\']';
    }
}
