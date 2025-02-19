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

namespace TYPO3\CMS\Install\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Install\Authentication\AuthenticationService;
use TYPO3\CMS\Install\Controller\AbstractController;
use TYPO3\CMS\Install\Controller\EnvironmentController;
use TYPO3\CMS\Install\Controller\IconController;
use TYPO3\CMS\Install\Controller\LayoutController;
use TYPO3\CMS\Install\Controller\LoginController;
use TYPO3\CMS\Install\Controller\MaintenanceController;
use TYPO3\CMS\Install\Controller\SettingsController;
use TYPO3\CMS\Install\Controller\UpgradeController;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Default middleware for all requests inside the TYPO3 Install Tool, which does a simple hardcoded
 * dispatching to a controller based on the get/post variable.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Maintenance implements MiddlewareInterface
{
    /**
     * @var array List of valid controllers
     */
    protected array $controllers = [
        'icon' => IconController::class,
        'layout' => LayoutController::class,
        'login' => LoginController::class,
        'maintenance' => MaintenanceController::class,
        'settings' => SettingsController::class,
        'upgrade' => UpgradeController::class,
        'environment' => EnvironmentController::class,
    ];

    public function __construct(
        protected readonly FailsafePackageManager $packageManager,
        protected readonly ConfigurationManager $configurationManager,
        protected readonly PasswordHashFactory $passwordHashFactory,
        protected readonly ContainerInterface $container,
        protected readonly FormProtectionFactory $formProtectionFactory,
        protected readonly SessionService $sessionService
    ) {}

    /**
     * Handles an Install Tool request for normal operations
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->canHandleRequest()) {
            return $handler->handle($request);
        }

        if (($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] ?? '') === '') {
            return new HtmlResponse('$GLOBALS[\'TYPO3_CONF_VARS\'][\'BE\'][\'installToolPassword\'] must not be empty.', 500);
        }

        $controllerName = $request->getQueryParams()['install']['controller'] ?? 'layout';
        $actionName = $request->getParsedBody()['install']['action'] ?? $request->getQueryParams()['install']['action'] ?? 'init';

        if ($actionName === 'showEnableInstallToolFile' && EnableFileService::isInstallToolEnableFilePermanent()) {
            $actionName = 'showLogin';
        }

        $action = $actionName . 'Action';

        // not session related actions
        if ($actionName === 'init') {
            $controller = $this->container->get(LayoutController::class);
            return $controller->initAction($request);
        }
        if ($actionName === 'checkEnableInstallToolFile') {
            return new JsonResponse([
                'success' => $this->checkEnableInstallToolFile(),
            ]);
        }
        if ($actionName === 'showEnableInstallToolFile') {
            $controller = $this->container->get(LoginController::class);
            return $controller->showEnableInstallToolFileAction($request);
        }
        if ($actionName === 'showLogin') {
            if (!$this->checkEnableInstallToolFile()) {
                throw new \RuntimeException('Not authorized', 1505564888);
            }
            $controller = $this->container->get(LoginController::class);
            return $controller->showLoginAction($request);
        }

        $this->sessionService->installSessionHandler();
        // the backend user has an active session but the admin / maintainer
        // rights have been revoked or the user was disabled or deleted in the meantime
        if ($this->sessionService->isAuthorizedBackendUserSession($request) && !$this->sessionService->hasActiveBackendUserRoleAndSession()) {
            // log out the user and destroy the session
            $this->sessionService->resetSession();
            $this->sessionService->destroySession($request);
            $formProtection = $this->formProtectionFactory->createFromRequest($request);
            $formProtection->clean();

            return new HtmlResponse('', 403);
        }

        if ($actionName === 'preAccessCheck') {
            $response = new JsonResponse([
                'installToolLocked' => !$this->checkEnableInstallToolFile(),
                'isAuthorized' => $this->sessionService->isAuthorized($request),
            ]);
        } elseif ($actionName === 'checkLogin') {
            if (!$this->checkEnableInstallToolFile() && !$this->sessionService->isAuthorizedBackendUserSession($request)) {
                throw new \RuntimeException('Not authorized', 1505563556);
            }
            if ($this->sessionService->isAuthorized($request)) {
                $this->sessionService->refreshSession();
                $response = new JsonResponse([
                    'success' => true,
                ]);
            } else {
                // Session expired, log out user, start new session
                $this->sessionService->resetSession();
                $this->sessionService->startSession();
                $response = new JsonResponse([
                    'success' => false,
                ]);
            }
        } elseif ($actionName === 'login') {
            $this->sessionService->initializeSession();
            if (!$this->checkEnableInstallToolFile()) {
                throw new \RuntimeException('Not authorized', 1505567462);
            }
            $this->checkSessionToken($request);
            $this->checkSessionLifetime($request);
            $password = $request->getParsedBody()['install']['password'] ?? null;
            $authService = $this->container->get(AuthenticationService::class);
            if ($authService->loginWithPassword($password, $request, $this->sessionService)) {
                $response = new JsonResponse([
                    'success' => true,
                ]);
            } else {
                if ($password === null || empty($password)) {
                    $messageQueue = new FlashMessageQueue('install');
                    $messageQueue->enqueue(
                        new FlashMessage('Please enter the install tool password', '', ContextualFeedbackSeverity::ERROR)
                    );
                } else {
                    $hashInstance = $this->passwordHashFactory->getDefaultHashInstance('BE');
                    $hashedPassword = $hashInstance->getHashedPassword($password);
                    $messageQueue = new FlashMessageQueue('install');
                    $messageQueue->enqueue(
                        new FlashMessage(
                            'Given password does not match the install tool login password. Calculated hash: ' . $hashedPassword,
                            '',
                            ContextualFeedbackSeverity::ERROR
                        )
                    );
                }
                $response = new JsonResponse([
                    'success' => false,
                    'status' => $messageQueue,
                ]);
            }
        } elseif ($actionName === 'logout') {
            if (EnableFileService::installToolEnableFileExists() && !EnableFileService::isInstallToolEnableFilePermanent()) {
                EnableFileService::removeInstallToolEnableFile();
            }
            $formProtection = $this->formProtectionFactory->createFromRequest($request);
            $formProtection->clean();
            $this->sessionService->destroySession($request);
            $response = new JsonResponse([
                'success' => true,
            ]);
        } else {
            $enforceReferrerResponse = $this->enforceReferrer($request);
            if ($enforceReferrerResponse !== null) {
                return $enforceReferrerResponse;
            }
            $this->sessionService->initializeSession();
            if (
                !$this->checkSessionToken($request)
                || !$this->checkSessionLifetime($request)
                || !$this->sessionService->isAuthorized($request)
            ) {
                return new HtmlResponse('', 403);
            }
            $this->sessionService->refreshSession();
            if (!array_key_exists($controllerName, $this->controllers)) {
                throw new \RuntimeException(
                    'Unknown controller ' . $controllerName,
                    1505215756
                );
            }
            $this->packageManager->recreatePackageStatesFileIfMissing();
            $className = $this->controllers[$controllerName];
            /** @var AbstractController $controller */
            $controller = $this->container->get($className);
            if (!method_exists($controller, $action)) {
                throw new \RuntimeException(
                    'Unknown action method ' . $action . ' in controller ' . $controllerName,
                    1505216027
                );
            }
            $response = $controller->$action($request);
        }

        return $response;
    }

    /**
     * This request handler is only accessible when basic system integrity constraints are fulfilled.
     */
    protected function canHandleRequest(): bool
    {
        $basicIntegrity = $this->checkIfEssentialConfigurationExists() && !EnableFileService::isFirstInstallAllowed();
        if (!$basicIntegrity) {
            return false;
        }
        return true;
    }

    /**
     * Checks if ENABLE_INSTALL_TOOL exists.
     */
    protected function checkEnableInstallToolFile(): bool
    {
        return EnableFileService::checkInstallToolEnableFile();
    }

    /**
     * Use form protection API to find out if protected POST forms are ok.
     */
    protected function checkSessionToken(ServerRequestInterface $request): bool
    {
        $postValues = $request->getParsedBody()['install'] ?? null;
        // no post data is there, so no token check necessary
        if (empty($postValues)) {
            return true;
        }
        $tokenOk = false;
        // A token must be given as soon as there is POST data
        if (isset($postValues['token'])) {
            $formProtection = $this->formProtectionFactory->createFromRequest($request);
            $action = (string)$postValues['action'];
            if ($action === '') {
                throw new \RuntimeException(
                    'No POST action given for token check',
                    1369326593
                );
            }
            $tokenOk = $formProtection->validateToken($postValues['token'], 'installTool', $action);
        }
        if (!$tokenOk) {
            $this->sessionService->resetSession();
            $this->sessionService->startSession();
        }
        return $tokenOk;
    }

    /**
     * Check if session expired.
     * If the session has expired, the login form is displayed.
     *
     * @return bool True if session lifetime is OK
     */
    protected function checkSessionLifetime(ServerRequestInterface $request): bool
    {
        $isExpired = $this->sessionService->isExpired($request);
        if ($isExpired) {
            // Session expired, log out user, start new session
            $this->sessionService->resetSession();
            $this->sessionService->startSession();
        }
        return !$isExpired;
    }

    /**
     * Check if system/settings.php exists (PackageStates is optional)
     *
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     */
    protected function checkIfEssentialConfigurationExists(): bool
    {
        if (file_exists($this->configurationManager->getSystemConfigurationFileLocation())) {
            return true;
        }
        // Check can be removed with TYPO3 v14.0
        if (file_exists($this->configurationManager->getLocalConfigurationFileLocation())) {
            mkdir(dirname($this->configurationManager->getSystemConfigurationFileLocation()), 02775, true);
            rename($this->configurationManager->getLocalConfigurationFileLocation(), $this->configurationManager->getSystemConfigurationFileLocation());
            if (file_exists(Environment::getLegacyConfigPath() . '/AdditionalConfiguration.php')) {
                rename(Environment::getLegacyConfigPath() . '/AdditionalConfiguration.php', $this->configurationManager->getAdditionalConfigurationFileLocation());
            }

            return file_exists($this->configurationManager->getSystemConfigurationFileLocation());
        }
        return false;
    }

    /**
     * Evaluates HTTP `Referer` header (which is denied by client to be a custom
     * value) - attempts to ensure the value is given using a HTML client refresh.
     * see: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referer
     */
    protected function enforceReferrer(ServerRequestInterface $request): ?ResponseInterface
    {
        if (!(new Features())->isFeatureEnabled('security.backend.enforceReferrer')) {
            return null;
        }
        return (new ReferrerEnforcer())->handle($request, [
            'flags' => ['refresh-always'],
            'subject' => 'Install Tool',
        ]);
    }
}
