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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Info;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests concerning Info Module
 */
final class InfoModuleCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('[data-modulemenu-identifier="web_info"]');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
        $I->see('Info', 'h1');
        $I->see('The Web>Info module is focused on statistic information about pages.');
    }

    private function infoMenuDataProvider(): array
    {
        return [
            ['title' => 'Pagetree Overview', 'description' => 'View page records and settings in a tree structure with detailed metadata.'],
            ['title' => 'Localization Overview', 'description' => 'Check translation status and manage localized content for pages.'],
        ];
    }

    #[DataProvider('infoMenuDataProvider')]
    public function seeInfoSubModules(ApplicationTester $I, Example $exampleData): void
    {
        $I->amGoingTo('see card for ' . $exampleData['title']);
        $I->waitForElementVisible('.card-container');
        $I->see($exampleData['title'], '.card-title');
        $I->see($exampleData['description'], '.card-text');
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
}
