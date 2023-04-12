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

namespace TYPO3\CMS\Backend\Security\SudoMode;

use TYPO3\CMS\Core\Authentication\AuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Controller\BackendModuleController;

/**
 * Service for either verifying the password of the current backend user
 * session, or the admin tool password (without actually going through
 * the complete authentication process).
 *
 * @internal
 */
class PasswordVerification
{
    public function __construct(
        protected readonly PasswordHashFactory $passwordHashFactory
    ) {
    }

    /**
     * Verifies that provided password matches Install Tool password.
     */
    public function verifyInstallToolPassword(string $password): bool
    {
        $installToolPassword = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] ?? null;
        if ($password === '') {
            return false;
        }

        try {
            return $this->passwordHashFactory
                ->get($installToolPassword, 'BE')
                ->checkPassword($password, $installToolPassword);
        } catch (InvalidPasswordHashException) {
            return false;
        }
    }

    /**
     * Verifies that the provided password is actually correct for current backend user
     * by stepping through the authentication chain in `$GLOBALS['BE_USER]`.
     */
    public function verifyBackendUserPassword(string $password, BackendUserAuthentication $backendUser): bool
    {
        if ($password === '') {
            return false;
        }

        // clone the current backend user object to avoid
        // possible side effects for the real instance
        $backendUser = clone $backendUser;
        $loginData = [
            'status' => 'sudo-mode',
            'origin' => BackendModuleController::class,
            'uname'  => $backendUser->user['username'],
            'uident' => $password,
        ];
        // currently there is no dedicated API to perform authentication
        // that's why this process partially has to be simulated here
        $fakeRequest = new ServerRequest();
        $loginData = $backendUser->processLoginData($loginData, $fakeRequest);
        $authInfo = $backendUser->getAuthInfoArray($fakeRequest);

        $authenticated = false;
        /** @var AuthenticationService $service or any other service (sic!) */
        foreach ($this->getAuthServices($backendUser, $loginData, $authInfo) as $service) {
            $ret = $service->authUser($backendUser->user);
            if ($ret <= 0) {
                return false;
            }
            if ($ret >= 200) {
                return true;
            }
            if ($ret < 100) {
                $authenticated = true;
            }
        }
        return $authenticated;
    }

    /**
     * Initializes authentication services to be used in a foreach loop
     *
     * @param array $loginData
     * @param array $authInfo
     * @return \Generator<int, object>
     */
    protected function getAuthServices(BackendUserAuthentication $backendUser, array $loginData, array $authInfo): \Generator
    {
        $serviceChain = [];
        $subType = 'authUserBE';
        while ($service = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain)) {
            /** @var AuthenticationService $service */
            $serviceChain[] = $service->getServiceKey();
            if (!is_object($service)) {
                continue;
            }
            $service->initAuth($subType, $loginData, $authInfo, $backendUser);
            yield $service;
        }
    }
}
