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

namespace TYPO3\CMS\Form\ConfigurationModuleProvider;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface as ExtbaseConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderInterface;

class FormYamlProvider implements ProviderInterface
{
    protected string $identifier;

    public function __construct(
        protected readonly ExtFormConfigurationManagerInterface $extFormConfigurationManager,
        protected readonly ExtbaseConfigurationManagerInterface $extbaseConfigurationManager,
    ) {}

    public function __invoke(array $attributes): self
    {
        $this->identifier = $attributes['identifier'];
        return $this;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:form/Resources/Private/Language/locallang.xlf:form.configuration.module.provider'
        );
    }

    public function getConfiguration(): array
    {
        // Another hidden dependency to $GLOBALS['TYPO3_REQUEST'] made explicit here.
        $request = $GLOBALS['TYPO3_REQUEST'];
        $extbaseConfigurationManager = $this->extbaseConfigurationManager;
        $extbaseConfigurationManager->setRequest($request);
        $typoScriptSettings = $extbaseConfigurationManager->getConfiguration(ExtbaseConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
        $configuration = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
        ArrayUtility::naturalKeySortRecursive($configuration);
        return $configuration;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
