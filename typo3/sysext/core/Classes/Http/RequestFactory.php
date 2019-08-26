<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Http;

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

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class RequestFactory to create Request objects
 * Returns PSR-7 Request objects
 */
class RequestFactory implements RequestFactoryInterface
{
    /**
     * Create a new request.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request.
     * @return RequestInterface
     */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($uri, $method, null);
    }

    /**
     * Create a guzzle request object with our custom implementation
     *
     * @param string $uri the URI to request
     * @param string $method the HTTP method (defaults to GET)
     * @param array $options custom options for this request
     * @return ResponseInterface
     */
    public function request(string $uri, string $method = 'GET', array $options = []): ResponseInterface
    {
        $client = $this->getClient();
        return $client->request($method, $uri, $options);
    }

    /**
     * Creates the client to do requests
     * @return ClientInterface
     */
    protected function getClient(): ClientInterface
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];

        if (isset($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler']) && is_array($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'])) {
            $stack = HandlerStack::create();
            foreach ($GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] ?? [] as $handler) {
                $stack->push($handler);
            }
            $httpOptions['handler'] = $stack;
        }

        return GeneralUtility::makeInstance(Client::class, $httpOptions);
    }
}
