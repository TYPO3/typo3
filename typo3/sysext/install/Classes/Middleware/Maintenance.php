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
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Security\ReferrerEnforcer;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\FailsafePackageManager;
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
     * @var FailsafePackageManager
     */
    protected $packageManager;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var PasswordHashFactory
     */
    protected $passwordHashFactory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array List of valid controllers
     */
    protected $controllers = [
        'icon' => IconController::class,
        'layout' => LayoutController::class,
        'login' => LoginController::class,
        'maintenance' => MaintenanceController::class,
        'settings' => SettingsController::class,
        'upgrade' => UpgradeController::class,
        'environment' => EnvironmentController::class,
    ];

    public function __construct(
        FailsafePackageManager $packageManager,
        ConfigurationManager $configurationManager,
        PasswordHashFactory $passwordHashFactory,
        ContainerInterface $container
    ) {
        $this->packageManager = $packageManager;
        $this->configurationManager = $configurationManager;
        $this->passwordHashFactory = $passwordHashFactory;
        $this->container = $container;
    }

    /**
     * Handles an Install Tool request for normal operations
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->canHandleRequest($request)) {
            return $handler->handle($request);
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

        // session related actions
        $session = new SessionService();

        // the backend user has an active session but the admin / maintainer
        // rights have been revoked or the user was disabled or deleted in the meantime
        if ($session->isAuthorizedBackendUserSession() && !$session->hasActiveBackendUserRoleAndSession()) {
            // log out the user and destroy the session
            $session->resetSession();
            $session->destroySession();
            $formProtection = FormProtectionFactory::get(
                InstallToolFormProtection::class
            );
            $formProtection->clean();

            return new HtmlResponse('', 403);
        }

        if ($actionName === 'preAccessCheck') {
            $response = new JsonResponse([
                'installToolLocked' => !$this->checkEnableInstallToolFile(),
                'isAuthorized' => $session->isAuthorized(),
            ]);
        } elseif ($actionName === 'checkLogin') {
            if (!$this->checkEnableInstallToolFile() && !$session->isAuthorizedBackendUserSession()) {
                throw new \RuntimeException('Not authorized', 1505563556);
            }
            if ($session->isAuthorized()) {
                $session->refreshSession();
                $response = new JsonResponse([
                    'success' => true,
                ]);
            } else {
                // Session expired, log out user, start new session
                $session->resetSession();
                $session->startSession();
                $response = new JsonResponse([
                    'success' => false,
                ]);
            }
        } elseif ($actionName === 'login') {
            $session->initializeSession();
            if (!$this->checkEnableInstallToolFile()) {
                throw new \RuntimeException('Not authorized', 1505567462);
            }
            $this->checkSessionToken($request, $session);
            $this->checkSessionLifetime($session);
            $password = $request->getParsedBody()['install']['password'] ?? null;
            $authService = new AuthenticationService($session);
            if ($authService->loginWithPassword($password, $request)) {
                $response = new JsonResponse([
                    'success' => true,
                ]);
            } else {
                if ($password === null || empty($password)) {
                    $messageQueue = (new FlashMessageQueue('install'))->enqueue(
                        new FlashMessage('Please enter the install tool password', '', FlashMessage::ERROR)
                    );
                } else {
                    $hashInstance = $this->passwordHashFactory->getDefaultHashInstance('BE');
                    $hashedPassword = $hashInstance->getHashedPassword($password);
                    $messageQueue = (new FlashMessageQueue('install'))->enqueue(
                        new FlashMessage(
                            'Given password does not match the install tool login password. Calculated hash: ' . $hashedPassword,
                            '',
                            FlashMessage::ERROR
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
            $formProtection = FormProtectionFactory::get(
                InstallToolFormProtection::class
            );
            $formProtection->clean();
            $session->destroySession();
            $response = new JsonResponse([
                'success' => true,
            ]);
        } else {
            $enforceReferrerResponse = $this->enforceReferrer($request);
            if ($enforceReferrerResponse instanceof ResponseInterface) {
                return $enforceReferrerResponse;
            }
            $session->initializeSession();
            if (
                !$this->checkSessionToken($request, $session)
                || !$this->checkSessionLifetime($session)
                || !$session->isAuthorized()
            ) {
                return new HtmlResponse('', 403);
            }
            $session->refreshSession();
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
     * This request handler can handle any request when not in CLI mode.
     * Warning: Order of these methods is security relevant and interferes with different access
     * conditions (new/existing installation). See the single method comments for details.
     *
     * @param ServerRequestInterface $request
     * @return bool Returns always TRUE
     */
    protected function canHandleRequest(ServerRequestInterface $request): bool
    {
        $basicIntegrity = $this->checkIfEssentialConfigurationExists()
            && !empty($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'])
            && !EnableFileService::isFirstInstallAllowed();
        if (!$basicIntegrity) {
            return false;
        }
        return true;
    }

    /**
     * Checks if ENABLE_INSTALL_TOOL exists.
     *
     * @return bool
     */
    protected function checkEnableInstallToolFile()
    {
        return EnableFileService::checkInstallToolEnableFile();
    }

    /**
     * Use form protection API to find out if protected POST forms are ok.
     *
     * @param ServerRequestInterface $request
     * @param SessionService $session
     * @return bool
     */
    protected function checkSessionToken(ServerRequestInterface $request, SessionService $session): bool
    {
        $postValues = $request->getParsedBody()['install'] ?? null;
        // no post data is there, so no token check necessary
        if (empty($postValues)) {
            return true;
        }
        $tokenOk = false;
        // A token must be given as soon as there is POST data
        if (isset($postValues['token'])) {
            $formProtection = FormProtectionFactory::get(
                InstallToolFormProtection::class
            );
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
            $session->resetSession();
            $session->startSession();
        }
        return $tokenOk;
    }

    /**
     * Check if session expired.
     * If the session has expired, the login form is displayed.
     *
     * @param SessionService $session
     * @return bool True if session lifetime is OK
     */
    protected function checkSessionLifetime(SessionService $session): bool
    {
        $isExpired = $session->isExpired();
        if ($isExpired) {
            // Session expired, log out user, start new session
            $session->resetSession();
            $session->startSession();
        }
        return !$isExpired;
    }

    /**
     * Check if LocalConfiguration.php exists (PackageStates is optional)
     *
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     */
    protected function checkIfEssentialConfigurationExists(): bool
    {
        return file_exists($this->configurationManager->getLocalConfigurationFileLocation());
    }

    /**
     * Evaluates HTTP `Referer` header (which is denied by client to be a custom
     * value) - attempts to ensure the value is given using a HTML client refresh.
     * see: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referer
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    protected function enforceReferrer(ServerRequestInterface $request): ?ResponseInterface
    {
        if (!(new Features())->isFeatureEnabled('security.backend.enforceReferrer')) {
            return null;
        }
        return (new ReferrerEnforcer($request))->handle([
            'flags' => ['refresh-always'],
            'subject' => 'Install Tool',
        ]);
    }
}
