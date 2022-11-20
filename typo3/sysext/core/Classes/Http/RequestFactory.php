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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\Client\GuzzleClientFactory;

/**
 * Front-end for sending requests, using PSR-7.
 */
class RequestFactory implements RequestFactoryInterface
{
    public function __construct(
        private readonly GuzzleClientFactory $guzzleFactory,
    ) {
    }

    /**
     * Create a new request.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request.
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($uri, $method, null);
    }

    /**
     * Create a Guzzle request object with our custom implementation
     *
     * @param string $uri the URI to request
     * @param string $method the HTTP method (defaults to GET)
     * @param array $options custom options for this request
     */
    public function request(string $uri, string $method = 'GET', array $options = []): ResponseInterface
    {
        return $this->guzzleFactory->getClient()->request($method, $uri, $options);
    }
}
