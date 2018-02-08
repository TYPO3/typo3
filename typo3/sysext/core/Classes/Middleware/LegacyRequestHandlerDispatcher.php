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
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\NullResponse;

/**
 * Dispatch legacy request handlers; although
 * Bootstrap::registerRequestHandlerImplementation() was always
 * marked as internal quite some extensions use that method
 * to register custom request handlers.
 *
 * @internal
 */
class LegacyRequestHandlerDispatcher implements MiddlewareInterface
{
    protected $bootstrap;

    public function __construct()
    {
        $this->bootstrap = Bootstrap::getInstance();
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHandler = null;

        try {
            $requestHandler = $this->bootstrap->resolveRequestHandler($request);
        } catch (Exception $e) {
            // 'No suitable request handler found.'
            if ($e->getCode() !== 1225418233) {
                throw $e;
            }
        }

        if ($requestHandler !== null) {
            // @todo: E_USER_DEPRECATED
            return $requestHandler->handleRequest($request) ?? new NullResponse();
        }

        return $handler->handle($request);
    }
}
