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

use Symfony\Component\Routing\Route as SymfonyRoute;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

/**
 * @internal
 */
class MatchedRoute
{
    protected array $hostMatches = [];
    protected array $pathMatches = [];

    public function __construct(protected SymfonyRoute $route, protected array $routeResult) {}

    public function withPathMatches(array $pathMatches): self
    {
        $target = clone $this;
        $target->pathMatches = $pathMatches;
        return $target;
    }

    public function withHostMatches(array $hostMatches): self
    {
        $target = clone $this;
        $target->hostMatches = $hostMatches;
        return $target;
    }

    public function getRoute(): SymfonyRoute
    {
        return $this->route;
    }

    public function getRouteResult(): array
    {
        return $this->routeResult;
    }

    public function getFallbackScore(): int
    {
        return $this->route->getOption('fallback') === true ? 1 : 0;
    }

    public function getHostMatchScore(): int
    {
        return empty($this->hostMatches[0]) ? 0 : 1;
    }

    public function getPathMatchScore(int $index): int
    {
        $completeMatch = $this->pathMatches[0];
        $tailMatch = $this->pathMatches[$index] ?? '';
        // no tail, it's a complete match
        if ($tailMatch === '') {
            return strlen($completeMatch);
        }
        // otherwise, find length of complete match that does not contain tail
        // example: complete: `/french/other`, tail: `/other` -> `strlen` of `/french`
        return strpos($completeMatch, $tailMatch);
    }

    public function getSiteIdentifier(): string
    {
        $site = $this->route->getDefault('site');
        return $site instanceof SiteInterface ? $site->getIdentifier() : '';
    }
}
