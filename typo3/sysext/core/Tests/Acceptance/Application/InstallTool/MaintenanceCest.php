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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\InstallTool;

use Codeception\Attribute\Env;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

final class MaintenanceCest extends AbstractCest
{
    public function _before(ApplicationTester $I): void
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Maintenance');
        $I->see('Maintenance', 'h1');
    }

    public function flushCacheWorks(ApplicationTester $I): void
    {
        $I->click('Flush cache');
        $I->waitForElementVisible('.alert-success');
        $I->see('Caches cleared', '.alert-success .alert-title');
    }

    public function analyzeDatabaseStructureWorks(ApplicationTester $I): void
    {
        $I->click('Analyze database…');
        $I->waitForElementVisible('.t3js-modal');
        $I->see('Analyze Database Structure', '.t3js-modal .modal-header-title');
        $I->waitForElementVisible('.callout-success');
        $I->see('Database schema is up to date. Good job!', '.callout-success .callout-title');
    }

    public function removeTemporaryAssetsWorks(ApplicationTester $I): void
    {
        $I->click('Scan temporary files…');
        $I->waitForElementVisible('.t3js-modal');
        $I->see('Remove Temporary Assets', '.t3js-modal .modal-header-title');
    }

    #[Env('classic')]
    public function dumpAutoloadWorks(ApplicationTester $I): void
    {
        $I->click('Dump autoload');
        $I->waitForElementVisible('.alert-success');
        $I->see('Successfully dumped class loading information for extensions.', '.alert-success .alert-title');
    }

    public function clearPersistentTablesWorks(ApplicationTester $I): void
    {
        $I->click('Scan tables…');
        $I->waitForElementVisible('.t3js-modal');
        $I->see('Clear Persistent Database Tables', '.t3js-modal .modal-header-title');
    }

    public function createAdminUserWorks(ApplicationTester $I): void
    {
        $I->click('Create Administrator…');
        $I->waitForElementVisible('.t3js-modal');
        $I->see('Create Administrative User', '.t3js-modal .modal-header-title');
    }

    public function resetBackendUserPreferencesWorks(ApplicationTester $I): void
    {
        $I->click('Reset backend user preferences');
        $I->waitForElementVisible('.alert-success');
        $I->see('Reset preferences of all backend users', '.alert-success .alert-title');
        $I->see('Preferences of all backend users have been reset', '.alert-success p');
    }

    public function manageLanguagePacksWorks(ApplicationTester $I): void
    {
        $I->click('Manage languages…');
        $I->waitForElementVisible('.t3js-modal');
        $I->see('Manage Language Packs', '.t3js-modal .modal-header-title');
        $I->waitForText('Active languages', 30, '.t3js-modal h2');
    }
}
