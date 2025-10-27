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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Integrations;

use Codeception\Attribute\DataProvider;
use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests concerning Integrations Module
 */
final class IntegrationsModuleCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('[data-modulemenu-identifier="integrations"]');
        $I->switchToContentFrame();
        $I->see('Integrations', 'h1');
        $I->see('This is the central hub for connecting TYPO3 with the world.');
    }

    private function integrationsMenuDataProvider(): array
    {
        return [
            ['title' => 'Reactions', 'shortDescription' => 'Manage incoming HTTP webhooks to external system'],
            ['title' => 'Webhooks', 'shortDescription' => 'Manage outgoing HTTP webhooks to external system'],
        ];
    }

    #[DataProvider('integrationsMenuDataProvider')]
    public function seeIntegrationSubModules(ApplicationTester $I, Example $exampleData): void
    {
        $I->amGoingTo('see card for ' . $exampleData['title']);
        $I->waitForElementVisible('.card-container');
        $I->see($exampleData['title'], '.card-title');
        $I->see($exampleData['shortDescription'], '.card-subtitle');
        $I->see('Open ' . $exampleData['title'] . ' module', '.card-footer');

        $I->amGoingTo('open ' . $exampleData['title'] . ' module via card button');
        // Find the card containing the specific title and click its "Open module" button
        $cardSelector = '//div[@class="card card-size-small" and .//h2[contains(text(), "' . $exampleData['title'] . '")]]';
        $I->click('.btn', $cardSelector);
        $I->waitForText($exampleData['title']);
        $I->see($exampleData['title'], 'h1');
    }
}
