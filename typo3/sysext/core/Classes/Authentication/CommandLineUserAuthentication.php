<?php
namespace TYPO3\CMS\Core\Authentication;

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;

/**
 * TYPO3 backend user authentication on a CLI level
 * Auto-logs in, only allowed on CLI
 */
class CommandLineUserAuthentication extends BackendUserAuthentication
{

    /**
     * The username of the CLI user (there is only one)
     * @var string
     */
    protected $username = '_cli_';

    /**
     * Constructor, only allowed in CLI mode
     */
    public function __construct()
    {
        if (!(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI)) {
            throw new \RuntimeException('Creating a CLI-based user object on non-CLI level is not allowed', 1483971165);
        }
        $this->dontSetCookie = true;
        parent::__construct();
    }

    /**
     * Logs-in the _CLI_ user. It does not need to check for credentials.
     *
     * @throws \RuntimeException when the user could not log in or it is an admin
     */
    public function authenticate()
    {
        // check if a _CLI_ user exists, if not, create one
        $this->setBeUserByName($this->username);
        if (!$this->user['uid']) {
            // create a new BE user in the database
            if (!$this->checkCliUserExists()) {
                $this->createCliUser();
            } else {
                throw new \RuntimeException('No backend user named "_cli_" could be authenticated, maybe this user is "hidden"?', 1484050401);
            }
            $this->setBeUserByName($this->username);
        }
        if (!$this->user['uid']) {
            throw new \RuntimeException('No backend user named "_cli_" could be created.', 1476107195);
        }
        // The groups are fetched and ready for permission checking in this initialization.
        $this->fetchGroupData();
        if (!$this->isUserAllowedToLogin()) {
            throw new \RuntimeException('Login Error: TYPO3 is in maintenance mode at the moment. Only administrators are allowed access.', 1483971855);
        }
        $this->backendSetUC();
    }

    /**
     * Check if user is logged in and if so, call ->fetchGroupData() to load group information and
     * access lists of all kind, further check IP, set the ->uc array and send login-notification email if required.
     * If no user is logged in the default behaviour is to exit with an error message.
     * This function is called right after ->start() in fx. the TYPO3 Bootstrap.
     *
     * @param bool $proceedIfNoUserIsLoggedIn if this option is set, then there won't be a redirect to the login screen of the Backend - used for areas in the backend which do not need user rights like the login page.
     * @throws \RuntimeException
     * @return void
     */
    public function backendCheckLogin($proceedIfNoUserIsLoggedIn = false)
    {
        $this->authenticate();
    }

    /**
     * Determines whether a CLI backend user is allowed to access TYPO3.
     * Only when adminOnly is off (=0), and only allowed for admins and CLI users (=2)
     *
     * @return bool Whether the CLI user is allowed to access TYPO3
     */
    protected function isUserAllowedToLogin()
    {
        return in_array((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['adminOnly'], [0, 2], true);
    }

    /**
     * Check if a user with username "_cli_" exists. Deleted users are left out
     * but hidden and start / endtime restricted users are considered.
     */
    protected function checkCliUserExists()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $count = $queryBuilder
            ->count('*')
            ->from('be_users')
            ->where($queryBuilder->expr()->eq('username', $queryBuilder->createNamedParameter('_cli_')))
            ->execute()
            ->fetchColumn(0);
        return (bool)$count;
    }

    /**
     * Create a record in the DB table be_users called "_cli_" with no other information
     *
     * @return void
     */
    protected function createCliUser()
    {
        $userFields = [
            'username' => $this->username,
            'password' => $this->generateHashedPassword(),
            'admin'    => 1,
            'tstamp'   => $GLOBALS['EXEC_TIME'],
            'crdate'   => $GLOBALS['EXEC_TIME']
        ];

        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users');
        $databaseConnection->insert('be_users', $userFields);
    }

    /**
     * This function returns a salted hashed key.
     *
     * @return string a random password
     */
    protected function generateHashedPassword()
    {
        $cryptoService = GeneralUtility::makeInstance(Random::class);
        $password = $cryptoService->generateRandomBytes(20);
        $saltFactory = SaltFactory::getSaltingInstance(null, 'BE');
        return $saltFactory->getHashedPassword($password);
    }
}
