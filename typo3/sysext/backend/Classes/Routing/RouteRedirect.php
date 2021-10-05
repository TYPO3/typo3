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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Exception\RouteTypeNotAllowedException;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * A value object representing redirects within Backend routing.
 */
class RouteRedirect
{
    /**
     * Name of route to be redirected to
     */
    private string $name;

    /**
     * Multi-dimensional query params array
     * e.g. `['level1' => ['level2' => 'value']]`
     */
    private array $parameters;

    public static function create(string $name, $params): self
    {
        if (is_string($params)) {
            parse_str($params, $parsedParameters);
            $params = $parsedParameters;
        } elseif (!is_array($params)) {
            throw new \LogicException('Params must be array or string', 1627907107);
        }
        return new self($name, $params);
    }

    public static function createFromRoute(Route $route, array $parameters): self
    {
        return new self($route->getOption('_identifier'), $parameters);
    }

    public static function createFromRequest(ServerRequestInterface $request): ?self
    {
        $name = $request->getQueryParams()['redirect'] ?? null;
        if (empty($name)) {
            return null;
        }
        return self::create($name, $request->getQueryParams()['redirectParams'] ?? []);
    }

    private function __construct(string $name, array $params)
    {
        $this->name = $name;
        $this->parameters = $this->sanitizeParameters($params);
    }

    private function sanitizeParameters(array $redirectParameters): array
    {
        unset($redirectParameters['token']);
        unset($redirectParameters['route']);
        unset($redirectParameters['redirect']);
        unset($redirectParameters['redirectParams']);
        return $redirectParameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function getFormattedParameters(): string
    {
        $redirectParameters = http_build_query($this->parameters, '', '&', PHP_QUERY_RFC3986);
        return ltrim($redirectParameters, '&?');
    }

    public function hasParameters(): bool
    {
        return !empty($this->parameters);
    }

    /**
     * Checks if the route can be resolved as a redirect.
     *
     * @param Router $router
     * @throws RouteNotFoundException
     * @throws MethodNotAllowedException
     * @throws RouteTypeNotAllowedException
     */
    public function resolve(Router $router): void
    {
        $route = $router->getRouteCollection()->get($this->name);
        if ($route === null) {
            throw new RouteNotFoundException(
                sprintf('Route "%s" was not found', $this->name),
                1627907587
            );
        }
        if ($route->getOption('ajax')) {
            throw new RouteTypeNotAllowedException(
                sprintf('AJAX route "%s" cannot be redirected', $this->name),
                1627407451
            );
        }
        // Links to modules are always allowed, no problem here
        // Check for the AJAX option should be handled before
        if ($route->getOption('module')) {
            return;
        }
        if ($route->getMethods() !== [] && !in_array('GET', $route->getMethods(), true)) {
            throw new MethodNotAllowedException(
                sprintf('"%s" cannot be redirected as it does not allow GET methods', $this->name),
                1627407452
            );
        }
        $settings = $route->getOption('redirect');
        if (($settings['enable'] ?? false) !== true) {
            throw new RouteNotFoundException(
                sprintf('Route "%s" cannot be redirected', $this->name),
                1627407511
            );
        }
        // Only use allowed arguments, if set, otherwise no parameters are allowed
        if (!empty($settings['parameters'])) {
            $this->parameters = ArrayUtility::intersectRecursive($this->parameters, (array)$settings['parameters']);
        } else {
            $this->parameters = [];
        }
    }
}
