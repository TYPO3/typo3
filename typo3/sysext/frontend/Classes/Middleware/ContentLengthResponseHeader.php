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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Add content-length HTTP header to the response.
 *
 * Notice that all Content outside the length of the content-length header will be cut off!
 * Therefore, content of unknown length from later-on middlewares and if admin users are logged
 * in (admin panel might show...), we disable it!
 *
 * @internal
 */
class ContentLengthResponseHeader implements MiddlewareInterface
{
    public function __construct(private readonly Context $context) {}

    /**
     * Adds the content length
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $tsfe = $request->getAttribute('frontend.controller');
        if ($tsfe instanceof TypoScriptFrontendController) {
            if (
                (!isset($tsfe->config['config']['enableContentLengthHeader']) || $tsfe->config['config']['enableContentLengthHeader'])
                && !$this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)
                && !$this->context->getPropertyFromAspect('workspace', 'isOffline', false)
            ) {
                $response = $response->withHeader('Content-Length', (string)$response->getBody()->getSize());
            }
        }
        return $response;
    }
}
