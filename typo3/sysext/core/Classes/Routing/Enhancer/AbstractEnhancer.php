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

use TYPO3\CMS\Core\Routing\Aspect\AspectInterface;
use TYPO3\CMS\Core\Routing\Aspect\ModifiableAspectInterface;
use TYPO3\CMS\Core\Routing\Route;

/**
 * Abstract Enhancer, useful for custom enhancers
 */
abstract class AbstractEnhancer implements EnhancerInterface
{
    /**
     * @var AspectInterface[]
     */
    protected $aspects = [];

    /**
     * @var VariableProcessor
     */
    protected $variableProcessor;

    /**
     * @param Route $route
     * @param AspectInterface[] $aspects
     * @param string|null $namespace
     */
    protected function applyRouteAspects(Route $route, array $aspects, string $namespace = null)
    {
        if (empty($aspects)) {
            return;
        }
        $aspects = $this->getVariableProcessor()
            ->deflateKeys($aspects, $namespace, $route->getArguments());
        $route->setAspects($aspects);
    }

    /**
     * @param Route $route
     * @param array $requirements
     * @param string|null $namespace
     */
    protected function applyRequirements(Route $route, array $requirements, string $namespace = null)
    {
        if (empty($requirements)) {
            return;
        }

        $requirements = $this->getVariableProcessor()
            ->deflateKeys($requirements, $namespace, $route->getArguments());
        // only keep requirements that are actually part of the current route path
        $requirements = $this->filterValuesByPathVariables($route, $requirements);
        // In general requirements are not considered when having aspects defined for a
        // given variable name and thus aspects are more specific and take precedence:
        // + since requirements in classic Symfony focus on internal arguments
        //   and aspects define a mapping between URL part (e.g. 'some-example-news')
        //   and the corresponding internal argument (e.g. 'tx_news_pi1[news]=123')
        // + thus, the requirement definition cannot be used for resolving and generating
        //   a route at the same time (it would have to be e.g. `[\w_._]+` AND `\d+`)
        // This call overrides default Symfony regular expression pattern `[^/]+` (see
        // `RouteCompiler::compilePattern()`) with `.+` to allow URI parameters like
        // `some-example-news/january` as well.
        $requirements = $this->overrideValuesByAspect($route, $requirements, '.+');

        if (!empty($requirements)) {
            $route->setRequirements($requirements);
        }
    }

    /**
     * Only keeps values that actually have been used as variables in route path.
     *
     * + routePath: '/list/{page}' ('page' used as variable in route path)
     * + values: ['entity' => 'entity...', 'page' => 'page...', 'other' => 'other...']
     * + result: ['page' => 'page...']
     *
     * @param Route $route
     * @param array $values
     * @return array
     */
    protected function filterValuesByPathVariables(Route $route, array $values): array
    {
        return array_intersect_key(
            $values,
            array_flip($route->compile()->getPathVariables())
        );
    }

    /**
     * Overrides items having an aspect definition with a given
     * $overrideValue in target $values array.
     *
     * @param Route $route
     * @param array $values
     * @param string $overrideValue
     * @return array
     */
    protected function overrideValuesByAspect(Route $route, array $values, string $overrideValue): array
    {
        foreach (array_keys($route->getAspects()) as $variableName) {
            $values[$variableName] = $overrideValue;
        }
        return $values;
    }

    /**
     * Modify the route path to add the variable names with the aspects, e.g.
     *
     * + `/{locale_modifier}/{product_title}`  -> `/products/{product_title}`
     * + `/{!locale_modifier}/{product_title}` -> `/products/{product_title}`
     *
     * @param string $routePath
     * @return string
     */
    protected function modifyRoutePath(string $routePath): string
    {
        $substitutes = [];
        foreach ($this->aspects as $variableName => $aspect) {
            if (!$aspect instanceof ModifiableAspectInterface) {
                continue;
            }
            $value = $aspect->modify();
            if ($value !== null) {
                $substitutes['{' . $variableName . '}'] = $value;
                $substitutes['{!' . $variableName . '}'] = $value;
            }
        }
        return str_replace(
            array_keys($substitutes),
            array_values($substitutes),
            $routePath
        );
    }

    /**
     * Retrieves type from processed route and modifies remaining query parameters.
     *
     * @param Route $route
     * @param array $remainingQueryParameters reference to remaining query parameters
     * @return string
     */
    protected function resolveType(Route $route, array &$remainingQueryParameters): string
    {
        $type = $remainingQueryParameters['type'] ?? 0;
        $decoratedParameters = $route->getOption('_decoratedParameters');
        if (isset($decoratedParameters['type'])) {
            $type = $decoratedParameters['type'];
            unset($decoratedParameters['type']);
            $remainingQueryParameters = array_replace_recursive(
                $remainingQueryParameters,
                $decoratedParameters
            );
        }
        return (string)$type;
    }

    /**
     * @return VariableProcessor
     */
    protected function getVariableProcessor(): VariableProcessor
    {
        if (isset($this->variableProcessor)) {
            return $this->variableProcessor;
        }
        return $this->variableProcessor = new VariableProcessor();
    }

    /**
     * {@inheritdoc}
     */
    public function setAspects(array $aspects): void
    {
        $this->aspects = $aspects;
    }

    /**
     * {@inheritdoc}
     */
    public function getAspects(): array
    {
        return $this->aspects;
    }
}
