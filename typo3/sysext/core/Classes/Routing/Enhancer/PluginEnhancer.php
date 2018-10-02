<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Enhancer;

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

use TYPO3\CMS\Core\Routing\Aspect\StaticMappableAspectInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Routing\RouteCollection;
use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Used for plugins like EXT:felogin.
 *
 * This is usually used for arguments that are built with a `tx_myplugin_pi1` as namespace in GET / POST parameter.
 *
 * routeEnhancers:
 *   ForgotPassword:
 *     type: Plugin
 *     routePath: '/forgot-pw/{user_id}/{hash}/'
 *     namespace: 'tx_felogin_pi1'
 *     _arguments:
 *       user_id: uid
 *     requirements:
 *       user_id: '[a-z]+'
 *       hash: '[a-z]{0-6}'
 */
class PluginEnhancer extends AbstractEnhancer implements RoutingEnhancerInterface, ResultingInterface
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $namespace;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
        $this->namespace = $this->configuration['namespace'] ?? '';
    }

    /**
     * {@inheritdoc}
     */
    public function buildResult(Route $route, array $results, array $remainingQueryParameters = []): PageArguments
    {
        $variableProcessor = $this->getVariableProcessor();
        // determine those parameters that have been processed
        $parameters = array_intersect_key(
            $results,
            array_flip($route->compile()->getPathVariables())
        );
        // strip of those that where not processed (internals like _route, etc.)
        $internals = array_diff_key($results, $parameters);
        $matchedVariableNames = array_keys($parameters);

        $staticMappers = $route->filterAspects([StaticMappableAspectInterface::class], $matchedVariableNames);
        $dynamicCandidates = array_diff_key($parameters, $staticMappers);

        // all route arguments
        $routeArguments = $this->inflateParameters($parameters, $internals);
        // dynamic arguments, that don't have a static mapper
        $dynamicArguments = $variableProcessor
            ->inflateNamespaceParameters($dynamicCandidates, $this->namespace);
        // static arguments, that don't appear in dynamic arguments
        $staticArguments = ArrayUtility::arrayDiffAssocRecursive($routeArguments, $dynamicArguments);
        // inflate remaining query arguments that could not be applied to the route
        $remainingQueryParameters = $variableProcessor
            ->inflateNamespaceParameters($remainingQueryParameters, $this->namespace);

        $page = $route->getOption('_page');
        $pageId = (int)($page['l10n_parent'] > 0 ? $page['l10n_parent'] : $page['uid']);
        $type = $this->resolveType($route, $remainingQueryParameters);
        return new PageArguments($pageId, $type, $routeArguments, $staticArguments, $remainingQueryParameters);
    }

    /**
     * {@inheritdoc}
     */
    public function enhanceForMatching(RouteCollection $collection): void
    {
        /** @var Route $defaultPageRoute */
        $defaultPageRoute = $collection->get('default');
        $variant = $this->getVariant($defaultPageRoute, $this->configuration);
        $collection->add('enhancer_' . $this->namespace . spl_object_hash($variant), $variant);
    }

    /**
     * Builds a variant of a route based on the given configuration.
     *
     * @param Route $defaultPageRoute
     * @param array $configuration
     * @return Route
     */
    protected function getVariant(Route $defaultPageRoute, array $configuration): Route
    {
        $arguments = $configuration['_arguments'] ?? [];
        unset($configuration['_arguments']);

        $routePath = $this->modifyRoutePath($configuration['routePath']);
        $routePath = $this->getVariableProcessor()->deflateRoutePath($routePath, $this->namespace, $arguments);
        $variant = clone $defaultPageRoute;
        $variant->setPath(rtrim($variant->getPath(), '/') . '/' . ltrim($routePath, '/'));
        $variant->addOptions(['_enhancer' => $this, '_arguments' => $arguments]);
        $variant->setDefaults($configuration['defaults'] ?? []);
        $variant->setRequirements($this->getNamespacedRequirements());
        $this->applyRouteAspects($variant, $this->aspects ?? [], $this->namespace);
        return $variant;
    }

    /**
     * {@inheritdoc}
     */
    public function enhanceForGeneration(RouteCollection $collection, array $parameters): void
    {
        // No parameter for this namespace given, so this route does not fit the requirements
        if (!is_array($parameters[$this->namespace])) {
            return;
        }
        /** @var Route $defaultPageRoute */
        $defaultPageRoute = $collection->get('default');
        $variant = $this->getVariant($defaultPageRoute, $this->configuration);
        $compiledRoute = $variant->compile();
        $deflatedParameters = $this->deflateParameters($variant, $parameters);
        $variables = array_flip($compiledRoute->getPathVariables());
        $mergedParams = array_replace($variant->getDefaults(), $deflatedParameters);
        // all params must be given, otherwise we exclude this variant
        if ($diff = array_diff_key($variables, $mergedParams)) {
            return;
        }
        $variant->addOptions(['deflatedParameters' => $deflatedParameters]);
        $collection->add('enhancer_' . $this->namespace . spl_object_hash($variant), $variant);
    }

    /**
     * Add the namespace of the plugin to all requirements, so they are unique for this plugin.
     *
     * @return array
     */
    protected function getNamespacedRequirements(): array
    {
        $requirements = [];
        foreach ($this->configuration['requirements'] ?? [] as $name => $value) {
            $requirements[$this->namespace . '_' . $name] = $value;
        }
        return $requirements;
    }

    /**
     * @param Route $route
     * @param array $parameters
     * @return array
     */
    protected function deflateParameters(Route $route, array $parameters): array
    {
        return $this->getVariableProcessor()->deflateNamespaceParameters(
            $parameters,
            $this->namespace,
            $route->getArguments()
        );
    }

    /**
     * @param array $parameters Actual parameter payload to be used
     * @param array $internals Internal instructions (_route, _controller, ...)
     * @return array
     */
    protected function inflateParameters(array $parameters, array $internals = []): array
    {
        return $this->getVariableProcessor()
            ->inflateNamespaceParameters($parameters, $this->namespace);
    }
}
