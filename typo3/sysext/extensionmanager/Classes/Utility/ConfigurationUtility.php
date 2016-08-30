<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

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

use TYPO3\CMS\Core\TypoScript\ConfigurationForm;

/**
 * Utility for dealing with ext_emconf and ext_conf_template settings
 */
class ConfigurationUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Get default configuration from ext_conf_template of an extension
     * and save as initial configuration to LocalConfiguration ['EXT']['extConf'].
     *
     * Used by the InstallUtility to initialize local extension config.
     *
     * @param string $extensionKey Extension key
     * @return void
     */
    public function saveDefaultConfiguration($extensionKey)
    {
        $currentConfiguration = $this->getCurrentConfiguration($extensionKey);
        $nestedConfiguration = $this->convertValuedToNestedConfiguration($currentConfiguration);
        $this->writeConfiguration($nestedConfiguration, $extensionKey);
    }

    /**
     * Writes extension specific configuration to LocalConfiguration file
     * in array ['EXT']['extConf'][$extensionKey].
     *
     * Removes core cache files afterwards.
     *
     * This low level method expects a nested configuration array that
     * was already merged with default configuration and maybe new form values.
     *
     * @param array $configuration Configuration to save
     * @param string $extensionKey Extension key
     * @return void
     */
    public function writeConfiguration(array $configuration = [], $extensionKey)
    {
        /** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
        $configurationManager = $this->objectManager->get(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class);
        $configurationManager->setLocalConfigurationValueByPath('EXT/extConf/' . $extensionKey, serialize($configuration));
    }

    /**
     * Get current configuration of an extension. Will return the configuration as a valued object
     *
     * @param string $extensionKey
     * @return array
     */
    public function getCurrentConfiguration($extensionKey)
    {
        $mergedConfiguration = $this->getDefaultConfigurationFromExtConfTemplateAsValuedArray($extensionKey);
        $currentExtensionConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey]);
        $currentExtensionConfig = is_array($currentExtensionConfig) ? $currentExtensionConfig : [];
        $currentExtensionConfig = $this->convertNestedToValuedConfiguration($currentExtensionConfig);
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule(
            $mergedConfiguration,
            $currentExtensionConfig
        );
        return $mergedConfiguration;
    }

    /**
     * Create a flat array of configuration options from
     * ext_conf_template.txt of an extension using core's typoscript parser.
     *
     * Generates an array from the typoscript style constants and
     * adds meta data like TSConstantEditor comments
     *
     * Result is an array, with configuration item as array keys,
     * and item properties as key-value sub-array:
     *
     * array(
     *   'fooOption' => array(
     *     'type' => 'string',
     *     'value' => 'foo',
     *     ...
     *   ),
     *   'barOption' => array(
     *     'type' => boolean,
     *     'default_value' => 0,
     *     ...
     *   ),
     *   ...
     * )
     *
     * @param string $extensionKey Extension key
     * @return array
     */
    public function getDefaultConfigurationFromExtConfTemplateAsValuedArray($extensionKey)
    {
        $rawConfigurationString = $this->getDefaultConfigurationRawString($extensionKey);

        $theConstants = [];

        if ((string)$rawConfigurationString !== '') {
            $extensionPathInformation = $this->getExtensionPathInformation($extensionKey);

            /** @var ConfigurationForm $tsStyleConfig */
            $tsStyleConfig = $this->objectManager->get(ConfigurationForm::class);
            $tsStyleConfig->doNotSortCategoriesBeforeMakingForm = true;

            $theConstants = $tsStyleConfig->ext_initTSstyleConfig(
                $rawConfigurationString,
                $extensionPathInformation['siteRelPath'],
                PATH_site . $extensionPathInformation['siteRelPath']
            );

            // Loop through configuration items, see if it is assigned to a sub category
            // and add the sub category label to the item property if so.
            foreach ($theConstants as $configurationOptionName => $configurationOption) {
                if (
                    array_key_exists('subcat_name', $configurationOption)
                    && isset($tsStyleConfig->subCategories[$configurationOption['subcat_name']])
                    && isset($tsStyleConfig->subCategories[$configurationOption['subcat_name']][0])
                ) {
                    $theConstants[$configurationOptionName]['subcat_label'] = $tsStyleConfig->subCategories[$configurationOption['subcat_name']][0];
                }
            }

            // Set up the additional descriptions
            if (isset($tsStyleConfig->setup['constants']['TSConstantEditor.'])) {
                foreach ($tsStyleConfig->setup['constants']['TSConstantEditor.'] as $category => $highlights) {
                    $theConstants['__meta__'][rtrim($category, '.')]['highlightText'] = $highlights['description'];
                    foreach ($highlights as $highlightNumber => $value) {
                        if (rtrim($category, '.') == $theConstants[$value]['cat']) {
                            $theConstants[$value]['highlight'] = $highlightNumber;
                        }
                    }
                }
            }
        }

        return $theConstants;
    }

    /**
     * @param string $extensionKey
     * @return mixed
     */
    protected function getExtensionPathInformation($extensionKey)
    {
        return $GLOBALS['TYPO3_LOADED_EXT'][$extensionKey];
    }

    /**
     * Return content of an extensions ext_conf_template.txt file if
     * the file exists, empty string if file does not exist.
     *
     * @param string $extensionKey Extension key
     * @return string
     */
    protected function getDefaultConfigurationRawString($extensionKey)
    {
        $rawString = '';
        $extConfTemplateFileLocation = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
            'EXT:' . $extensionKey . '/ext_conf_template.txt',
            false
        );
        if (file_exists($extConfTemplateFileLocation)) {
            $rawString = file_get_contents($extConfTemplateFileLocation);
        }
        return $rawString;
    }

    /**
     * Converts a valued configuration to a nested configuration.
     *
     * array('first.second' => array('value' => 1))
     * will become
     * array('first.' => array('second' => ))
     *
     * @param array $valuedConfiguration
     * @return array
     */
    public function convertValuedToNestedConfiguration(array $valuedConfiguration)
    {
        $nestedConfiguration = [];
        foreach ($valuedConfiguration as $name => $section) {
            $path = str_replace('.', './', $name);
            $nestedConfiguration = \TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath($nestedConfiguration, $path, $section['value'], '/');
        }
        return $nestedConfiguration;
    }

    /**
     * Convert a nested configuration to a valued configuration
     *
     * array('first.' => array('second' => 1))
     * will become
     * array('first.second' => array('value' => 1)
     * @param array $nestedConfiguration
     * @return array
     */
    public function convertNestedToValuedConfiguration(array $nestedConfiguration)
    {
        $flatExtensionConfig = \TYPO3\CMS\Core\Utility\ArrayUtility::flatten($nestedConfiguration);
        $valuedCurrentExtensionConfig = [];
        foreach ($flatExtensionConfig as $key => $value) {
            $valuedCurrentExtensionConfig[$key]['value'] = $value;
        }
        return $valuedCurrentExtensionConfig;
    }
}
