<?php
namespace TYPO3\CMS\Core\Tests\AcceptanceInstallMysql;

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

/**
 * Click through installer, go to backend, check blank site in FE works
 */
class InstallWithMysqlIntroductionPackageCest
{
    /**
     * @param \AcceptanceTester $I
     */
    public function installTypo3OnMysql(\AcceptanceTester $I)
    {
        // Calling frontend redirects to installer
        $I->amOnPage('/');

        // EnvironmentAndFolders step
        $I->waitForText('Installing TYPO3');
        $I->waitForText('System looks good. Continue!');
        $I->click('System looks good. Continue!');

        // DatabaseConnection step
        $I->waitForText('Database connection');
        $I->fillField('#t3-install-step-mysqliManualConfiguration-username', getenv('typo3DatabaseUsername'));
        $I->fillField('#t3-install-step-mysqliManualConfiguration-password', getenv('typo3DatabasePassword'));
        $I->click('Continue');

        // DatabaseSelect step
        $I->waitForText('Select database');
        $I->click('#t3-install-form-db-select-type-new');
        $I->fillField('#t3-install-step-database-new', getenv('typo3DatabaseName') . '_atimysql');
        $I->click('Continue');

        // DatabaseData step
        $I->waitForText('Create user and import base data');
        $I->fillField('#username', 'admin');
        $I->fillField('#password', 'password');
        $I->click('Continue');

        // DefaultConfiguration step - Create empty page
        $I->waitForText('Installation done!');
        $I->click('#load-distributions');
        $I->click('Open the TYPO3 Backend');

        // Verify backend login successful
        $I->waitForElement('#t3-username');
        $I->fillField('#t3-username', 'admin');
        $I->fillField('#t3-password', 'password');
        $I->click('#t3-login-submit-section > button');
        $I->waitForElement('.nav', 30);
        $I->waitForElement('.scaffold-content iframe', 30);
        $I->seeCookie('be_lastLoginProvider');
        $I->seeCookie('be_typo_user');

        // Loading might take some time
        $I->wait(10);
        $I->switchToIFrame('list_frame');
        $I->waitForText('Get preconfigured distribution', 30);
        $I->click('.t3-button-action-installdistribution');
        $I->waitForText('You successfully installed the distribution:introduction', 120);

        // Verify default frontend is rendered
        $I->amOnPage('/');
        $I->waitForText('Let us introduce you to TYPO3', 30);
        $I->waitForText('Make it your own');

        // Verify link
        $I->click('[title="Features"]');
        $I->waitForText('Feature Complete Out-of-the-box', 30);
    }
}
