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

use Psr\Http\Message\UriInterface;

/**
 * A default redirect response object
 *
 * Highly inspired by ZF zend-diactoros
 *
 * @internal Note that this is not public API yet.
 */
class RedirectResponse extends Response
{
    /**
     * Creates a redirect response object with a given URI and status code.
     * Also sets the "Location" response header.
     *
     * @param string|UriInterface $uri URI for the Location header.
     * @param int $status status code for the redirect; defaults to 302.
     * @param array $headers Additional headers to be set.
     */
    public function __construct($uri, $status = 302, array $headers = [])
    {
        if (!is_string($uri) && !($uri instanceof UriInterface)) {
            throw new \InvalidArgumentException(
                'The given uri be a string or UriInterface - '
                . (is_object($uri) ? get_class($uri) : gettype($uri)) . ' given',
                1504814459
            );
        }
        $headers['location'] = [(string)$uri];
        parent::__construct('php://temp', $status, $headers);
    }
}
