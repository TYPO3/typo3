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
 * A backend editor
 */
class Editor extends \AcceptanceTester
{
    /**
     * @var string Assigned session cookie
     */
    protected $sessionCookie = 'ff83dfd81e20b34c27d3e97771a4525a';

    /**
     * Use the existing database session from the fixture by setting the backend user cookie
     */
    public function useExistingSession()
    {
        $I = $this;
        $I->amOnPage('/typo3/index.php');
        $I->setCookie('be_typo_user', $this->sessionCookie, array('path' => '/typo3temp/var/tests/acceptance/'));
        $I->setCookie('be_lastLoginProvider', '1433416747', array('path' => '/typo3temp/var/tests/acceptance/'));
        // reload the page to have a logged in backend
        $I->amOnPage('/typo3/index.php');
    }
}
