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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider as AbstractLanguageProvider;
use TYPO3\CMS\Core\ExpressionLanguage\DefaultProvider;
use TYPO3\CMS\Core\ExpressionLanguage\ProviderConfigurationLoader;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class retrieves all Symfony expression language providers to be displayed in the lowlevel configuration module.
 */
class SymfonyExpressionLanguageProvidersProvider extends AbstractProvider
{
    /**
     * @return array<string, array>
     */
    public function getConfiguration(): array
    {
        $list = [];
        $providers = GeneralUtility::makeInstance(ProviderConfigurationLoader::class)->getExpressionLanguageProviders();

        /** Always add default provider @see \TYPO3\CMS\Core\ExpressionLanguage\Resolver::__construct */
        $providers = array_merge($providers, ['default' => [DefaultProvider::class]]);

        foreach ($providers as $context => $providersOfContext) {
            foreach ($providersOfContext as $providerClass) {
                /** @var AbstractLanguageProvider $provider */
                $provider = GeneralUtility::makeInstance($providerClass);
                $variables = array_keys($provider->getExpressionLanguageVariables());
                if ($variables !== []) {
                    $list[$context][$providerClass]['variables'] = $variables;
                }
                foreach ($provider->getExpressionLanguageProviders() as $languageProviderClass) {
                    /** @var ExpressionFunctionProviderInterface $languageProvider */
                    $languageProvider = GeneralUtility::makeInstance($languageProviderClass);
                    $functions = $languageProvider->getFunctions();
                    foreach ($functions as $function) {
                        $list[$context][$providerClass]['functions'][] = $function->getName();
                    }
                }
            }
        }

        return $list;
    }
}
