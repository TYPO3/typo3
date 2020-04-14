<?php

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
 * A default HTML response object
 *
 * Highly inspired by ZF zend-diactoros
 *
 * @internal Note that this is not public API yet.
 */
class HtmlResponse extends Response
{
    /**
     * Creates a HTML response object with a default 200 response code
     *
     * @param string $content HTML content written to the response
     * @param int $status status code for the response; defaults to 200.
     * @param array $headers Additional headers to be set.
     */
    public function __construct($content, $status = 200, array $headers = [])
    {
        $body = new Stream('php://temp', 'wb+');
        $body->write($content);
        $body->rewind();
        parent::__construct($body, $status, $headers);

        // Ensure that text/html header is set, if Content-Type was not set before
        if (!$this->hasHeader('Content-Type')) {
            $this->headers['Content-Type'][] = 'text/html; charset=utf-8';
            $this->lowercasedHeaderNames['content-type'] = 'Content-Type';
        }
    }
}
