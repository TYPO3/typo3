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

namespace TYPO3\CMS\Core\Routing\Enhancer;

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
class PluginEnhancer extends AbstractEnhancer implements RoutingEnhancerInterface, InflatableEnhancerInterface, ResultingInterface
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
        // route arguments, that don't appear in dynamic arguments
        $staticArguments = ArrayUtility::arrayDiffKeyRecursive($routeArguments, $dynamicArguments);

        $page = $route->getOption('_page');
        $pageId = (int)(isset($page['t3ver_oid']) && $page['t3ver_oid'] > 0 ? $page['t3ver_oid'] : $page['uid']);
        $pageId = (int)($page['l10n_parent'] > 0 ? $page['l10n_parent'] : $pageId);
        // See PageSlugCandidateProvider where this is added.
        if ($page['MPvar'] ?? '') {
            $routeArguments['MP'] = $page['MPvar'];
        }
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

        $variableProcessor = $this->getVariableProcessor();
        $routePath = $this->modifyRoutePath($configuration['routePath']);
        $routePath = $variableProcessor->deflateRoutePath($routePath, $this->namespace, $arguments);
        $variant = clone $defaultPageRoute;
        $variant->setPath(rtrim($variant->getPath(), '/') . '/' . ltrim($routePath, '/'));
        $variant->addOptions(['_enhancer' => $this, '_arguments' => $arguments]);
        $defaults = $variableProcessor->deflateKeys($this->configuration['defaults'] ?? [], $this->namespace, $arguments);
        // only keep `defaults` that are actually used in `routePath`
        $variant->setDefaults($this->filterValuesByPathVariables($variant, $defaults));
        $this->applyRouteAspects($variant, $this->aspects ?? [], $this->namespace);
        $this->applyRequirements($variant, $this->configuration['requirements'] ?? [], $this->namespace);
        return $variant;
    }

    /**
     * {@inheritdoc}
     */
    public function enhanceForGeneration(RouteCollection $collection, array $parameters): void
    {
        // No parameter for this namespace given, so this route does not fit the requirements
        if (!is_array($parameters[$this->namespace] ?? null)) {
            return;
        }
        /** @var Route $defaultPageRoute */
        $defaultPageRoute = $collection->get('default');
        $variant = $this->getVariant($defaultPageRoute, $this->configuration);
        $compiledRoute = $variant->compile();
        // contains all given parameters, even if not used as variables in route
        $deflatedParameters = $this->deflateParameters($variant, $parameters);
        $variables = array_flip($compiledRoute->getPathVariables());
        $mergedParams = array_replace($variant->getDefaults(), $deflatedParameters);
        // all params must be given, otherwise we exclude this variant
        if ($variables === [] || array_diff_key($variables, $mergedParams) !== []) {
            return;
        }
        $variant->addOptions(['deflatedParameters' => $deflatedParameters]);
        $collection->add('enhancer_' . $this->namespace . spl_object_hash($variant), $variant);
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
    public function inflateParameters(array $parameters, array $internals = []): array
    {
        return $this->getVariableProcessor()
            ->inflateNamespaceParameters($parameters, $this->namespace);
    }
}
