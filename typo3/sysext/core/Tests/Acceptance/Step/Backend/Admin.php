<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Step\Backend;

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
 * A backend user with admin access
 */
class Admin extends \AcceptanceTester
{
    /**
     * @var string Assigned session cookie
     */
    protected $sessionCookie = '886526ce72b86870739cc41991144ec1';

    /**
     * Use the existing database session from the fixture by setting the backend user cookie
     */
    public function useExistingSession()
    {
        $I = $this;
        $I->amOnPage('/typo3/index.php');

        // @todo: There is a bug in PhantomJS where adding a cookie fails.
        // This bug will be fixed in the next PhantomJS version but i also found
        // this workaround. First reset / delete the cookie and than set it and catch
        // the webdriver exception as the cookie has been set successful.
        try {
            $I->resetCookie('be_typo_user');
            $I->setCookie('be_typo_user', $this->sessionCookie);
        } catch (\Facebook\WebDriver\Exception\UnableToSetCookieException $e) {
        }
        try {
            $I->resetCookie('be_lastLoginProvider');
            $I->setCookie('be_lastLoginProvider', '1433416747');
        } catch (\Facebook\WebDriver\Exception\UnableToSetCookieException $e) {
        }

        // reload the page to have a logged in backend
        $I->amOnPage('/typo3/index.php');
    }
}
