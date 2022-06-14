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

namespace TYPO3\CMS\Install\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\AbstractAuthenticationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Backend module controller to the Install Tool. Sets an Install Tool session
 * marked as "initialized by a valid system administrator backend user" and
 * redirects to the Install Tool entry point.
 *
 * This is a classic backend module that does not interfere with other code
 * within the Install Tool, it can be seen as a facade around Install Tool just
 * to embed the Install Tool in backend.
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class BackendModuleController
{
    protected const FLAG_CONFIRMATION_REQUEST = 1;
    protected const FLAG_INSTALL_TOOL_PASSWORD = 2;
    protected const ALLOWED_ACTIONS = ['maintenance', 'settings', 'upgrade', 'environment'];

    protected ?SessionService $sessionService = null;

    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
    }

    /**
     * Shows and handles backend user session confirmation ("sudo mode") for
     * accessing a particular Install Tool controller (as given in `$targetController`).
     */
    public function backendUserConfirmationAction(ServerRequestInterface $request): ResponseInterface
    {
        $flags = (int)($request->getQueryParams()['flags'] ?? 0);
        $targetController = (string)($request->getQueryParams()['targetController'] ?? '');
        $targetHash = (string)($request->getQueryParams()['targetHash'] ?? '');
        $expectedTargetHash = GeneralUtility::hmac($targetController, BackendModuleController::class);
        $flagInstallToolPassword = (bool)($flags & self::FLAG_INSTALL_TOOL_PASSWORD);
        $flagInvalidPassword = false;

        if (!in_array($targetController, self::ALLOWED_ACTIONS, true)
            || !hash_equals($expectedTargetHash, $targetHash)) {
            return new HtmlResponse('', 403);
        }
        if ($flags & self::FLAG_CONFIRMATION_REQUEST) {
            if ($flagInstallToolPassword && $this->verifyInstallToolPassword($request)) {
                return $this->setAuthorizedAndRedirect($targetController);
            }
            if (!$flagInstallToolPassword && $this->verifyBackendUserPassword($request)) {
                return $this->setAuthorizedAndRedirect($targetController);
            }
            $flagInvalidPassword = true;
        }

        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'flagInvalidPassword' => $flagInvalidPassword,
            'flagInstallToolPassword' => $flagInstallToolPassword,
            'passwordModeUri' => $this->getBackendUserConfirmationUri([
                'targetController' => $targetController,
                'targetHash' => $targetHash,
                // current flags, unset FLAG_CONFIRMATION_REQUEST, toggle FLAG_INSTALL_TOOL_PASSWORD
                'flags' => $flags & ~self::FLAG_CONFIRMATION_REQUEST ^ self::FLAG_INSTALL_TOOL_PASSWORD,
            ]),
            'verifyUri' => $this->getBackendUserConfirmationUri([
                'targetController' => $targetController,
                'targetHash' => $targetHash,
                // current flags, add FLAG_CONFIRMATION_REQUEST
                'flags' => $flags | self::FLAG_CONFIRMATION_REQUEST,
            ]),
        ]);

        $view->setModuleName('tools_tools' . $targetController);
        return $view->renderResponse('BackendModule/BackendUserConfirmation');
    }

    /**
     * Initialize session and redirect to "maintenance"
     */
    public function maintenanceAction(): ResponseInterface
    {
        return $this->getBackendUserConfirmationRedirect('maintenance')
            ?? $this->setAuthorizedAndRedirect('maintenance');
    }

    /**
     * Initialize session and redirect to "settings"
     */
    public function settingsAction(): ResponseInterface
    {
        return $this->getBackendUserConfirmationRedirect('settings')
            ?? $this->setAuthorizedAndRedirect('settings');
    }

    /**
     * Initialize session and redirect to "upgrade"
     */
    public function upgradeAction(): ResponseInterface
    {
        return $this->getBackendUserConfirmationRedirect('upgrade')
            ?? $this->setAuthorizedAndRedirect('upgrade');
    }

    /**
     * Initialize session and redirect to "environment"
     */
    public function environmentAction(): ResponseInterface
    {
        return $this->getBackendUserConfirmationRedirect('environment')
            ?? $this->setAuthorizedAndRedirect('environment');
    }

    /**
     * Creates redirect response to backend user confirmation (if required).
     */
    protected function getBackendUserConfirmationRedirect(string $targetController): ?ResponseInterface
    {
        if ($this->getSessionService()->isAuthorizedBackendUserSession()) {
            return null;
        }
        if (Environment::getContext()->isDevelopment()) {
            return null;
        }
        $redirectUri = $this->getBackendUserConfirmationUri([
            'targetController' => $targetController,
            'targetHash' => GeneralUtility::hmac($targetController, BackendModuleController::class),
        ]);
        return new RedirectResponse((string)$redirectUri, 403);
    }

    protected function getBackendUserConfirmationUri(array $parameters): Uri
    {
        return $this->uriBuilder->buildUriFromRoute(
            'install.backend-user-confirmation',
            $parameters
        );
    }

    /**
     * Starts / updates the session and redirects to the Install Tool
     * with given action.
     */
    protected function setAuthorizedAndRedirect(string $controller): ResponseInterface
    {
        $userSession = $this->getBackendUser()->getSession();
        $this->getSessionService()->setAuthorizedBackendSession($userSession);
        $redirectLocation = PathUtility::getAbsoluteWebPath('install.php?install[controller]=' . $controller . '&install[context]=backend');
        return new RedirectResponse($redirectLocation, 303);
    }

    /**
     * Verifies that provided password matches Install Tool password.
     */
    protected function verifyInstallToolPassword(ServerRequestInterface $request): bool
    {
        $parsedBody = $request->getParsedBody();
        $password = $parsedBody['confirmationPassword'] ?? null;
        $installToolPassword = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] ?? null;
        if (!is_string($password) || empty($installToolPassword)) {
            return false;
        }

        try {
            $hashFactory = GeneralUtility::makeInstance(PasswordHashFactory::class);
            $hashInstance = $hashFactory->get($installToolPassword, 'BE');
            return $hashInstance->checkPassword($password, $installToolPassword);
        } catch (InvalidPasswordHashException $exception) {
            return false;
        }
    }

    /**
     * Verifies that provided password is actually correct for current backend user
     * by stepping through authentication chain in `$GLOBALS['BE_USER]`.
     */
    protected function verifyBackendUserPassword(ServerRequestInterface $request): bool
    {
        $parsedBody = $request->getParsedBody();
        $password = $parsedBody['confirmationPassword'] ?? null;
        if (!is_string($password)) {
            return false;
        }

        // clone current backend user object to avoid
        // possible side effects for the real instance
        $backendUser = clone $this->getBackendUser();
        $loginData = [
            'status' => 'sudo-mode',
            'origin' => BackendModuleController::class,
            'uname'  => $backendUser->user['username'],
            'uident' => $password,
        ];
        // currently there is no dedicated API to perform authentication
        // that's why this process partially has to be simulated here
        $loginData = $backendUser->processLoginData($loginData);
        $authInfo = $backendUser->getAuthInfoArray();

        $authenticated = false;
        /** @var AbstractAuthenticationService $service or any other service (sic!) */
        foreach ($this->getAuthServices($backendUser, $loginData, $authInfo) as $service) {
            $ret = (int)$service->authUser($backendUser->user);
            if ($ret <= 0) {
                return false;
            }
            if ($ret >= 200) {
                return true;
            }
            if ($ret < 100) {
                $authenticated = true;
                continue;
            }
        }
        return $authenticated;
    }

    /**
     * Initializes authentication services to be used in a foreach loop
     *
     * @return \Generator<int, object>
     */
    protected function getAuthServices(BackendUserAuthentication $backendUser, array $loginData, array $authInfo): \Generator
    {
        $serviceChain = [];
        $subType = 'authUserBE';
        while ($service = GeneralUtility::makeInstanceService('auth', $subType, $serviceChain)) {
            $serviceChain[] = $service->getServiceKey();
            if (!is_object($service)) {
                break;
            }
            $service->initAuth($subType, $loginData, $authInfo, $backendUser);
            yield $service;
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Install Tool modified sessions meta-data (handler, storage, name) which
     * conflicts with existing session that for instance.
     */
    protected function getSessionService(): SessionService
    {
        if ($this->sessionService === null) {
            $this->sessionService = new SessionService();
            $this->sessionService->startSession();
        }
        return $this->sessionService;
    }
}
