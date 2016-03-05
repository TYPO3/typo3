<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Login;

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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Kasper;

/**
 * Acceptance test
 */
class BadCredentialsCest
{

    /**
     * Call backend login page and submit invalid login data.
     * Verifies login is not accepted and an error message is rendered.
     *
     * @param Kasper $I
     */
    public function tryToTest(Kasper $I)
    {
        $I->wantTo('check login functions');
        $I->amOnPage('/typo3/index.php');
        $I->waitForElement('#t3-username');

        $I->wantTo('check empty credentials');
        $required = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            return $webdriver->findElement(\WebDriverBy::cssSelector('#t3-username'))->getAttribute('required');
        });
        $I->assertEquals('true', $required, '#t3-username');

        $required = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            return $webdriver->findElement(\WebDriverBy::cssSelector('#t3-password'))->getAttribute('required');
        });
        $I->assertEquals('true', $required, '#t3-password');

        $I->wantTo('use bad credentials');
        $I->fillField('#t3-username', 'testify');
        $I->fillField('#t3-password', '123456');
        $I->click('#t3-login-submit-section > button');
        $I->see('Verifying Login Data');
        $I->waitForElement('#t3-login-error');
        $I->see('Your login attempt did not succeed');
    }
}
