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

/**
 * Standard values for a JSON response
 *
 * Highly inspired by ZF zend-diactoros
 *
 * @internal Note that this is not public API, use PSR-17 interfaces instead
 */
class JsonResponse extends Response
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
    public const DEFAULT_JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES;

    /**
     * Create a JSON response with the given data.
     *
     * Default JSON encoding is performed with the following options, which
     * produces RFC4627-compliant JSON, capable of embedding into HTML.
     *
     * - JSON_HEX_TAG
     * - JSON_HEX_APOS
     * - JSON_HEX_AMP
     * - JSON_HEX_QUOT
     * - JSON_UNESCAPED_SLASHES
     *
     * @param array|null $data Data to convert to JSON.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @param int $encodingOptions JSON encoding options to use.
     */
    public function __construct(
        ?array $data = [],
        int $status = 200,
        array $headers = [],
        int $encodingOptions = self::DEFAULT_JSON_FLAGS
    ) {
        $body = new Stream('php://temp', 'wb+');
        parent::__construct($body, $status, $headers);

        if ($data !== null) {
            $this->setPayload($data, $encodingOptions);
        }

        // Ensure that application/json header is set, if Content-Type was not set before
        if (!$this->hasHeader('Content-Type')) {
            $this->headers['Content-Type'][] = 'application/json; charset=utf-8';
            $this->lowercasedHeaderNames['content-type'] = 'Content-Type';
        }
    }

    /**
     * Overrides the exiting content, takes an array as input
     *
     * @param array $data
     * @param int $encodingOptions
     * @return $this
     */
    public function setPayload(array $data = [], int $encodingOptions = self::DEFAULT_JSON_FLAGS): JsonResponse
    {
        $this->body->write($this->jsonEncode($data, $encodingOptions));
        $this->body->rewind();
        return $this;
    }

    /**
     * Encode the provided data to JSON.
     *
     * @throws \InvalidArgumentException if unable to encode the $data to JSON.
     */
    private function jsonEncode(array $data, int $encodingOptions): string
    {
        // Clear json_last_error()
        json_encode(null);
        $json = json_encode($data, $encodingOptions);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to encode data to JSON in %s: %s',
                __CLASS__,
                json_last_error_msg()
            ), 1504972434);
        }
        return $json;
    }
}
