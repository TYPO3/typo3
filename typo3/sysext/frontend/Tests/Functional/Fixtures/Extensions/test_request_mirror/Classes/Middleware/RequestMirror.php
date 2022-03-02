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

namespace TYPO3\RequestMirror\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RequestMirror implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== '/request-mirror') {
            return $handler->handle($request);
        }
        return GeneralUtility::makeInstance(ResponseFactoryInterface::class)
            ->createResponse(200, '')
            ->withHeader('Content-type', 'application/json')
            ->withBody(GeneralUtility::makeInstance(StreamFactoryInterface::class)->createStream(\json_encode(
                [
                    'uri' => $request->getUri(),
                    'method' => $request->getMethod(),
                    'headers' => $request->getHeaders(),
                    'queryParams' => $request->getQueryParams(),
                    'parsedBody' => $request->getParsedBody(),
                    'body' => (string)$request->getBody(),
                ],
                JSON_THROW_ON_ERROR
            )));
    }
}
