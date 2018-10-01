<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Http;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
 * Default request handler for all requests inside the TYPO3 Install Tool, which does a simple hardcoded
 * dispatching to a controller based on the get/post variable.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class RequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
{
    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

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

    /**
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Handles an Install Tool request for normal operations
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Handles an Install Tool request for normal operations
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controllerName = $request->getQueryParams()['install']['controller'] ?? 'layout';
        $actionName = $request->getParsedBody()['install']['action'] ?? $request->getQueryParams()['install']['action'] ?? 'init';
        $action = $actionName . 'Action';

        $session = $this->initializeSession();
        if ($actionName === 'init') {
            $controller = new LayoutController();
            $response = $controller->initAction($request);
        } elseif ($actionName === 'checkEnableInstallToolFile') {
            $response = new JsonResponse([
                'success' => $this->checkEnableInstallToolFile(),
            ]);
        } elseif ($actionName === 'showEnableInstallToolFile') {
            $controller = new LoginController();
            $response = $controller->showEnableInstallToolFileAction($request);
        } elseif ($actionName === 'checkLogin') {
            if (!$this->checkEnableInstallToolFile() && !$session->isAuthorizedBackendUserSession()) {
                throw new \RuntimeException('Not authorized', 1505563556);
            }
            if ($session->isExpired() || !$session->isAuthorized()) {
                // Session expired, log out user, start new session
                $session->resetSession();
                $session->startSession();
                $response = new JsonResponse([
                    'success' => false,
                ]);
            } else {
                $session->refreshSession();
                $response = new JsonResponse([
                    'success' => true,
                ]);
            }
        } elseif ($actionName === 'showLogin') {
            if (!$this->checkEnableInstallToolFile()) {
                throw new \RuntimeException('Not authorized', 1505564888);
            }
            $controller = new LoginController();
            $response = $controller->showLoginAction($request);
        } elseif ($actionName === 'login') {
            if (!$this->checkEnableInstallToolFile()) {
                throw new \RuntimeException('Not authorized', 1505567462);
            }
            $this->checkSessionToken($request, $session);
            $this->checkSessionLifetime($session);
            $password = $request->getParsedBody()['install']['password'] ?? null;
            $authService = new AuthenticationService($session);
            if ($authService->loginWithPassword($password)) {
                $response = new JsonResponse([
                    'success' => true,
                ]);
            } else {
                if ($password === null || empty($password)) {
                    $messageQueue = (new FlashMessageQueue('install'))->enqueue(
                        new FlashMessage('Please enter the install tool password', '', FlashMessage::ERROR)
                    );
                } else {
                    $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('BE');
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
            $this->recreatePackageStatesFileIfMissing();
            /** @var AbstractController $controller */
            $controller = new $this->controllers[$controllerName];
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
    public function canHandleRequest(ServerRequestInterface $request): bool
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
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 50;
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
     * Initialize session object.
     * Subclass will throw exception if session can not be created or if
     * preconditions like a valid encryption key are not set.
     *
     * @return SessionService
     */
    protected function initializeSession()
    {
        $session = new SessionService();
        if (!$session->hasSession()) {
            $session->startSession();
        }
        return $session;
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
        $postValues = $request->getParsedBody()['install'];
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
     * Check if LocalConfiguration.php and PackageStates.php exist
     *
     * @return bool TRUE when the essential configuration is available, otherwise FALSE
     */
    protected function checkIfEssentialConfigurationExists(): bool
    {
        return file_exists($this->configurationManager->getLocalConfigurationFileLocation());
    }

    /**
     * Create PackageStates.php if missing and LocalConfiguration exists.
     *
     * It is fired if PackageStates.php is deleted on a running instance,
     * all packages marked as "part of minimal system" are activated in this case.
     */
    protected function recreatePackageStatesFileIfMissing(): void
    {
        if (!file_exists(Environment::getLegacyConfigPath() . '/PackageStates.php')) {
            // We need a FailsafePackageManager at this moment, however this is given
            // As Bootstrap is registering the FailsafePackageManager object as a singleton instance
            // of the main PackageManager class. See \TYPO3\CMS\Core\Core\Bootstrap::init()
            /** @var \TYPO3\CMS\Core\Package\FailsafePackageManager $packageManager */
            $packageManager = GeneralUtility::makeInstance(PackageManager::class);
            $packages = $packageManager->getAvailablePackages();
            foreach ($packages as $package) {
                if ($package instanceof PackageInterface && $package->isPartOfMinimalUsableSystem()) {
                    $packageManager->activatePackage($package->getPackageKey());
                }
            }
            $packageManager->forceSortAndSavePackageStates();
        }
    }
}
