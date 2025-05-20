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

namespace TYPO3\CMS\Core\Http\Client;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\HandlerStack;

/**
 * @internal
 */
class GuzzleClientFactory
{
    /**
     * Creates the client to do requests
     */
    public function getClient(?string $context = null): ClientInterface
    {
        $httpOptions = $GLOBALS['TYPO3_CONF_VARS']['HTTP'];
        $httpOptions['verify'] = filter_var($httpOptions['verify'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $httpOptions['verify'];

        // HEADS UP:
        // Passing a guzzle handler stack instead of an array of middlewares has never been documented,
        // but was theoretically possible since the introduction of handler middlewares.
        // This is not considered API (we can not control the ordering of the AllowedHosts Middleware)
        // but is preserved for maximum compatibility for now. It may vanish in a major release without notice.
        $stack = ($httpOptions['handler'] ?? null) instanceof HandlerStack ? $httpOptions['handler'] : HandlerStack::create();

        $allowedHosts = $httpOptions['allowed_hosts'][$context] ?? null;
        if (is_array($allowedHosts)) {
            $stack->push(
                new AllowedHostsMiddleware($context, array_filter($allowedHosts, is_string(...))),
                'typo3_allowed_hosts'
            );
        }
        unset($httpOptions['allowed_hosts']);

        if (is_array($httpOptions['handler'] ?? null)) {
            foreach ($httpOptions['handler'] as $name => $handler) {
                $stack->push($handler, (string)$name);
            }
        }

        $httpOptions['handler'] = $stack;
        return new Client($httpOptions);
    }
}
