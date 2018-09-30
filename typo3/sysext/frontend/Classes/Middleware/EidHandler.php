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
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Lightweight alternative to regular frontend requests; used when $_GET[eID] is set.
 * In the future, logic from the EidUtility will be moved to this class, however in most cases
 * a custom PSR-15 middleware will be better suited for whatever job the eID functionality does currently.
 *
 * @internal
 */
class EidHandler implements MiddlewareInterface
{
    /**
     * Dispatches the request to the corresponding eID class or eID script
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $eID = $request->getParsedBody()['eID'] ?? $request->getQueryParams()['eID'] ?? null;

        if ($eID === null) {
            return $handler->handle($request);
        }

        // Remove any output produced until now
        ob_clean();

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        if (empty($eID) || !isset($GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$eID])) {
            return $response->withStatus(404, 'eID not registered');
        }

        $configuration = $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$eID];

        // Simple check to make sure that it's not an absolute file (to use the fallback)
        if (strpos($configuration, '::') !== false || is_callable($configuration)) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = GeneralUtility::makeInstance(Dispatcher::class);
            $request = $request->withAttribute('target', $configuration);
            return $dispatcher->dispatch($request, $response) ?? new NullResponse();
        }
        trigger_error(
            'eID "' . $eID . '" is registered with a script to a file. This behaviour will be removed in TYPO3 v10.0.'
            . ' Register eID with a class::method syntax like "\MyVendor\MyExtension\Controller\MyEidController::myMethod" instead.',
            E_USER_DEPRECATED
        );
        $scriptPath = GeneralUtility::getFileAbsFileName($configuration);
        if ($scriptPath === '') {
            throw new Exception('Registered eID has invalid script path.', 1518042216);
        }
        include $scriptPath;
        return new NullResponse();
    }
}
