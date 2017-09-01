<?php
namespace TYPO3\CMS\Install\Controller;

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

/**
 * Backend module controller to dispatch to the main modules or to an AJAX request
 *
 * This is a classic backend module that does not interfere with other code
 * within the install tool, it can be seen as a facade around install tool just
 * to embed the install tool in backend.
 */
class BackendModuleController
{
    /**
     * Renders the maintenance tool action (or AJAX, if it was specifically requested)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function maintenanceAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->executeSpecificToolAction($request, 'maintenance');
    }

    /**
     * Renders the settings tool action (or AJAX, if it was specifically requested)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function settingsAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->executeSpecificToolAction($request, 'settings');
    }

    /**
     * Renders the upgrade tool action (or AJAX, if it was specifically requested)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function upgradeAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->executeSpecificToolAction($request, 'upgrade');
    }

    /**
     * Renders the environment tool action (or AJAX, if it was specifically requested)
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function environmentAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->executeSpecificToolAction($request, 'environment');
    }

    /**
     * Sets the action inside the install tool to a specific action and calls the "toolcontroller" afterwards
     *
     * @param ServerRequestInterface $request
     * @param $action
     * @return ResponseInterface
     */
    protected function executeSpecificToolAction(ServerRequestInterface $request, $action): ResponseInterface
    {
        $request = $request->withAttribute('context', 'backend');
        // Can be moved into one controller in my opinion now, or should go into a dispatcher that
        // also deals with actions
        if ($request->getQueryParams()['install']['controller'] === 'ajax') {
            return $this->handleAjaxRequest($request);
        }
        $queryParameters = $request->getQueryParams();
        $queryParameters['install']['action'] = $action;
        $request = $request->withQueryParams($queryParameters);
        return (new ToolController())->execute($request);
    }

    /**
     * Calls the AJAX controller (if requested as "controller")
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handleAjaxRequest(ServerRequestInterface $request): ResponseInterface
    {
        return (new AjaxController())->execute($request);
    }
}
