<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Routing;

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

use TYPO3\CMS\Core\Routing\Enhancer\PluginEnhancer;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Routing\RouteCollection;

/**
 * Allows to have a plugin with multiple controllers + actions for one specific plugin that has a namespace.
 *
 * A typical configuration looks like this:
 *
 * routeEnhancers:
 *   BlogExample:
 *     type: Extbase
 *     extension: BlogExample
 *     plugin: Pi1
 *     routes:
 *       - { routePath: '/blog/{page}', _controller: 'Blog::list', _arguments: {'page': '@widget_0/currentPage'} }
 *       - { routePath: '/blog/{slug}', _controller: 'Blog::detail' }
 *     requirements:
 *       page: '[0-9]+'
 *       slug: '.*'
 */
class ExtbasePluginEnhancer extends PluginEnhancer
{
    /**
     * @var array
     */
    protected $routesOfPlugin;

    public function __construct(array $configuration)
    {
        parent::__construct($configuration);
        $this->routesOfPlugin = $this->configuration['routes'] ?? [];
        // Only set the namespace if the plugin+extension keys are given. This allows to also use "namespace" property
        // instead from the parent constructor.
        if (isset($this->configuration['extension']) && isset($this->configuration['plugin'])) {
            $extensionName = $this->configuration['extension'];
            $pluginName = $this->configuration['plugin'];
            $extensionName = str_replace(' ', '', ucwords(str_replace('_', ' ', $extensionName)));
            $pluginSignature = strtolower($extensionName . '_' . $pluginName);
            $this->namespace = 'tx_' . $pluginSignature;
        }
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function enhanceForMatching(RouteCollection $collection): void
    {
        $i = 0;
        /** @var Route $defaultPageRoute */
        $defaultPageRoute = $collection->get('default');
        foreach ($this->routesOfPlugin as $configuration) {
            $route = $this->getVariant($defaultPageRoute, $configuration);
            $collection->add($this->namespace . '_' . $i++, $route);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getVariant(Route $defaultPageRoute, array $configuration): Route
    {
        $arguments = $configuration['_arguments'] ?? [];
        unset($configuration['_arguments']);

        $namespacedRequirements = $this->getNamespacedRequirements();
        $routePath = $this->modifyRoutePath($configuration['routePath']);
        $routePath = $this->getVariableProcessor()->deflateRoutePath($routePath, $this->namespace, $arguments);
        unset($configuration['routePath']);
        $defaults = array_merge_recursive($defaultPageRoute->getDefaults(), $configuration);
        $options = array_merge($defaultPageRoute->getOptions(), ['_enhancer' => $this, 'utf8' => true, '_arguments' => $arguments]);
        $route = new Route(rtrim($defaultPageRoute->getPath(), '/') . '/' . ltrim($routePath, '/'), $defaults, [], $options);
        $this->applyRouteAspects($route, $this->aspects ?? [], $this->namespace);
        if ($namespacedRequirements) {
            $compiledRoute = $route->compile();
            $variables = $compiledRoute->getPathVariables();
            $variables = array_flip($variables);
            $requirements = array_filter($namespacedRequirements, function ($key) use ($variables) {
                return isset($variables[$key]);
            }, ARRAY_FILTER_USE_KEY);
            $route->setRequirements($requirements);
        }
        return $route;
    }

    /**
     * {@inheritdoc}
     */
    public function enhanceForGeneration(RouteCollection $collection, array $originalParameters): void
    {
        if (!is_array($originalParameters[$this->namespace] ?? null)) {
            return;
        }
        // apply default controller and action names if not set in parameters
        if (!$this->hasControllerActionValues($originalParameters[$this->namespace])
            && !empty($this->configuration['defaultController'])
        ) {
            $this->applyControllerActionValues(
                $this->configuration['defaultController'],
                $originalParameters[$this->namespace]
            );
        }

        $i = 0;
        /** @var Route $defaultPageRoute */
        $defaultPageRoute = $collection->get('default');
        foreach ($this->routesOfPlugin as $configuration) {
            $variant = $this->getVariant($defaultPageRoute, $configuration);
            // The enhancer tells us: This given route does not match the parameters
            if (!$this->verifyRequiredParameters($variant, $originalParameters)) {
                continue;
            }
            $parameters = $originalParameters;
            unset($parameters[$this->namespace]['action']);
            unset($parameters[$this->namespace]['controller']);
            $compiledRoute = $variant->compile();
            $deflatedParameters = $this->deflateParameters($variant, $parameters);
            $variables = array_flip($compiledRoute->getPathVariables());
            $mergedParams = array_replace($variant->getDefaults(), $deflatedParameters);
            // all params must be given, otherwise we exclude this variant
            if ($diff = array_diff_key($variables, $mergedParams)) {
                continue;
            }
            $variant->addOptions(['deflatedParameters' => $deflatedParameters]);
            $collection->add($this->namespace . '_' . $i++, $variant);
        }
    }

    /**
     * A route has matched the controller/action combination, so ensure that these properties
     * are set to tx_blogexample_pi1[controller] and tx_blogexample_pi1[action].
     *
     * @param array $parameters Actual parameter payload to be used
     * @param array $internals Internal instructions (_route, _controller, ...)
     * @return array
     */
    protected function inflateParameters(array $parameters, array $internals = []): array
    {
        $parameters = $this->getVariableProcessor()
            ->inflateNamespaceParameters($parameters, $this->namespace);
        $parameters[$this->namespace] = $parameters[$this->namespace] ?? [];

        // Invalid if there is no controller given, so this enhancers does not do anything
        if (empty($internals['_controller'] ?? null)) {
            return $parameters;
        }
        $this->applyControllerActionValues(
            $internals['_controller'],
            $parameters[$this->namespace]
        );
        return $parameters;
    }

    /**
     * Check if controller+action combination matches
     *
     * @param Route $route
     * @param array $parameters
     * @return bool
     */
    protected function verifyRequiredParameters(Route $route, array $parameters): bool
    {
        if (!is_array($parameters[$this->namespace])) {
            return false;
        }
        if (!$route->hasDefault('_controller')) {
            return false;
        }
        $controller = $route->getDefault('_controller');
        list($controllerName, $actionName) = explode('::', $controller);
        if ($controllerName !== $parameters[$this->namespace]['controller']) {
            return false;
        }
        if ($actionName !== $parameters[$this->namespace]['action']) {
            return false;
        }
        return true;
    }

    /**
     * Check if action and controller are not empty.
     *
     * @param array $target
     * @return bool
     */
    protected function hasControllerActionValues(array $target): bool
    {
        return !empty($target['controller']) && !empty($target['action']);
    }

    /**
     * Add controller and action parameters so they can be used later-on.
     *
     * @param string $controllerActionValue
     * @param array $target
     */
    protected function applyControllerActionValues(string $controllerActionValue, array &$target)
    {
        if (strpos($controllerActionValue, '::') === false) {
            return;
        }
        list($controllerName, $actionName) = explode('::', $controllerActionValue, 2);
        $target['controller'] = $controllerName;
        $target['action'] = $actionName;
    }
}
