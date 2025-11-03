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
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class ContentElementsCest
{
    private string $sidebar = '.sidebar.list-group';

    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->waitForElementVisible('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->click('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-bar-buttons .dropdown-menu');
        $I->click('Layout', '.module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->wait(1);
        $I->click('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->wait(1);
        $I->executeInSelenium(static function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });
        $I->wait(1);
        $I->see('TYPO3 Styleguide Frontend', '.content');
    }

    public function _after(ApplicationTester $I): void
    {
        // Close FE tab again and switch to BE to avoid side effects
        $I->executeInSelenium(static function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            // Avoid closing the main backend tab (holds the webdriver session) if the test failed to open the frontend tab
            // (All subsequent tests would fail with "[Facebook\WebDriver\Exception\InvalidSessionIdException] invalid session id"
            if (count($handles) > 1) {
                $webdriver->close();
                $firstWindow = current($handles);
                $webdriver->switchTo()->window($firstWindow);
            }
        });
    }

    private function contentElementsDataProvider(): array
    {
        return [
            [
                'link' => 'bullets',
                'seeElement' => ['.ce-bullets'],
                'see' => [
                    'Another bullet list',
                    'A bullet list',
                ],
            ],
            [
                'link' => 'div',
                'seeElement' => ['hr.ce-div'],
            ],
            [
                'link' => 'header',
                'seeElement' => ['.frame-type-header'],
                'see' => [
                    'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.',
                ],
            ],
            [
                'link' => 'text',
                'seeElement' => ['.frame-type-text', '.content.col a'],
                'see' => [
                    'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.',
                ],
            ],
            [
                'link' => 'textpic',
                'seeElement' => ['.frame-type-textpic', '.content.col a', '.ce-gallery img'],
                'see' => [
                    'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.',
                ],
            ],
            [
                'link' => 'textmedia',
                'seeElement' => ['.frame-type-textmedia', '.content.col a', '.ce-gallery img'],
                'see' => [
                    'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.',
                ],
            ],
            [
                'link' => 'image',
                'seeElement' => ['.frame-type-image', '.ce-gallery img'],
                'see' => [
                    'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.',
                ],
            ],
            [
                'link' => 'html',
                'seeElement' => ['.frame-type-html', '.content.col a', '.content.col strong'],
                'see' => [
                    'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.',
                ],
            ],
            [
                'link' => 'table',
                'seeElement' => ['.frame-type-table', 'table.table'],
                'see' => [
                    'row4 col4',
                ],
            ],
            [
                'link' => 'shortcut',
                'seeElement' => [
                    '.frame-type-shortcut',
                    '.content.col a',
                ],
                'see' => [
                    'Bacon ipsum dolor sit strong amet capicola jerky pork chop rump shoulder shank. Shankle strip steak pig salami link.',
                ],
            ],
            [
                'link' => 'uploads',
                'seeElement' => [
                    '.frame-type-uploads',
                    '.ce-uploads',
                ],
                'see' => [
                    'bus_lane.jpg',
                    'telephone_box.jpg',
                    'underground.jpg',
                ],
            ],
            [
                'link' => 'menu_categorized_pages',
                'seeElement' => [
                    '.frame-type-menu_categorized_pages ul li',
                ],
                'see' => [
                    'Menu categorized pages',
                ],
            ],
            [
                'link' => 'menu_categorized_content',
                'seeElement' => [
                    '.frame-type-menu_categorized_content ul li',
                ],
                'see' => [
                    'Menu categorized content',
                ],
            ],
            [
                'link' => 'menu_pages',
                'seeElement' => [
                    '.frame-type-menu_pages ul li',
                ],
                'see' => [
                    'Menu pages',
                ],
            ],
            [
                'link' => 'menu_subpages',
                'seeElement' => [
                    '.frame-type-menu_subpages ul li',
                ],
                'see' => [
                    'Menu subpages',
                ],
            ],
            [
                'link' => 'menu_sitemap',
                'seeElement' => [
                    '.frame-type-menu_sitemap ul li',
                ],
                'see' => [
                    'Menu sitemap',
                ],
            ],
            [
                'link' => 'menu_section',
                'seeElement' => [
                    '.frame-type-menu_section ul ul li',
                ],
                'see' => [
                    'Menu section',
                ],
            ],
            [
                'link' => 'menu_abstract',
                'seeElement' => [
                    '.frame-type-menu_abstract ul li a',
                    '.frame-type-menu_abstract ul li p',
                ],
                'see' => [
                    'Menu abstract',
                ],
            ],
            [
                'link' => 'menu_recently_updated',
                'seeElement' => [
                    '.frame-type-menu_recently_updated ul li',
                ],
                'see' => [
                    'Menu recently updated',
                ],
            ],
            [
                'link' => 'menu_related_pages',
                'seeElement' => [
                    '.frame-type-menu_related_pages ul li',
                ],
                'see' => [
                    'Menu related pages',
                ],
            ],
            [
                'link' => 'menu_section_pages',
                'seeElement' => [
                    '.frame-type-menu_section_pages ul ul',
                ],
                'see' => [
                    'Menu section pages',
                ],
            ],
            [
                'link' => 'menu_sitemap_pages',
                'seeElement' => [
                    '.frame-type-menu_sitemap_pages ul li',
                ],
                'see' => [
                    'Menu sitemap pages',
                ],
            ],
        ];
    }

    public function seeAllContentElements(ApplicationTester $I): void
    {
        $I->see('styleguide frontend demo');
        foreach ($this->contentElementsDataProvider() as $contentElement) {
            $I->scrollTo('//a[contains(., "' . $contentElement['link'] . '")]');
            $I->click($contentElement['link'], $this->sidebar);
            foreach ($contentElement['seeElement'] ?? [] as $element) {
                $I->seeElement($element);
            }
            foreach ($contentElement['see'] ?? [] as $text) {
                $I->see($text, '.content.col');
            }
        }
    }
}
