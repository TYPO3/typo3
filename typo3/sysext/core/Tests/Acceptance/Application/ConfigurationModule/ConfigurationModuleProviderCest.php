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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\ConfigurationModule;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Configuration module provider tests
 */
class ConfigurationModuleProviderCest
{
    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->scrollTo('#system_config');
        $I->see('Configuration', '#system_config');
        $I->click('#system_config');
        $I->switchToContentFrame();
    }

    /**
     * @param ApplicationTester $I
     */
    public function selectAndDisplayConfiguration(ApplicationTester $I): void
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
        $I->click('#lowlevel-config button.dropdown-toggle');
        $I->waitForElementVisible('#lowlevel-config .dropdown-menu');
        $I->checkOption('#lowlevel-regexSearch');
        $I->click('#lowlevel-config button[type=submit]');

        // Correct tree with search options present and active results is loaded
        $I->waitForElementVisible('#ConfigurationView');
        $I->see('HTTP Middlewares (PSR-15)', 'h2');
        $I->seeElement('#lowlevel-searchString', ['value' => '\/authentication$']);
        $I->seeCheckboxIsChecked('#lowlevel-regexSearch');
        $I->seeElement('li.active');
    }

    /**
     * @param ApplicationTester $I
     */
    public function canOpenTreeNodeAndScrollTo(ApplicationTester $I): void
    {
        $I->selectOption('select[name=tree]', '$GLOBALS[\'TYPO3_CONF_VARS\'] (Global Configuration)');
        $I->click('.list-tree > li:first-child .list-tree-control');
        $I->see('checkStoredRecordsLoose', '.list-tree-group');
        $I->see('BE', '.active > .list-tree-group');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeAllPagesInDropDown(ApplicationTester $I): void
    {
        foreach ($this->dropDownPagesDataProvider() as $item) {
            $I->selectOption('select[name=tree]', $item);
            $I->see($item, 'h2');
        }
    }

    protected function dropDownPagesDataProvider(): array
    {
        return [
            '$GLOBALS[\'TYPO3_CONF_VARS\'] (Global Configuration)',
            '$GLOBALS[\'TCA\'] (Table configuration array)',
            '$GLOBALS[\'TCA_DESCR\'] (Table Help Description)',
            '$GLOBALS[\'T3_SERVICES\'] (Registered Services)',
            '$GLOBALS[\'TBE_MODULES\'] (BE Modules)',
            '$GLOBALS[\'TBE_MODULES_EXT\'] (BE Modules Extensions)',
            '$GLOBALS[\'TBE_STYLES\'] (Skinning Styles)',
            '$GLOBALS[\'TYPO3_USER_SETTINGS\'] (User Settings Configuration)',
            '$GLOBALS[\'PAGES_TYPES\'] (Table permissions by page type)',
            '$GLOBALS[\'BE_USER\']->uc (User Settings)',
            '$GLOBALS[\'BE_USER\']->getTSConfig() (User TSconfig)',
            'Backend Routes',
            'HTTP Middlewares (PSR-15)',
            'Site Configuration',
            'Event Listeners (PSR-14)',
            'MFA providers',
        ];
    }
}
