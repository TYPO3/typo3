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
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * PSR-15 middleware initializing frontend editing
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
        if ($GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication) {
            $config = $GLOBALS['BE_USER']->getTSConfigProp('admPanel');
            $active = (int)$GLOBALS['TSFE']->displayEditIcons === 1 || (int)$GLOBALS['TSFE']->displayFieldEditIcons === 1;
            if ($active && isset($config['enable.'])) {
                foreach ($config['enable.'] as $value) {
                    if ($value) {
                        if ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController) {
                            // Grab the Page TSConfig property that determines which controller to use.
                            $pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
                            $controllerKey = $pageTSConfig['TSFE.']['frontendEditingController'] ?? 'default';
                        } else {
                            $controllerKey = 'default';
                        }
                        $controllerClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tsfebeuserauth.php']['frontendEditingController'][$controllerKey];
                        if ($controllerClass) {
                            $GLOBALS['BE_USER']->frontendEdit = GeneralUtility::makeInstance($controllerClass);
                        }
                        break;
                    }
                }
            }
        }
        return $handler->handle($request);
    }
}
