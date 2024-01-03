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
use Symfony\Component\Routing\RouteCollection as SymfonyRouteCollection;
use TYPO3\CMS\Backend\Routing\Route as Typo3Route;

/**
 * Extensible container based on Symfony's Route Collection
 *
 * @internal as this is tightly coupled to Symfony's Routing and we try to encapsulate this, please note that this might change
 */
class RouteCollection extends SymfonyRouteCollection
{
    public function add(string $name, Typo3Route|SymfonyRoute $route, int $priority = 0): void
    {
        if ($route instanceof Typo3Route) {
            $symfonyRoute = new SymfonyRoute($route->getPath(), [], [], $route->getOptions());
            $symfonyRoute->setMethods($route->getMethods());
            parent::add($name, $symfonyRoute, $priority);
        } else {
            parent::add($name, $route, $priority);
        }
    }
}
