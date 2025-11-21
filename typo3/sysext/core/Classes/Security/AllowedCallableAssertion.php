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

namespace TYPO3\CMS\Core\Security;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Attribute\AsAllowedCallable;

#[Autoconfigure(public: true)]
final readonly class AllowedCallableAssertion
{
    /**
     * @param list<array{class-string, string}> $items
     */
    public function __construct(private array $items = []) {}

    /**
     * @param string|array $callable
     */
    public function assertCallable(string|array $callable): void
    {
        // fall-back to using reflection
        $isTrusted = $this->isTrusted($callable);
        if ($isTrusted === null) {
            throw new AllowedCallableException(
                sprintf('Unexpected callable reference: %s', $this->stringifyCallable($callable)),
                1758626231
            );
        }
        if ($isTrusted === false) {
            throw new AllowedCallableException(
                sprintf(
                    'Attribute %s required for callback reference: %s',
                    AsAllowedCallable::class,
                    $this->stringifyCallable($callable)
                ),
                1758626232
            );
        }
    }

    public function isTrusted(string|array $callable): ?bool
    {
        if (is_string($callable)) {
            $callable = [$callable];
        }
        if (count($callable) === 1) {
            if ((is_string($callable[0]) && function_exists($callable[0])) || $callable[0] instanceof \Closure) {
                return $this->hasMatchingAttributes(
                    (new \ReflectionFunction($callable[0]))->getAttributes(AsAllowedCallable::class)
                );
            }
            return null;
        }
        if (count($callable) === 2
            && is_string($callable[1])
            && (
                (is_string($callable[0]) && class_exists($callable[0]))
                || is_object($callable[0])
            )
        ) {
            // lookup autoconfigured attributes
            $mapKey = [is_object($callable[0]) ? get_class($callable[0]) : $callable[0], $callable[1]];
            if (in_array($mapKey, $this->items, true)) {
                return true;
            }
            // fall-back to using reflection
            return $this->hasMatchingAttributes(
                (new \ReflectionMethod(...$callable))->getAttributes(AsAllowedCallable::class),
            );
        }
        return null;
    }

    private function hasMatchingAttributes(array $attributes): bool
    {
        return $attributes !== [];
    }

    private function stringifyCallable(string|array $callable): string
    {
        if (is_array($callable)) {
            $callable = array_map(
                static fn(mixed $value): mixed => is_object($value) ? get_class($value) : $value,
                $callable
            );
        }
        return json_encode($callable, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
