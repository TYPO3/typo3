<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\ExpressionLanguage;

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

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Resolver
 */
class Resolver
{
    /**
     * @var ProviderInterface
     */
    protected $provider;

    /**
     * @var \Symfony\Component\ExpressionLanguage\ExpressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var array
     */
    public $expressionLanguageVariables = [];

    /**
     * @param string $context
     * @param array $variables
     */
    public function __construct(string $context, array $variables)
    {
        $functionProviderInstances = [];
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
            $functionProviderInstances[] = GeneralUtility::makeInstance($functionProvider);
        }
        $this->expressionLanguage = new ExpressionLanguage(null, $functionProviderInstances);
    }

    /**
     * Evaluate an expression.
     *
     * @param string $condition The expression to parse
     * @return bool
     */
    public function evaluate(string $condition): bool
    {
        return (bool)$this->expressionLanguage->evaluate($condition, $this->expressionLanguageVariables);
    }

    /**
     * Compiles an expression source code.
     *
     * @param string $condition The expression to compile
     * @return string
     */
    public function compile(string $condition): string
    {
        return (string)$this->expressionLanguage->compile($condition, array_keys($this->expressionLanguageVariables));
    }
}
