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

namespace TYPO3\CMS\Core\ExpressionLanguage;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The main API endpoint to evaluate symfony expression language.
 *
 * This Resolver can prepare common variables and functions for specific scopes,
 * it is a "prepared" facade to symfony expression language that can load
 * and provide things from Configuration.
 */
class Resolver
{
    private ExpressionLanguage $expressionLanguage;
    private array $expressionLanguageVariables;

    public function __construct(string $context, array $variables)
    {
        $functionProviderInstances = [];
        // @todo: The entire ProviderConfigurationLoader approach should fall and
        //        substituted with a symfony service provider strategy in v13.
        //        Also, the magic "DefaultProvider" approach should fall at this time,
        //        default functions and variable providers should be provided explicitly
        //        by config.
        //        The entire construct should be reviewed at this point and most likely
        //        declared final as well.
        $providers = GeneralUtility::makeInstance(ProviderConfigurationLoader::class)->getExpressionLanguageProviders()[$context] ?? [];
        // Always add default provider
        array_unshift($providers, DefaultProvider::class);
        $providers = array_unique($providers);
        $functionProviders = [];
        $generalVariables = [];
        foreach ($providers as $provider) {
            /** @var ProviderInterface $providerInstance */
            $providerInstance = GeneralUtility::makeInstance($provider);
            $functionProviders[] = $providerInstance->getExpressionLanguageProviders();
            $generalVariables[] = $providerInstance->getExpressionLanguageVariables();
        }
        $functionProviders = array_merge(...$functionProviders);
        $generalVariables = array_replace_recursive(...$generalVariables);
        $this->expressionLanguageVariables = array_replace_recursive($generalVariables, $variables);
        foreach ($functionProviders as $functionProvider) {
            /** @var ExpressionFunctionProviderInterface[] $functionProviderInstances */
            $functionProviderInstances[] = GeneralUtility::makeInstance($functionProvider);
        }
        $this->expressionLanguage = new ExpressionLanguage(null, $functionProviderInstances);
    }

    /**
     * Evaluate an expression.
     */
    public function evaluate(string $expression, array $contextVariables = []): mixed
    {
        return $this->expressionLanguage->evaluate($expression, array_replace($this->expressionLanguageVariables, $contextVariables));
    }

    /**
     * Compiles an expression to source code.
     * Currently unused in core: We *may* add support for this later to speed up condition parsing?
     */
    public function compile(string $condition): string
    {
        return $this->expressionLanguage->compile($condition, array_keys($this->expressionLanguageVariables));
    }
}
