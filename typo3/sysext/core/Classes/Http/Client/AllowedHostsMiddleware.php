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

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle client middleware for filtering allowed request targets (SSRF prevention)
 *
 * @internal
 */
final class AllowedHostsMiddleware
{
    public function __construct(
        private readonly string $context,
        private readonly array $allowedHosts,
    ) {}

    /**
     * @param callable(RequestInterface, array): PromiseInterface $nextHandler
     * @return callable(RequestInterface $request, array $options): PromiseInterface
     */
    public function __invoke(callable $nextHandler): callable
    {
        return fn(RequestInterface $request, array $options): PromiseInterface =>
            $this->matches($request->getUri()->getHost()) ?
                $nextHandler($request, $options) :
                new RejectedPromise(sprintf(
                    'Requested host \'%s\' is not allowed in $GLOBALS[\'TYPO3_CONF_VARS\'][\'HTTP\'][\'allowed_hosts\'][\'%s\']',
                    $request->getUri()->getHost(),
                    $this->context
                ));
    }

    private function matches(string $host): bool
    {
        foreach ($this->allowedHosts as $allowedHost) {
            // Match wildcards
            if (str_contains($allowedHost, '*')) {
                $expr = implode(
                    '.+',
                    array_map(
                        static fn(string $part): string => preg_quote($part, '/'),
                        explode('*', $allowedHost)
                    )
                );
                if (preg_match('/^' . $expr . '$/', $host)) {
                    return true;
                }
            } elseif ($allowedHost === $host) {
                // Exact matches
                return true;
            }
        }

        return false;
    }
}
