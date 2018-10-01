<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Feedit\Middleware;

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
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Feedit\DataHandling\FrontendEditDataHandler;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * PSR-15 middleware initializing frontend editing
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:feedit and not part of TYPO3's Core API.
 */
class FrontendEditInitiator implements MiddlewareInterface
{

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            $config = $GLOBALS['BE_USER']->getTSConfig()['admPanel.'] ?? [];
            $active = (int)$GLOBALS['TSFE']->displayEditIcons === 1 || (int)$GLOBALS['TSFE']->displayFieldEditIcons === 1;
            // Include classes for editing IF editing module in Admin Panel is open
            if ($active && isset($config['enable.'])) {
                foreach ($config['enable.'] as $value) {
                    if ($value) {
                        $parameters = $request->getParsedBody()['TSFE_EDIT'] ?? $request->getQueryParams()['TSFE_EDIT'] ?? null;
                        $isValidEditAction = $this->isValidEditAction($parameters);
                        if ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
                            // Grab the Page TSConfig property that determines which controller to use.
                            $pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
                            $controllerKey = $pageTSConfig['TSFE.']['frontendEditingController'] ?? 'default';
                        } else {
                            $controllerKey = 'default';
                        }
                        /** @deprecated will be removed in TYPO3 v10.0. */
                        $controllerClassName = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['frontendEditingController'][$controllerKey] ?? \TYPO3\CMS\Core\FrontendEditing\FrontendEditingController::class;
                        if (!empty($controllerClassName)) {
                            /** @deprecated will be removed in TYPO3 v10.0. */
                            $GLOBALS['BE_USER']->frontendEdit = GeneralUtility::makeInstance(
                                $controllerClassName,
                                $parameters
                            );
                        }
                        if ($isValidEditAction) {
                            GeneralUtility::makeInstance(FrontendEditDataHandler::class, $parameters)->editAction();
                        }
                        break;
                    }
                }
            }
        }
        return $handler->handle($request);
    }

    /**
     * Returns TRUE if an edit-action is sent from the Admin Panel
     *
     * @param array|null $parameters
     * @return bool
     */
    protected function isValidEditAction(array &$parameters = null): bool
    {
        if (!is_array($parameters)) {
            return false;
        }
        if ($parameters['cancel']) {
            unset($parameters['cmd']);
        } else {
            $cmd = (string)$parameters['cmd'];
            if (($cmd !== 'edit' || is_array($parameters['data']) && ($parameters['doSave'] || $parameters['update'] || $parameters['update_close'])) && $cmd !== 'new') {
                // $cmd can be a command like "hide" or "move". If $cmd is "edit" or "new" it's an indication to show the formfields. But if data is sent with update-flag then $cmd = edit is accepted because edit may be sent because of .keepGoing flag.
                return true;
            }
        }
        return false;
    }
}
