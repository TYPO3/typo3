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

namespace TYPO3\CMS\Backend\Routing;

use TYPO3\CMS\Core\Routing\RouteResultInterface;

/**
 * A route result for the TYPO3 Backend Routing,
 * containing the matched Route and the related arguments found in the URL
 */
class RouteResult implements RouteResultInterface
{
    public function __construct(
        protected Route $route,
        protected array $arguments = [],
    ) {
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getRouteName(): string
    {
        return $this->route->getOption('_identifier');
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function offsetExists($offset): bool
    {
        return $offset === 'route' || isset($this->arguments[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return match ($offset) {
            'route' => $this->route,
            default => $this->arguments[$offset],
        };
    }

    public function offsetSet(mixed $offset = '', mixed $value = ''): void
    {
        switch ($offset) {
            case 'route':
                $this->route = $value;
                break;
            default:
                $this->arguments[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        switch ($offset) {
            case 'route':
                throw new \InvalidArgumentException('You can never unset the Route in a route result', 1669839336);
            default:
                unset($this->arguments[$offset]);
        }
    }
}
