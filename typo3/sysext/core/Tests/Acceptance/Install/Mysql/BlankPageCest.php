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

namespace TYPO3\CMS\Core\Tests\Acceptance\Install\Mysql;

use Codeception\Scenario;
use TYPO3\CMS\Core\Tests\Acceptance\Support\InstallTester;

/**
 * Click through installer, go to backend, check blank site in FE works
 */
final class BlankPageCest
{
    /**
     * @env mysql
     */
    public function installTypo3OnMysql(InstallTester $I, Scenario $scenario): void
    {
        // Calling frontend redirects to installer
        $I->amOnPage('/');

        // EnvironmentAndFolders step
        $I->waitForText('Installing TYPO3');
        $I->waitForText('No problems detected, continue with installation');
        $I->click('No problems detected, continue with installation');

        // DatabaseConnection step
        $I->waitForText('Connect to database', 30);
        $I->fillField('#t3-install-step-mysqliManualConfiguration-username', $scenario->current('typo3InstallMysqlDatabaseUsername'));
        $I->fillField('#t3-install-step-mysqliManualConfiguration-password', $scenario->current('typo3InstallMysqlDatabasePassword'));
        $I->fillField('#t3-install-step-mysqliManualConfiguration-host', $scenario->current('typo3InstallMysqlDatabaseHost'));
        $I->click('Continue');

        // DatabaseSelect step
        $I->waitForText('Select a database', 30);
        $I->click('#t3-install-form-db-select-type-new');
        $I->fillField('#t3-install-step-database-new', $scenario->current('typo3InstallMysqlDatabaseName'));
        $I->click('Continue');

        // DatabaseData step
        $I->waitForText('Create administrative user and specify site name');
        $I->fillField('#username', 'admin');
        $I->fillField('#password', 'Policy-Compliant_Password.1');
        $I->click('Continue');

        // DefaultConfiguration step - Create empty page
        $I->waitForText('Installation complete', 60);
        $I->click('#create-site');
        $I->click('Open the TYPO3 Backend');

        // Verify backend login successful
        $I->waitForElement('#t3-username');
        $I->fillField('#t3-username', 'admin');
        $I->fillField('#t3-password', 'Policy-Compliant_Password.1');
        $I->click('#t3-login-submit-section > button');
        $I->waitForElement('.modulemenu', 30);
        $I->waitForElement('.scaffold-content iframe', 30);
        $I->seeCookie('be_typo_user');

        // Verify default frontend is rendered
        $I->amOnPage('/');
        $I->waitForText('Welcome to a default website made with TYPO3');
    }
}
