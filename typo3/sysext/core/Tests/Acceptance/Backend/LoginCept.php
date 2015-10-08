<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('I want to see the TYPO3 backend login form');
$I->amOnPage('/typo3/index.php');
$I->see('Login', '#t3-login-submit');
$I->fillField('#t3-username', 'admin');
$I->fillField('#t3-password', 'joh316');
