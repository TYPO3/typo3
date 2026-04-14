<?php

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

namespace TYPO3\CMS\Core\Authentication;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 backend user authentication on a CLI level
 * Auto-logs in, only allowed on CLI
 */
class CommandLineUserAuthentication extends BackendUserAuthentication
{
    /**
    /**
     * Constructor, only allowed in CLI mode
     *
     * @throws \RuntimeException
     */
    public function __construct()
    {
        if (!Environment::isCli()) {
            throw new \RuntimeException('Creating a CLI-based user object on non-CLI level is not allowed', 1483971165);
        }
        if (!$this->isUserAllowedToLogin()) {
            throw new \RuntimeException('Login Error: TYPO3 is in maintenance mode at the moment. Only administrators are allowed access.', 1483971855);
        }
        $this->dontSetCookie = true;
        parent::__construct();
    }

    /**
     * Replacement for AbstractUserAuthentication::start()
     *
     * We do not need support for sessions, cookies, $_GET-modes, the postUserLookup hook or
     * a database connection during CLI Bootstrap
     *
     * @param ServerRequestInterface|null $request
     */
    public function start(?ServerRequestInterface $request = null)
    {
        // do nothing
    }

    /**
     * Replacement for AbstractUserAuthentication::checkAuthentication()
     *
     * Not required in CLI mode, therefore empty.
     */
    public function checkAuthentication(ServerRequestInterface $request)
    {
        // do nothing
    }

    /**
     * On CLI there is no session and no switched user
     */
    public function getOriginalUserIdWhenInSwitchUserMode(): ?int
    {
        return null;
    }

    /**
     * Logs-in the _CLI_ user. It does not need to check for credentials.
     *
     * @throws \RuntimeException when the user could not log in or it is an admin
     */
    public function authenticate()
    {
        // check if a _CLI_ user exists, if not, create one
        $this->setBeUserByName(CommandLineUserCreation::CLI_USERNAME);
        if (empty($this->user['uid'])) {
            $userCreation = GeneralUtility::makeInstance(CommandLineUserCreation::class);
            // create a new BE user in the database
            if (!$userCreation->ensureCliUserExists()) {
                throw new \RuntimeException('No backend user named "_cli_" could be authenticated, maybe this user is "hidden"?', 1484050401);
            }
            $this->setBeUserByName(CommandLineUserCreation::CLI_USERNAME);
        }
        if (empty($this->user['uid'])) {
            throw new \RuntimeException('No backend user named "_cli_" could be created.', 1476107195);
        }
        $this->unpack_uc();
        // The groups are fetched and ready for permission checking in this initialization.
        $this->fetchGroupData();
        $this->backendSetUC();
    }

    /**
     * Logs in the TYPO3 Backend user "_cli_"
     */
    public function backendCheckLogin(?ServerRequestInterface $request = null)
    {
        $this->authenticate();
    }

    /**
     * Determines whether a CLI backend user is allowed to access TYPO3.
     * Only when adminOnly is off (=0), and only allowed for admins and CLI users (=2)
     *
     * @return bool Whether the CLI user is allowed to access TYPO3
     * @internal
     */
    public function isUserAllowedToLogin()
    {
        return in_array((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'], [0, 2], true);
    }
}
