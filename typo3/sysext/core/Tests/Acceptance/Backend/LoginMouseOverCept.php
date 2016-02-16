<?php
/**
 * This tests login mouse over button.
 */

$I = new AcceptanceTester($scenario);
$I->wantTo('check login functions');
$I->amOnPage('/typo3/index.php');
$I->waitForElement('#t3-username', 10);
$I->wantTo('mouse over css change login button');

$bs = $I->executeInSelenium(function(\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
    return $webdriver->findElement(WebDriverBy::cssSelector('#t3-login-submit'))->getCSSValue('box-shadow');
});

$I->moveMouseOver('#t3-login-submit');
$I->wait(1);
$bsmo = $I->executeInSelenium(function(\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
    return $webdriver->findElement(WebDriverBy::cssSelector('#t3-login-submit'))->getCSSValue('box-shadow');
});
$this->assertFalse($bs == $bsmo);
