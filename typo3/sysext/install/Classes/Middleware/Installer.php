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
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Install\Controller\InstallerController;
use TYPO3\CMS\Install\Service\EnableFileService;
use TYPO3\CMS\Install\Service\SessionService;

/**
 * Middleware to walk through the web installation process of TYPO3
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class Installer implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Handles an Install Tool request when nothing is there
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws \RuntimeException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->canHandleRequest($request)) {
            return $handler->handle($request);
        }

        // Lazy load InstallerController, to instantiate the class and the dependencies only if we handle an install request.
        $controller = $this->container->get(InstallerController::class);
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
            $session->startSession();
            if ($session->isExpired()) {
                $session->refreshSession();
            }
            $postValues = $request->getParsedBody()['install'] ?? [];
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
    protected function canHandleRequest(ServerRequestInterface $request): bool
    {
        $localConfigurationFileLocation = (new ConfigurationManager())->getLocalConfigurationFileLocation();
        return !@is_file($localConfigurationFileLocation) || EnableFileService::isFirstInstallAllowed();
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
