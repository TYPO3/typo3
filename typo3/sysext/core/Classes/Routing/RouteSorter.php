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

namespace TYPO3\CMS\Core\Routing;

/**
 * Pre-processing of given routes based on their actual disposal concerning given parameters.
 * @internal as this is tightly coupled to Symfony's Routing and we try to encapsulate this, please note that this might change
 */
class RouteSorter
{
    protected const EARLIER = -1;
    protected const LATER = 1;

    /**
     * @var Route[]
     */
    protected $routes = [];

    /**
     * @var array<string, string>
     */
    protected $originalParameters = [];

    /**
     * @return Route[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    public function withRoutes(array $routes): self
    {
        $target = clone $this;
        $target->routes = $routes;
        return $target;
    }

    public function withOriginalParameters(array $originalParameters): self
    {
        $target = clone $this;
        $target->originalParameters = $originalParameters;
        return $target;
    }

    public function sortRoutesForGeneration(): self
    {
        \uasort($this->routes, [$this, 'compareForGeneration']);
        return $this;
    }

    protected function compareForGeneration(Route $self, Route $other): int
    {
        // default routes (e.g `/my-page`) -> process later
        return $this->compareDefaultRoutes($self, $other, self::LATER)
            // no variables (e.g. `/my-page/list`) -> process later
            ?? $this->compareStaticRoutes($self, $other, self::LATER)
            // all variables complete -> process earlier
            ?? $this->compareAllVariablesPresence($self, $other, self::EARLIER)
            // mandatory variables complete -> process earlier
            ?? $this->compareMandatoryVariablesPresence($self, $other, self::EARLIER)
            // more missing variable defaults -> process later
            ?? $this->compareMissingDefaultsAmount($self, $other, self::LATER)
            // more variable defaults -> process later
            ?? $this->compareDefaultsAmount($self, $other, self::LATER)
            // hm, dunno -> keep position
            ?? 0;
    }

    protected function compareDefaultRoutes(Route $self, Route $other, int $action = self::LATER): ?int
    {
        $selfIsDefaultRoute = (bool)$self->getOption('_isDefault');
        $otherIsDefaultRoute = (bool)$other->getOption('_isDefault');
        // both are default routes, keep order
        if ($selfIsDefaultRoute && $otherIsDefaultRoute) {
            return 0;
        }
        // $self is default route, sort $self after $other
        if ($selfIsDefaultRoute && !$otherIsDefaultRoute) {
            return $action;
        }
        // $other is default route, sort $self before $other
        if (!$selfIsDefaultRoute && $otherIsDefaultRoute) {
            return -$action;
        }
        return null;
    }

    protected function compareStaticRoutes(Route $self, Route $other, int $action = self::LATER): ?int
    {
        $selfVariableNames = $self->compile()->getPathVariables();
        $otherVariableNames = $other->compile()->getPathVariables();
        if ($selfVariableNames === [] && $otherVariableNames === []) {
            return 0;
        }
        if ($selfVariableNames === [] && $otherVariableNames !== []) {
            return $action;
        }
        if ($selfVariableNames !== [] && $otherVariableNames === []) {
            return -$action;
        }
        return null;
    }

    protected function compareAllVariablesPresence(Route $self, Route $other, int $action = self::EARLIER): ?int
    {
        $selfVariables = $this->getAllRouteVariables($self);
        $otherVariables = $this->getAllRouteVariables($other);
        $missingSelfVariables = \array_diff_key(
            $selfVariables,
            $this->getRouteParameters($self)
        );
        $missingOtherVariables = \array_diff_key(
            $otherVariables,
            $this->getRouteParameters($other)
        );
        if ($missingSelfVariables === [] && $missingOtherVariables === []) {
            $difference = \count($selfVariables) - \count($otherVariables);
            return $difference * $action;
        }
        if ($missingSelfVariables === [] && $missingOtherVariables !== []) {
            return $action;
        }
        if ($missingSelfVariables !== [] && $missingOtherVariables === []) {
            return -$action;
        }
        return null;
    }

    protected function compareMandatoryVariablesPresence(Route $self, Route $other, int $action = self::EARLIER): ?int
    {
        $missingSelfVariables = \array_diff_key(
            $this->getMandatoryRouteVariables($self),
            $this->getRouteParameters($self)
        );
        $missingOtherVariables = \array_diff_key(
            $this->getMandatoryRouteVariables($other),
            $this->getRouteParameters($other)
        );
        if ($missingSelfVariables === [] && $missingOtherVariables !== []) {
            return $action;
        }
        if ($missingSelfVariables !== [] && $missingOtherVariables === []) {
            return -$action;
        }
        return null;
    }

    protected function compareMissingDefaultsAmount(Route $self, Route $other, int $action = self::LATER): ?int
    {
        $missingSelfDefaults = \array_diff_key(
            $this->getActualRouteDefaults($self),
            $this->getRouteParameters($self)
        );
        $missingOtherDefaults = \array_diff_key(
            $this->getActualRouteDefaults($other),
            $this->getRouteParameters($other)
        );
        $difference = \count($missingSelfDefaults) - \count($missingOtherDefaults);
        // return `null` in case of equality (`0`)
        return $difference === 0 ? null : $difference * $action;
    }

    protected function compareDefaultsAmount(Route $self, Route $other, int $action = self::LATER): ?int
    {
        $selfDefaults = $this->getActualRouteDefaults($self);
        $otherDefaults = $this->getActualRouteDefaults($other);
        $difference = \count($selfDefaults) - \count($otherDefaults);
        // return `null` in case of equality (`0`)
        return $difference === 0 ? null : $difference * $action;
    }

    /**
     * Filters route variable defaults that are actually used in route path.
     *
     * @param Route $route
     * @return array<string, string>
     */
    protected function getActualRouteDefaults(Route $route): array
    {
        return array_intersect_key(
            $route->getDefaults(),
            array_flip($route->compile()->getPathVariables())
        );
    }

    /**
     * @param Route $route
     * @return array<string, int>
     */
    protected function getAllRouteVariables(Route $route): array
    {
        return array_flip($route->compile()->getPathVariables());
    }

    /**
     * @param Route $route
     * @return array<string, int>
     */
    protected function getMandatoryRouteVariables(Route $route): array
    {
        return \array_diff_key(
            $this->getAllRouteVariables($route),
            $route->getDefaults()
        );
    }

    /**
     * @param Route $route
     * @return array<string, string>
     */
    protected function getRouteParameters(Route $route): array
    {
        // $originalParameters is used used as fallback
        // (custom enhancers should have processed and deflated parameters)
        return $route->getOption('deflatedParameters') ?? $this->originalParameters;
    }
}
