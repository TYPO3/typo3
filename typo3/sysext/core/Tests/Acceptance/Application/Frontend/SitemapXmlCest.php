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

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use Facebook\WebDriver\WebDriverBy;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class SitemapXmlCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->waitForElementVisible('.module-docheader-bar-column-left a:first-child');
        $I->wait(1);
        $I->waitForElementNotVisible('#nprogress', 120);
        $dataDispatchArgs = $I->grabAttributeFrom('.module-docheader-bar-column-left a:first-child', 'data-dispatch-args');
        $url = json_decode($dataDispatchArgs, false, 512, JSON_THROW_ON_ERROR);
        // Add Sitemap parameter to URL
        $I->amOnPage(str_replace('/typo3temp/var/tests/acceptance', '', $url[0]) . '?type=1533906435');
    }

    private function sitemapDataProvider(): array
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
            ['slug' => '/indexedsearch-pi2'],
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

    #[DataProvider('sitemapDataProvider')]
    public function seeSitemapXml(ApplicationTester $I, Example $testData): void
    {
        $I->see('TYPO3 XML Sitemap');
        $I->see('tx_seo%5Bsitemap%5D=pages');
        $I->see('type=1533906435');

        $I->amGoingTo('See sitemap pages details');
        $I->click('a');

        $I->see($testData['slug']);
        $priority = $this->getTableColumn($I, $testData['slug'])->getText();
        $I->assertIsNumeric($priority);
    }

    /**
     * Find text by given slug part
     */
    private function getTableColumn(ApplicationTester $I, string $slug, int $sibling = 3): RemoteWebElement
    {
        $I->comment('Get context for table row "' . $slug . '"');
        return $I->executeInSelenium(
            function (RemoteWebDriver $webDriver) use ($slug, $sibling) {
                return $webDriver->findElement(
                    WebDriverBy::xpath(
                        '//a[contains(text(),"' . $slug . '")]/ancestor::td/following-sibling::td[' . $sibling . ']'
                    )
                );
            }
        );
    }
}
