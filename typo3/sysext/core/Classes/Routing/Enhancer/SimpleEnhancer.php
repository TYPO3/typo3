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
 * This is usually used for simple GET arguments that have no namespace (e.g. not plugins).
 *
 * routeEnhancers
 *   Categories:
 *     type: Simple
 *     routePath: '/cmd/{category_id}/{scope_id}'
 *     _arguments:
 *       category_id: 'category/id'
 *       scope_id: 'scope/id'
 */
class SimpleEnhancer extends AbstractEnhancer implements RoutingEnhancerInterface, ResultingInterface
{
    /**
     * @var array
     */
    protected $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
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
        $matchedVariableNames = array_keys($parameters);

        $staticMappers = $route->filterAspects([StaticMappableAspectInterface::class], $matchedVariableNames);
        $dynamicCandidates = array_diff_key($parameters, $staticMappers);

        // all route arguments
        $routeArguments = $this->getVariableProcessor()->inflateParameters($parameters, $route->getArguments());
        // dynamic arguments, that don't have a static mapper
        $dynamicArguments = $variableProcessor
            ->inflateNamespaceParameters($dynamicCandidates, '');
        // static arguments, that don't appear in dynamic arguments
        $staticArguments = ArrayUtility::arrayDiffAssocRecursive($routeArguments, $dynamicArguments);
        // inflate remaining query arguments that could not be applied to the route
        $remainingQueryParameters = $variableProcessor
            ->inflateNamespaceParameters($remainingQueryParameters, '');

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
        $collection->add('enhancer_' . spl_object_hash($variant), $variant);
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
        $routePath = $this->getVariableProcessor()->deflateRoutePath($routePath, null, $arguments);
        $variant = clone $defaultPageRoute;
        $variant->setPath(rtrim($variant->getPath(), '/') . '/' . ltrim($routePath, '/'));
        $variant->setDefaults($configuration['defaults'] ?? []);
        $variant->setRequirements($configuration['requirements'] ?? []);
        $variant->addOptions(['_enhancer' => $this, '_arguments' => $arguments]);
        $this->applyRouteAspects($variant, $this->aspects ?? []);
        return $variant;
    }

    /**
     * {@inheritdoc}
     */
    public function enhanceForGeneration(RouteCollection $collection, array $parameters): void
    {
        /** @var Route $defaultPageRoute */
        $defaultPageRoute = $collection->get('default');
        $variant = $this->getVariant($defaultPageRoute, $this->configuration);
        $compiledRoute = $variant->compile();
        $deflatedParameters = $this->getVariableProcessor()->deflateParameters($parameters, $variant->getArguments());
        $variables = array_flip($compiledRoute->getPathVariables());
        $mergedParams = array_replace($variant->getDefaults(), $deflatedParameters);
        // all params must be given, otherwise we exclude this variant
        if ($diff = array_diff_key($variables, $mergedParams)) {
            return;
        }
        $variant->addOptions(['deflatedParameters' => $deflatedParameters]);
        $collection->add('enhancer_' . spl_object_hash($variant), $variant);
    }
}
