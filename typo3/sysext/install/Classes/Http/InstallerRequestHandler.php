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
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Install\Controller\InstallerController;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Request handler to walk through the web installation process of TYPO3
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class InstallerRequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
{
    /**
     * Handles an Install Tool request when nothing is there
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Handles an Install Tool request when nothing is there
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $controller = new InstallerController();
        $actionName = $request->getParsedBody()['install']['action'] ?? $request->getQueryParams()['install']['action'] ?? 'init';
        $action = $actionName . 'Action';

        if ($actionName === 'init' || $actionName === 'mainLayout') {
            $response = $controller->$action();
        } elseif ($actionName === 'checkInstallerAvailable') {
            $response = new JsonResponse([
                'success' => $this->isInstallerAvailable(),
            ]);
        } elseif ($actionName === 'showInstallerNotAvailable') {
            $response = $controller->showInstallerNotAvailableAction();
        } elseif ($actionName === 'checkEnvironmentAndFolders'
            || $actionName === 'showEnvironmentAndFolders'
            || $actionName === 'executeEnvironmentAndFolders'
        ) {
            $this->throwIfInstallerIsNotAvailable();
            $response = $controller->$action($request);
        } else {
            $this->throwIfInstallerIsNotAvailable();
            // With main folder layout available, sessions can be handled
            $session = new SessionService();
            if (!$session->hasSession()) {
                $session->startSession();
            }
            if ($session->isExpired()) {
                $session->refreshSession();
            }
            $postValues = $request->getParsedBody()['install'];
            $sessionTokenOk = false;
            if (empty($postValues)) {
                // No post data is there, no token check necessary
                $sessionTokenOk = true;
            }
            if (isset($postValues['token'])) {
                // A token must be given as soon as there is POST data
                $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
                if ($actionName === '') {
                    throw new \RuntimeException('No POST action given for token check', 1505647681);
                }
                $sessionTokenOk = $formProtection->validateToken($postValues['token'], 'installTool', $actionName);
            }
            if (!$sessionTokenOk) {
                $session->resetSession();
                $session->startSession();
                throw new \RuntimeException('Invalid session token', 1505647737);
            }

            if (!method_exists($controller, $action)) {
                // Sanitize action method, preventing injecting whatever method name
                throw new \RuntimeException(
                    'Unknown action method ' . $action . ' in controller InstallerController',
                    1505687700
                );
            }

            $response = $controller->$action($request);

            if ($actionName === 'executeDefaultConfiguration') {
                // Executing last step cleans session
                $session->destroySession();
            }
        }

        return $response;
    }

    /**
     * First installation is in progress, if LocalConfiguration does not exist,
     * or if FIRST_INSTALL file exists.
     *
     * @param ServerRequestInterface $request
     * @return bool Returns always TRUE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        $localConfigurationFileLocation = (new ConfigurationManager())->getLocalConfigurationFileLocation();
        return !@is_file($localConfigurationFileLocation) || EnableFileService::isFirstInstallAllowed();
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 20;
    }

    /**
     * @throws \RuntimeException If installer is not available due to missing FIRST_INSTALL
     */
    protected function throwIfInstallerIsNotAvailable()
    {
        if (!$this->isInstallerAvailable()) {
            throw new \RuntimeException(
                'Installer not available',
                1505637427
            );
        }
    }

    /**
     * @return bool TRUE if FIRST_INSTALL file exists
     */
    protected function isInstallerAvailable(): bool
    {
        if (EnableFileService::isFirstInstallAllowed()) {
            return true;
        }
        return false;
    }
}
