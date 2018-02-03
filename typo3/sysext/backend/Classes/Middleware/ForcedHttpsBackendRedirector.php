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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Check lockSSL configuration variable and redirect
 * to https version of the backend if needed
 *
 * @internal
 */
class ForcedHttpsBackendRedirector implements MiddlewareInterface
{
    /**
     * @todo Remove getIndpEnv() usage once $request contains all the site parameters (URL etc.)
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ((bool)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSL'] && !GeneralUtility::getIndpEnv('TYPO3_SSL')) {
            if ((int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort']) {
                $sslPortSuffix = ':' . (int)$GLOBALS['TYPO3_CONF_VARS']['BE']['lockSSLPort'];
            } else {
                $sslPortSuffix = '';
            }
            list(, $url) = explode('://', GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir, 2);
            list($server, $address) = explode('/', $url, 2);
            return new RedirectResponse('https://' . $server . $sslPortSuffix . '/' . $address);
        }

        return $handler->handle($request);
    }
}
