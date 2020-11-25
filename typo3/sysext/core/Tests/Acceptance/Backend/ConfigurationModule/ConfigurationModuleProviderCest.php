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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\ConfigurationModule;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Configuration module provider tests
 */
class ConfigurationModuleProviderCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I): void
    {
        $I->useExistingSession('admin');
        $I->scrollTo('#system_config');
        $I->see('Configuration', '#system_config');
        $I->click('#system_config');
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     */
    public function selectAndDisplayConfiguration(BackendTester $I): void
    {
        // Module can be accessed
        $I->see('Configuration', 'h1');

        // Sorting is applied and TYPO3_CONF_VARS is the default provider to display
        $I->see('$GLOBALS[\'TYPO3_CONF_VARS\'] (Global Configuration)', 'h2');

        // Middlewares provider exists
        $I->selectOption('select[name=tree]', 'HTTP Middlewares (PSR-15)');

        // Middleware provider can be loaded
        $I->waitForElementVisible('#ConfigurationView');
        $I->see('HTTP Middlewares (PSR-15)', 'h2');

        // Tree search can be applied
        $I->fillField('#lowlevel-searchString', '\/authentication$');
        $I->checkOption('#lowlevel-regexSearch');
        $I->click('input#search');

        // Correct tree with search options present and active results is loaded
        $I->waitForElementVisible('#ConfigurationView');
        $I->see('HTTP Middlewares (PSR-15)', 'h2');
        $I->seeElement('#lowlevel-searchString', ['value' => '\/authentication$']);
        $I->seeCheckboxIsChecked('#lowlevel-regexSearch');
        $I->seeElement('li.active');
    }
}
