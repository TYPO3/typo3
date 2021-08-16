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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Frontend;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests concerning Reports Module
 */
class SitemapXmlCest
{
    protected string $sidebar = '.sidebar.list-group';

    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->click('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->executeInSelenium(function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });

        // Get current url
        $url = $I->executeInSelenium(function (RemoteWebDriver $webdriver) {
            return $webdriver->getCurrentURL();
        });

        // Add Sitemap parameter to URL
        $I->amOnUrl($url . '?type=1533906435');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeSitemapXml(ApplicationTester $I): void
    {
        $I->see('TYPO3 XML Sitemap');
        $I->see('sitemap=pages');
        $I->see('type=1533906435');

        $I->amGoingTo('See sitemap pages details');
        $I->click('a');

        foreach ($this->sitemapDataProvider() as $slug) {
            $I->see($slug);
            $priority = $this->getTableColumn($I, $slug)->getText();
            $I->assertIsNumeric($priority);
        }
    }

    /**
     * @return array
     */
    protected function sitemapDataProvider(): array
    {
        return [
            '/bullets',
            '/div',
            '/header',
            '/text',
            '/textpic',
            '/textmedia',
            '/image',
            '/html',
            '/table',
            '/felogin-login',
            '/form-formframework',
            '/list',
            '/shortcut',
            '/uploads',
            '/menu-categorized-pages',
            '/menu-categorized-content',
            '/menu-pages',
            '/menu-subpages',
            '/menu-sitemap',
            '/menu-section',
            '/menu-abstract',
            '/menu-recently-updated',
            '/menu-related-pages',
            '/menu-section-pages',
            '/menu-sitemap-pages',
        ];
    }

    /**
     * Find text by given slug part
     *
     * @param ApplicationTester $I
     * @param string $slug
     * @param int $sibling
     * @return RemoteWebElement
     */
    protected function getTableColumn(ApplicationTester $I, string $slug, int $sibling = 3): RemoteWebElement
    {
        $I->comment('Get context for table row "' . $slug . '"');
        return $I->executeInSelenium(
            function (RemoteWebDriver $webDriver) use ($slug, $sibling) {
                return $webDriver->findElement(
                    \Facebook\WebDriver\WebDriverBy::xpath(
                        '//a[contains(text(),"' . $slug . '")]/ancestor::td/following-sibling::td[' . $sibling . ']'
                    )
                );
            }
        );
    }
}
