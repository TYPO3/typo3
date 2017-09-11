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
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Install tool controller, dispatcher class of the install tool.
 *
 * Handles install tool session, login and login form rendering,
 * calls actions that need authentication and handles form tokens.
 */
class ToolController extends AbstractController
{
    /**
     * @var array List of valid action names that need authentication
     */
    protected $authenticationActions = [
        'environment',
        'maintenance',
        'settings',
        'upgrade',
    ];

    /**
     * Main dispatch method
     *
     * @param $request ServerRequestInterface
     * @return ResponseInterface
     * @throws Exception
     */
    public function execute(ServerRequestInterface $request): ResponseInterface
    {
        $action = $this->sanitizeAction($request->getParsedBody()['install']['action'] ?? $request->getQueryParams()['install']['action'] ?? '');
        if ($action === '') {
            $action = 'maintenance';
        }
        $this->validateAuthenticationAction($action);
        $actionClass = ucfirst($action);
        /** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $toolAction */
        $toolAction = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\' . $actionClass);
        if (!($toolAction instanceof Action\ActionInterface)) {
            throw new Exception(
                $action . ' does not implement ActionInterface',
                1369474309
            );
        }
        $toolAction->setController('tool');
        $toolAction->setAction($action);
        $toolAction->setToken($this->generateTokenForAction($action));
        $toolAction->setContext($request->getQueryParams()['install']['context']);
        $toolAction->setPostValues($request->getParsedBody()['install'] ?? []);
        return $toolAction->handle();
    }

    /**
     * Show login for if user is not authorized yet
     *
     * @param ServerRequestInterface $request
     * @param FlashMessage $message
     * @return ResponseInterface
     */
    public function unauthorizedAction(ServerRequestInterface $request, FlashMessage $message = null): ResponseInterface
    {
        return $this->loginForm($request, $message);
    }
}
