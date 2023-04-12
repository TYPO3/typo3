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
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    protected ?SessionService $sessionService = null;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory
    ) {
    }

    /**
     * Initialize session and redirect to "maintenance"
     */
    public function maintenanceAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->setAuthorizedAndRedirect('maintenance', $request);
    }

    /**
     * Initialize session and redirect to "settings"
     */
    public function settingsAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->setAuthorizedAndRedirect('settings', $request);
    }

    /**
     * Initialize session and redirect to "upgrade"
     */
    public function upgradeAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->setAuthorizedAndRedirect('upgrade', $request);
    }

    /**
     * Initialize session and redirect to "environment"
     */
    public function environmentAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->setAuthorizedAndRedirect('environment', $request);
    }

    /**
     * Starts / updates the session and redirects to the Install Tool
     * with given action.
     */
    protected function setAuthorizedAndRedirect(string $controller, ServerRequestInterface $request): ResponseInterface
    {
        $userSession = $this->getBackendUser()->getSession();
        $this->getSessionService()->setAuthorizedBackendSession($userSession);
        $entryPointResolver = GeneralUtility::makeInstance(BackendEntryPointResolver::class);
        $redirectLocation = $entryPointResolver->getUriFromRequest($request, 'install.php')->withQuery('?install[controller]=' . $controller . '&install[context]=backend');
        return new RedirectResponse($redirectLocation, 303);
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
