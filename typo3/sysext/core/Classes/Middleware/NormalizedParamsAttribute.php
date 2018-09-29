<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Middleware;

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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\NormalizedParams;

/**
 * Add NormalizedParams as 'normalizedParams' attribute.
 * Used in FE, BE and install tool context.
 *
 * @internal
 */
class NormalizedParamsAttribute implements MiddlewareInterface
{
    /**
     * Adds an instance of TYPO3\CMS\Core\Http\NormalizedParams as
     * attribute to $request object
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute(
            'normalizedParams',
            new NormalizedParams(
                $request->getServerParams(),
                $GLOBALS['TYPO3_CONF_VARS']['SYS'],
                Environment::getCurrentScript(),
                Environment::getPublicPath()
            )
        );

        // Set $request as global variable. This is needed in a transition phase until core code has been
        // refactored to have ServerRequest object available where it is needed. This global will be
        // deprecated then and removed.
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return $handler->handle($request);
    }
}
