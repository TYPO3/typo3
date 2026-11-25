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

namespace TYPO3\CMS\Core\Tests\Functional\Error\PageErrorHandler\Fixtures;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Http\Response;

/**
 * This class replaces the network for the request of an external error page. The test
 * configures it as a handler of the HTTP client. For the path "/status-503", the answer
 * has the status code 503. For all other paths, the connection fails.
 */
final readonly class ExternalErrorPageRequestHandler
{
    public static function create(callable $handler): \Closure
    {
        return static function (RequestInterface $request): PromiseInterface {
            if ($request->getUri()->getPath() === '/status-503') {
                return new FulfilledPromise(new Response('php://temp', 503));
            }
            return new RejectedPromise(new ConnectException('Connection refused', $request));
        };
    }
}
