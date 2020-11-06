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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Default flags for json_encode; value of:
     *
     * <code>
     * JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
     * </code>
     *
     * @var int
     */
    public const JSON_FLAGS_RFC4627 = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;

    /**
     * Create a new response.
     *
     * @param int $code HTTP status code; defaults to 200
     * @param string $reasonPhrase Reason phrase to associate with status code
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response(null, $code, [], $reasonPhrase);
    }

    public function createHtmlResponse(string $html): ResponseInterface
    {
        $response = $this->createResponse();
        $response->withAddedHeader('Content-Type', 'text/html; charset=utf-8');

        $stream = new Stream('php://temp', 'wb+');
        $stream->write($html);
        $stream->rewind();
        return $response->withBody($stream);
    }

    public function createJsonResponse(string $json): ResponseInterface
    {
        $response = $this->createResponse();
        $response->withAddedHeader('Content-Type', 'application/json; charset=utf-8');

        $stream = new Stream('php://temp', 'wb+');
        $stream->write($json);
        $stream->rewind();
        return $response->withBody($stream);
    }

    /**
     * Create a JSON response with the given data.
     *
     * Default JSON encoding is performed with the following options, which
     * produces RFC4627-compliant JSON, capable of embedding into HTML.
     *
     * - {@see JSON_HEX_TAG}
     * - {@see JSON_HEX_APOS}
     * - {@see JSON_HEX_AMP}
     * - {@see JSON_HEX_QUOT}
     * - {@see JSON_UNESCAPED_SLASHES}
     *
     * @param mixed $data
     * @param int $encodingOptions
     * @return ResponseInterface
     * @throws \JsonException
     */
    public function createJsonResponseFromData($data, int $encodingOptions = self::JSON_FLAGS_RFC4627): ResponseInterface
    {
        return $this->createJsonResponse((string)json_encode($data, $encodingOptions | JSON_THROW_ON_ERROR));
    }

    public function createRedirectResponse(UriInterface $uri, int $code = 303): ResponseInterface
    {
        return $this->createResponse($code)->withAddedHeader('location', (string)$uri);
    }
}
