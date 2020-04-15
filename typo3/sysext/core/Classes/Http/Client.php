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

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\Client\ClientException;
use TYPO3\CMS\Core\Http\Client\NetworkException;

/**
 * PSR-18 adapter for Guzzle\ClientInterface
 *
 * Will be removed once GuzzleHTTP implements PSR-18.
 *
 * @internal
 */
class Client implements ClientInterface
{
    /**
     * @var GuzzleClientInterface
     */
    private $guzzle;

    public function __construct(GuzzleClientInterface $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     * @return ResponseInterface
     * @throws ClientExceptionInterface If an error happens while processing the request.
     * @throws NetworkExceptionInterface If the request cannot be sent due to a network failure of any kind
     * @throws RequestExceptionInterface If the request message is not a well-formed HTTP request
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->guzzle->send($request, [
                RequestOptions::HTTP_ERRORS => false,
                RequestOptions::ALLOW_REDIRECTS => false,
            ]);
        } catch (ConnectException $e) {
            throw new NetworkException($e->getMessage(), 1566909446, $e->getRequest(), $e);
        } catch (RequestException $e) {
            throw new Client\RequestException($e->getMessage(), 1566909447, $e->getRequest(), $e);
        } catch (GuzzleException $e) {
            throw new ClientException($e->getMessage(), 1566909448, $e);
        }
    }
}
