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

use Symfony\Component\Routing\Generator\UrlGenerator as SymfonyUrlGenerator;
use TYPO3\CMS\Core\Routing\Aspect\MappableProcessor;

/**
 * @internal
 */
class UrlGenerator extends SymfonyUrlGenerator
{
    /**
     * @var MappableProcessor|null
     */
    protected $mappableProcessor;

    public function injectMappableProcessor(MappableProcessor $mappableProcessor): void
    {
        $this->mappableProcessor = $mappableProcessor;
    }

    /**
     * Processes aspect mapping on default values and delegates route generation to parent class.
     *
     * {@inheritdoc}
     */
    protected function doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, array $requiredSchemes = [])
    {
        /** @var Route $route */
        $route = $this->routes->get($name);
        // _appliedDefaults contains internal(!) values (mapped default values are not generated yet)
        // (keys used are deflated and need to be inflated later using VariableProcessor)
        $relevantDefaults = array_intersect_key($defaults, array_flip($route->compile()->getPathVariables()));
        $route->setOption('_appliedDefaults', array_diff_key($relevantDefaults, $parameters));
        // map default values for URL generation (e.g. '1' becomes 'one' if defined in aspect)
        $mappableProcessor = $this->mappableProcessor ?? new MappableProcessor();
        $mappableProcessor->generate($route, $defaults);

        return parent::doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }
}
