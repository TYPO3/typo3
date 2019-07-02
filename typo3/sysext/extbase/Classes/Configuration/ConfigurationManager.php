<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Extbase\Configuration;

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

/**
 * A configuration manager following the strategy pattern (GoF315). It hides the concrete
 * implementation of the configuration manager and provides an unified access point.
 *
 * Use the shutdown() method to drop the concrete implementation.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ConfigurationManager implements \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager
     */
    protected $concreteConfigurationManager;

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function __construct(
        \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager,
        \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
    ) {
        $this->objectManager = $objectManager;
        $this->environmentService = $environmentService;

        $this->initializeConcreteConfigurationManager();
    }

    protected function initializeConcreteConfigurationManager(): void
    {
        if ($this->environmentService->isEnvironmentInFrontendMode()) {
            $this->concreteConfigurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager::class);
        } else {
            $this->concreteConfigurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager::class);
        }
    }

    /**
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
     */
    public function setContentObject(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject): void
    {
        $this->concreteConfigurationManager->setContentObject($contentObject);
    }

    /**
     * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|null
     */
    public function getContentObject(): ?\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
    {
        return $this->concreteConfigurationManager->getContentObject();
    }

    /**
     * Sets the specified raw configuration coming from the outside.
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param array $configuration The new configuration
     */
    public function setConfiguration(array $configuration = []): void
    {
        $this->concreteConfigurationManager->setConfiguration($configuration);
    }

    /**
     * Returns the specified configuration.
     * The actual configuration will be merged from different sources in a defined order.
     *
     * You can get the following types of configuration invoking:
     * CONFIGURATION_TYPE_SETTINGS: Extbase settings
     * CONFIGURATION_TYPE_FRAMEWORK: the current module/plugin settings
     * CONFIGURATION_TYPE_FULL_TYPOSCRIPT: a raw TS array
     *
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
     * @param string $extensionName if specified, the configuration for the given extension will be returned.
     * @param string $pluginName if specified, the configuration for the given plugin will be returned.
     * @throws Exception\InvalidConfigurationTypeException
     * @return array The configuration
     */
    public function getConfiguration(string $configurationType, string $extensionName = null, string $pluginName = null): array
    {
        switch ($configurationType) {
            case self::CONFIGURATION_TYPE_SETTINGS:
                $configuration = $this->concreteConfigurationManager->getConfiguration($extensionName, $pluginName);
                return $configuration['settings'] ?? [];
            case self::CONFIGURATION_TYPE_FRAMEWORK:
                return $this->concreteConfigurationManager->getConfiguration($extensionName, $pluginName);
            case self::CONFIGURATION_TYPE_FULL_TYPOSCRIPT:
                return $this->concreteConfigurationManager->getTypoScriptSetup();
            default:
                throw new \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1206031879);
        }
    }

    /**
     * Returns TRUE if a certain feature, identified by $featureName
     * should be activated, FALSE for backwards-compatible behavior.
     *
     * This is an INTERNAL API used throughout Extbase and Fluid for providing backwards-compatibility.
     * Do not use it in your custom code!
     *
     * @param string $featureName
     * @return bool
     */
    public function isFeatureEnabled(string $featureName): bool
    {
        $configuration = $this->getConfiguration(self::CONFIGURATION_TYPE_FRAMEWORK);
        return (bool)(isset($configuration['features'][$featureName]) && $configuration['features'][$featureName]);
    }
}
