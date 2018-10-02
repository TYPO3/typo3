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
     * Modify the route path to add the variable names with the aspects.
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
