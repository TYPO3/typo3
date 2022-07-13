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

use Codeception\Example;
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
        $url = $this->getCurrentURL($I);

        // Add Sitemap parameter to URL
        $I->amOnUrl($url . '?type=1533906435');
    }

    /**
     * @param ApplicationTester $I
     * @param int $attempt
     * @return string
     */
    private function getCurrentURL(ApplicationTester $I, int $attempt = 1): string
    {
        $url = $I->executeInSelenium(function (RemoteWebDriver $webdriver) {
            return $webdriver->getCurrentURL();
        });

        if ($attempt > 4) {
            return $url ?? '';
        }

        if (!$url || str_contains($url, 'about:blank')) {
            $I->wait(0.5);
            $url = $this->getCurrentURL($I, $attempt + 1);
        }

        return $url;
    }

    /**
     * @dataProvider sitemapDataProvider
     * @param ApplicationTester $I
     */
    public function seeSitemapXml(ApplicationTester $I, Example $testData): void
    {
        $I->see('TYPO3 XML Sitemap');
        $I->see('sitemap=pages');
        $I->see('type=1533906435');

        $I->amGoingTo('See sitemap pages details');
        $I->click('a');

        $I->see($testData['slug']);
        $priority = $this->getTableColumn($I, $testData['slug'])->getText();
        $I->assertIsNumeric($priority);
    }

    /**
     * @return array
     */
    protected function sitemapDataProvider(): array
    {
        return [
            ['slug' => '/bullets'],
            ['slug' => '/div'],
            ['slug' => '/header'],
            ['slug' => '/text'],
            ['slug' => '/textpic'],
            ['slug' => '/textmedia'],
            ['slug' => '/image'],
            ['slug' => '/html'],
            ['slug' => '/table'],
            ['slug' => '/felogin-login'],
            ['slug' => '/form-formframework'],
            ['slug' => '/list'],
            ['slug' => '/shortcut'],
            ['slug' => '/uploads'],
            ['slug' => '/menu-categorized-pages'],
            ['slug' => '/menu-categorized-content'],
            ['slug' => '/menu-pages'],
            ['slug' => '/menu-subpages'],
            ['slug' => '/menu-sitemap'],
            ['slug' => '/menu-section'],
            ['slug' => '/menu-abstract'],
            ['slug' => '/menu-recently-updated'],
            ['slug' => '/menu-related-pages'],
            ['slug' => '/menu-section-pages'],
            ['slug' => '/menu-sitemap-pages'],
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
