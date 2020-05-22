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

namespace TYPO3\CMS\Core\Tests\Acceptance\InstallTool;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

class MaintenanceCest extends AbstractCest
{
    public function _before(BackendTester $I)
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Maintenance');
        $I->see('Maintenance', 'h1');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function flushCacheWorks(BackendTester $I)
    {
        $I->click('Flush cache');
        $I->waitForElementVisible('.alert-success');
        $I->see('Caches cleared', '.alert-success h4');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function analyzeDatabaseStructureWorks(BackendTester $I)
    {
        $I->click('Analyze database');
        $I->waitForElementVisible('.modal-dialog');
        $I->see('Analyze Database Structure', '.modal-dialog h4');
        $I->waitForElementVisible('.callout-success');
        $I->see('Database schema is up to date. Good job!', '.callout-success h4');
        $I->click('.t3js-modal-close');
        $I->waitForElementNotVisible('.modal-dialog');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function removeTemporaryAssetsWorks(BackendTester $I)
    {
        $I->click('Scan temporary files');
        $I->waitForElementVisible('.modal-dialog');
        $I->see('Remove Temporary Assets', '.modal-dialog h4');
        $I->click('.t3js-modal-close');
        $I->waitForElementNotVisible('.modal-dialog');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function dumpAutoloadWorks(BackendTester $I)
    {
        $I->click('Dump autoload');
        $I->waitForElementVisible('.alert-success');
        $I->see('Successfully dumped class loading information for extensions.', '.alert-success h4');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function clearPersistentTablesWorks(BackendTester $I)
    {
        $I->click('Scan tables');
        $I->waitForElementVisible('.modal-dialog');
        $I->see('Clear Persistent Database Tables', '.modal-dialog h4');
        $I->click('.t3js-modal-close');
        $I->waitForElementNotVisible('.modal-dialog');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function createAdminUserWorks(BackendTester $I)
    {
        $I->click('Create Administrator');
        $I->waitForElementVisible('.modal-dialog');
        $I->see('Create Administrative User', '.modal-dialog h4');
        $I->click('.t3js-modal-close');
        $I->waitForElementNotVisible('.modal-dialog');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function resetBackendUserPreferencesWorks(BackendTester $I)
    {
        $I->click('Reset backend user preferences');
        $I->waitForElementVisible('.alert-success');
        $I->see('Reset preferences of all backend users', '.alert-success h4');
        $I->see('Preferences of all backend users have been reset', '.alert-success p');
    }

    /**
     * @param BackendTester $I
     * @throws \Exception
     */
    public function manageLanguagePacksWorks(BackendTester $I)
    {
        $I->click('Manage languages');
        $I->waitForElementVisible('.modal-dialog');
        $I->see('Manage Language Packs', '.modal-dialog h4');
        $I->waitForText('Active languages', 30, '.modal-dialog h3');
        $I->click('.t3js-modal-close');
        $I->waitForElementNotVisible('.modal-dialog');
    }
}
