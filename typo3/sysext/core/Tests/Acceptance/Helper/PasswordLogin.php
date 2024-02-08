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

namespace TYPO3\CMS\Core\Tests\Acceptance\Helper;

use Codeception\Module;
use Codeception\Module\WebDriver;
use Codeception\Util\Locator;
use Facebook\WebDriver\WebDriverKeys;

/**
 * Helper class to log in backend users and load backend.
 */
final class PasswordLogin extends Module
{
    /**
     * @var array Filled by .yml config with valid sessions per role
     */
    protected $config = [
        'passwords' => [],
    ];

    /**
     * Log in a backend user (or use a session snapshot) ato ensure a
     * session cookie is set. Afterwards the backend entrypoint is loaded.
     *
     * Use this action to change the backend user and avoid switching between users in the backend module
     * "Backend Users" as this will change the user session ID and make it useless for subsequent calls of this action.
     *
     * @param string $role The backend user who should be logged in.
     * @param float $waitTime Used waitTime in seconds between single steps. Default: 0.5
     */
    public function useExistingSession(string $role, float $waitTime = 0.5): void
    {
        $webDriver = $this->getWebDriver();

        $hasSession = $this->loadSession($role);

        $webDriver->amOnPage('/typo3');
        $webDriver->wait($waitTime);

        if (!$hasSession) {
            $webDriver->waitForElement('body[data-typo3-login-ready]');
            $password = $this->_getConfig('passwords')[$role];
            $webDriver->fillField('#t3-username', $role);
            $webDriver->fillField('#t3-password', $password);
            $webDriver->pressKey('#t3-password', WebDriverKeys::ENTER);
            $webDriver->waitForElement('.t3js-scaffold-toolbar');
            $webDriver->saveSessionSnapshot('login.' . $role);
        }

        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions ..
        $webDriver->debugSection('IFRAME', 'Switch to list_frame');
        $webDriver->waitForElement('iframe[name="list_frame"]');
        $webDriver->switchToIFrame('list_frame');
        $webDriver->waitForElement(Locator::firstElement('div.module'));
        $webDriver->wait($waitTime);
        // .. and switch back to main frame.
        $webDriver->debugSection('IFRAME', 'Switch to main frame');
        $webDriver->switchToIFrame();

        $webDriver->debug(sprintf('useExistingSession("%s", %s) finished.', $role, $waitTime));
    }

    private function loadSession(string $role): bool
    {
        $webDriver = $this->getWebDriver();
        $webDriver->webDriver->manage()->deleteCookieNamed('be_typo_user');
        $webDriver->webDriver->manage()->deleteCookieNamed('be_lastLoginProvider');
        return $webDriver->loadSessionSnapshot('login.' . $role, false);
    }

    private function getWebDriver(): WebDriver
    {
        return $this->getModule('WebDriver');
    }
}
