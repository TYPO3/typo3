<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Middleware;

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
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * Check lockSSL configuration variable and redirect
 * to https version of the backend if needed
 *
 * Depends on the NormalizedParams middleware to identify the
 * Site URL and if the page is not running via HTTPS yet.
 *
 * @internal
 */
class ForcedHttpsBackendRedirector implements MiddlewareInterface
{
    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ((bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] && !$request->getAttribute('normalizedParams')->isHttps()) {
            if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort']) {
                $sslPortSuffix = ':' . (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort'];
            } else {
                $sslPortSuffix = '';
            }
            list(, $url) = explode('://', $request->getAttribute('normalizedParams')->getSiteUrl() . TYPO3_mainDir, 2);
            list($server, $address) = explode('/', $url, 2);
            return new RedirectResponse('https://' . $server . $sslPortSuffix . '/' . $address);
        }

        return $handler->handle($request);
    }
}
