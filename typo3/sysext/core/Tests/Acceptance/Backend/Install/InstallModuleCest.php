<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Language;

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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Install Module tests
 */
class InstallModuleCest
{
    /**
     * @var string
     */
    protected $password = '';

    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $this->password = getenv('typo3InstallToolPassword');

        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('list_frame');
        $I->waitForText('Web Content Management System');
        $I->switchToIFrame();

        $I->see('Install');
        $I->click('Install');

        // switch to content iframe
        $I->switchToIFrame('list_frame');
    }

    /**
     * @param Admin $I
     */
    public function unlockAndLockInstallTool(Admin $I)
    {
        $I->wantTo('Check the Install Tool unlock and lock functions.');

        // @todo probably there is a better solution skipping the test
        if (empty($this->password)) {
            $I->comment('Skip this test.');
        } else {
            $I->amGoingTo('unlock the install tool');
            $I->waitForElement('#t3-install-form-unlock');
            $I->see('The Install Tool is locked');
            $I->see('Unlock the Install Tool');
            $I->click('//button[@value="enableInstallTool"]');
            $I->waitForElement('#t3-install-outer');
            $I->see('Password');
            $I->see('Login');

            $I->amGoingTo('lock the install tool');
            $I->see('Lock Install Tool again');
            $I->click('Lock Install Tool again');
            $I->see('The Install Tool is locked');
        }
    }

    /**
     * @param Admin $I
     */
    public function loginToInstallTool(Admin $I)
    {
        $I->wantTo('Check the Install Tool Login with wrong and right passwords.');

        // @todo probably there is a better solution skipping the test
        if (empty($this->password)) {
            $I->comment('Skip this test.');
        } else {
            $I->amGoingTo('unlock the install tool');
            $I->waitForElement('#t3-install-form-unlock');
            $I->see('The Install Tool is locked');
            $I->see('Unlock the Install Tool');
            $I->click('//button[@value="enableInstallTool"]');
            $I->waitForElement('#t3-install-outer');

            $I->amGoingTo('login to install tool with wrong password');
            $I->fillField('#t3-install-form-password', 'wrong_' . $this->password);
            $I->click('//button[@type="submit"]');
            $I->waitForElement('//div[@class="t3js-message typo3-message alert alert-danger"]');
            $I->see('Login failed');
            $I->see('Given password does not match the install tool login password.');
            $I->see('Calculated hash:');

            $I->amGoingTo('login to install tool with right password');
            $I->fillField('#t3-install-form-password', $this->password);
            $I->click('//button[@type="submit"]');
            $I->waitForElement('//body[@class="backend"]');
            $I->see('Important actions');
            $I->waitForElement('.t3js-list-group-wrapper');
            $I->see('Logout from Install Tool');
            // can't click the link text
            $I->seeElement('//*[@id="menuWrapper"]/div/div/a');
            $I->click('//*[@id="menuWrapper"]/div/div/a[text()="Logout from Install Tool"]');
            $I->see('The Install Tool is locked');
        }
    }
}
