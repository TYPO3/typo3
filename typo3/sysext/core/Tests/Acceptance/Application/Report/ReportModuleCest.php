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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Report;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\RemoteWebElement;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests concerning Reports Module
 */
final class ReportModuleCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->click('[data-modulemenu-identifier="system_reports"]');
        $I->switchToContentFrame();
    }

    public function seeStatusReport(ApplicationTester $I): void
    {
        $this->goToPageAndSeeHeadline($I, 'Status Report', 'Status Report');
        $I->see('TYPO3 System', 'h2');
    }

    private function recordStatisticsDataProvider(): array
    {
        return [
            [
                'name' => 'Total number of default language pages',
                'count' => 84,
            ],
            [
                'name' => 'Total number of translated pages',
                'count' => 132,
            ],
            [
                'name' => 'Marked-deleted pages',
                'count' => 0,
            ],
            [
                'name' => 'Hidden pages',
                'count' => 1,
            ],
            [
                'name' => 'Standard',
                'count' => 1,
            ],
            [
                'name' => 'Backend User Section',
                'count' => 0,
            ],
            [
                'name' => 'Link to External URL',
                'count' => 0,
            ],
        ];
    }

    #[DataProvider('recordStatisticsDataProvider')]
    public function seeRecordStatistics(ApplicationTester $I, Example $testData): void
    {
        $this->goToPageAndSeeHeadline($I, 'Record Statistics', 'Record Statistics');

        $count = $this->getCountByRowName($I, $testData['name'])->getText();
        // Use >= here to make sure we don't get in trouble with other tests creating db entries
        $I->assertGreaterThanOrEqual($testData['count'], $count);
    }

    private function reportsMenuDataProvider(): array
    {
        return [
            ['title' => 'Status Report', 'shortDescription' => 'Get a status report about your site\'s operation and any detected problems.'],
            ['title' => 'Record Statistics', 'shortDescription' => 'Shows database record statistics'],
        ];
    }

    #[DataProvider('reportsMenuDataProvider')]
    public function seeReportSubModules(ApplicationTester $I, Example $exampleData): void
    {
        $I->amGoingTo('see card for ' . $exampleData['title']);
        $I->waitForElementVisible('.card-container');
        $I->see($exampleData['title'], '.card-title');
        $I->see($exampleData['shortDescription'], '.card-subtitle');
        $I->see('Open module', '.card-footer');

        $I->amGoingTo('check aria-label contains module name for accessibility');
        // Find the card containing the specific title and verify its button has proper aria-label
        $cardSelector = '//div[@class="card card-size-small" and .//h2[contains(text(), "' . $exampleData['title'] . '")]]';
        $buttonSelector = $cardSelector . '//a[@aria-label="Open ' . $exampleData['title'] . ' module"]';
        $I->seeElement($buttonSelector);

        $I->amGoingTo('open ' . $exampleData['title'] . ' module via card button');
        $I->click('.btn', $cardSelector);
        $I->waitForText($exampleData['title']);
        $I->see($exampleData['title'], 'h1');
    }

    /**
     * Find count of table row by name
     */
    private function getCountByRowName(ApplicationTester $I, string $rowName, int $sibling = 1): RemoteWebElement
    {
        $I->comment('Get context for table row "' . $rowName . '"');
        return $I->executeInSelenium(
            static function (RemoteWebDriver $webDriver) use ($rowName, $sibling) {
                return $webDriver->findElement(
                    \Facebook\WebDriver\WebDriverBy::xpath(
                        '//td[contains(text(),"' . $rowName . '")]/following-sibling::td[' . $sibling . ']'
                    )
                );
            }
        );
    }

    private function goToPageAndSeeHeadline(ApplicationTester $I, string $select, string $headline): void
    {
        $I->click('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-bar-buttons .dropdown-menu');
        $I->click($select, '.module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->see($headline, 'h1');
    }
}
