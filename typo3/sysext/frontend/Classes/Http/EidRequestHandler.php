<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Http;

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
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Middleware\EidHandler as EidMiddleware;

/**
 * Lightweight alternative to the regular RequestHandler used when $_GET[eID] is set.
 * In the future, logic from the EidUtility will be moved to this class.
 *
 * @deprecated since TYPO3 v9.2, will be removed in TYPO3 v10.0
 */
class EidRequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
{
    /**
     * Constructor handing over the bootstrap and the original request
     */
    public function __construct()
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use ' . EidMiddleware::class . ' instead.', E_USER_DEPRECATED);
    }

    /**
     * Handles a frontend request based on the _GP "eID" variable.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use ' . EidMiddleware::class . ' instead.', E_USER_DEPRECATED);
        return $this->handle($request);
    }

    /**
     * This request handler can handle any frontend request.
     *
     * @param ServerRequestInterface $request The request to process
     * @return bool If the request is not an eID request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use ' . EidMiddleware::class . ' instead.', E_USER_DEPRECATED);
        return !empty($request->getQueryParams()['eID']) || !empty($request->getParsedBody()['eID']);
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use ' . EidMiddleware::class . ' instead.', E_USER_DEPRECATED);
        return 80;
    }

    /**
     * Dispatches the request to the corresponding eID class or eID script
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        trigger_error(self::class . ' will be removed in TYPO3 v10.0. Use ' . EidMiddleware::class . ' instead.', E_USER_DEPRECATED);
        // Remove any output produced until now
        ob_clean();

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        $eID = $request->getParsedBody()['eID'] ?? $request->getQueryParams()['eID'] ?? '';

        if (empty($eID) || !isset($GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$eID])) {
            return $response->withStatus(404, 'eID not registered');
        }

        $configuration = $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$eID];

        // Simple check to make sure that it's not an absolute file (to use the fallback)
        if (strpos($configuration, '::') !== false || is_callable($configuration)) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
            $request = $request->withAttribute('target', $configuration);
            return $dispatcher->dispatch($request, $response);
        }

        $scriptPath = GeneralUtility::getFileAbsFileName($configuration);
        if ($scriptPath === '') {
            throw new Exception('Registered eID has invalid script path.', 1416391467);
        }
        include $scriptPath;
        return new NullResponse();
    }
}
