<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Http;

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
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * AJAX dispatcher
 *
 * Main entry point for AJAX calls in the TYPO3 Backend. Based on ?route=/ajax/* of the outside application.
 *
 * AJAX Requests are typically registered within EXT:myext/Configuration/Backend/AjaxRoutes.php
 *
 * @deprecated since TYPO3 v9.2, will be removed in TYPO3 v10.0
 */
class AjaxRequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
{
    /**
     * Handles any AJAX request in the TYPO3 Backend
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use the regular application dispatcher instead.', E_USER_DEPRECATED);
        return $this->handle($request);
    }

    /**
     * Handles any AJAX request in the TYPO3 Backend, after finishing running middlewares
     *
     * Creates a response object with JSON headers automatically, and then dispatches to the correct route
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws ResourceNotFoundException if no valid route was found
     * @throws InvalidRequestTokenException if the request could not be verified
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use the regular application dispatcher instead.', E_USER_DEPRECATED);
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class, 'php://temp', 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-JSON' => 'true'
        ]);

        /** @var RouteDispatcher $dispatcher */
        $dispatcher = GeneralUtility::makeInstance(RouteDispatcher::class);
        return $dispatcher->dispatch($request, $response);
    }

    /**
     * This request handler can handle any backend request having
     * an /ajax/ request
     *
     * @param ServerRequestInterface $request
     * @return bool If the request is an AJAX backend request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use the regular application dispatcher instead.', E_USER_DEPRECATED);
        $routePath = $request->getParsedBody()['route'] ?? $request->getQueryParams()['route'] ?? '';
        return strpos($routePath, '/ajax/') === 0;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use the regular application dispatcher instead.', E_USER_DEPRECATED);
        return 80;
    }
}
