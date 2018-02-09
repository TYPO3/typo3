<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

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
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Send content-length header.
 * Notice that all HTML content outside the length of the content-length header will be cut off!
 * Therefore content of unknown length from included PHP-scripts and if admin users are logged
 * in (admin panel might show...) or if debug mode is turned on, we disable it!
 *
 * @internal
 */
class ContentLengthResponseHeader implements MiddlewareInterface
{

    /**
     * Adds the content length
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        if (
            !($response instanceof NullResponse)
            && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController
            && $GLOBALS['TSFE']->isOutputting()) {
            if (
                    (!isset($GLOBALS['TSFE']->config['config']['enableContentLengthHeader']) || $GLOBALS['TSFE']->config['config']['enableContentLengthHeader'])
                    && !$GLOBALS['TSFE']->isBackendUserLoggedIn() && !$GLOBALS['TYPO3_CONF_VARS']['FE']['debug']
                    && !$GLOBALS['TSFE']->config['config']['debug'] && !$GLOBALS['TSFE']->doWorkspacePreview()
                ) {
                $response = $response->withHeader('Content-Length', (string)$response->getBody()->getSize());
            }
        }
        return $response;
    }
}
