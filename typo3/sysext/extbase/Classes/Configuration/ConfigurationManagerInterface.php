<?php
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
 * Class ConfigurationManagerInterface
 */
interface ConfigurationManagerInterface extends \TYPO3\CMS\Core\SingletonInterface
{
    const CONFIGURATION_TYPE_FRAMEWORK = 'Framework';
    const CONFIGURATION_TYPE_SETTINGS = 'Settings';
    const CONFIGURATION_TYPE_FULL_TYPOSCRIPT = 'FullTypoScript';

    /**
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
     */
    public function setContentObject(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject = null);

    /**
     * Get the content object
     *
     * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     * @api (v4 only)
     */
    public function getContentObject();

    /**
     * Returns the specified configuration.
     * The actual configuration will be merged from different sources in a defined order.
     *
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
     * @param string $extensionName if specified, the configuration for the given extension will be returned.
     * @param string $pluginName if specified, the configuration for the given plugin will be returned.
     * @return array The configuration
     */
    public function getConfiguration($configurationType, $extensionName = null, $pluginName = null);

    /**
     * Sets the specified raw configuration coming from the outside.
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param array $configuration The new configuration
     */
    public function setConfiguration(array $configuration = []);

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
    public function isFeatureEnabled($featureName);
}
