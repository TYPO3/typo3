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
        $I->selectOption('select[name=WebFuncJumpMenu]', $select);
        $I->see($headline, 'h1');
    }
}
