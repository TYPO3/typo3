<?php


/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * The session cookie that is used if the session is injected.
     * This session must exist in the database fixture to get a logged in state.
     * 
     * @var string
     */
    protected $sessionCookie = '';

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
