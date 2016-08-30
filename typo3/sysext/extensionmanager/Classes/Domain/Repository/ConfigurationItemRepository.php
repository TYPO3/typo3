<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A repository for extension configuration items
 */
class ConfigurationItemRepository
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Find configuration options by extension
     *
     * @param string $extensionKey Extension key
     * @return \SplObjectStorage
     */
    public function findByExtensionKey($extensionKey)
    {
        $configurationArray = $this->getConfigurationArrayFromExtensionKey($extensionKey);
        return $this->convertHierarchicArrayToObject($configurationArray);
    }

    /**
     * Converts the raw configuration file content to an configuration object storage
     *
     * @param string $extensionKey Extension key
     * @return array
     */
    protected function getConfigurationArrayFromExtensionKey($extensionKey)
    {
        /** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
        $configurationUtility = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class);
        $defaultConfiguration = $configurationUtility->getDefaultConfigurationFromExtConfTemplateAsValuedArray($extensionKey);

        $resultArray = [];
        if (!empty($defaultConfiguration)) {
            $metaInformation = $this->addMetaInformation($defaultConfiguration);
            $configuration = $this->mergeWithExistingConfiguration($defaultConfiguration, $extensionKey);
            $hierarchicConfiguration = [];
            foreach ($configuration as $configurationOption) {
                $originalConfiguration = $this->buildConfigurationArray($configurationOption, $extensionKey);
                ArrayUtility::mergeRecursiveWithOverrule($originalConfiguration, $hierarchicConfiguration);
                $hierarchicConfiguration = $originalConfiguration;
            }

            // Flip category array as it was merged the other way around
            $hierarchicConfiguration = array_reverse($hierarchicConfiguration);

            // Sort configurations of each subcategory
            foreach ($hierarchicConfiguration as &$catConfigurationArray) {
                foreach ($catConfigurationArray as &$subcatConfigurationArray) {
                    uasort($subcatConfigurationArray, function ($a, $b) {
                        return strnatcmp($a['subcat'], $b['subcat']);
                    });
                }
                unset($subcatConfigurationArray);
            }
            unset($tempConfiguration);

            ArrayUtility::mergeRecursiveWithOverrule($hierarchicConfiguration, $metaInformation);
            $resultArray = $hierarchicConfiguration;
        }

        return $resultArray;
    }

    /**
     * Builds a configuration array from each line (option) of the config file
     *
     * @param string $configurationOption config file line representing one setting
     * @param string $extensionKey Extension key
     * @return array
     */
    protected function buildConfigurationArray($configurationOption, $extensionKey)
    {
        $hierarchicConfiguration = [];
        if (GeneralUtility::isFirstPartOfStr($configurationOption['type'], 'user')) {
            $configurationOption = $this->extractInformationForConfigFieldsOfTypeUser($configurationOption);
        } elseif (GeneralUtility::isFirstPartOfStr($configurationOption['type'], 'options')) {
            $configurationOption = $this->extractInformationForConfigFieldsOfTypeOptions($configurationOption);
        }
        if ($this->translate($configurationOption['label'], $extensionKey)) {
            $configurationOption['label'] = $this->translate($configurationOption['label'], $extensionKey);
        }
        $configurationOption['labels'] = GeneralUtility::trimExplode(':', $configurationOption['label'], false, 2);
        $configurationOption['subcat_name'] = $configurationOption['subcat_name'] ?: '__default';
        $hierarchicConfiguration[$configurationOption['cat']][$configurationOption['subcat_name']][$configurationOption['name']] = $configurationOption;
        return $hierarchicConfiguration;
    }

    /**
     * Extracts additional information for fields of type "options"
     * Extracts "type", "label" and values information
     *
     * @param array $configurationOption
     * @return array
     */
    protected function extractInformationForConfigFieldsOfTypeOptions(array $configurationOption)
    {
        preg_match('/options\[(.*)\]/is', $configurationOption['type'], $typeMatches);
        $optionItems = GeneralUtility::trimExplode(',', $typeMatches[1]);
        foreach ($optionItems as $optionItem) {
            $optionPair = GeneralUtility::trimExplode('=', $optionItem);
            if (count($optionPair) === 2) {
                $configurationOption['generic'][$optionPair[0]] = $optionPair[1];
            } else {
                $configurationOption['generic'][$optionPair[0]] = $optionPair[0];
            }
        }
        $configurationOption['type'] = 'options';
        return $configurationOption;
    }

    /**
     * Extract additional information for fields of type "user"
     * Extracts "type" and the function to be called
     *
     * @param array $configurationOption
     * @return array
     */
    protected function extractInformationForConfigFieldsOfTypeUser(array $configurationOption)
    {
        preg_match('/user\\[(.*)\\]/is', $configurationOption['type'], $matches);
        $configurationOption['generic'] = $matches[1];
        $configurationOption['type'] = 'user';
        return $configurationOption;
    }

    /**
     * Gets meta information from configuration array and
     * returns only the meta information
     *
     * @param array $configuration
     * @return array
     */
    protected function addMetaInformation(&$configuration)
    {
        $metaInformation = $configuration['__meta__'] ?: [];
        unset($configuration['__meta__']);
        return $metaInformation;
    }

    /**
     * Merge current local configuration over default configuration
     *
     * @param array $defaultConfiguration Default configuration from ext_conf_template.txt
     * @param string $extensionKey the extension information
     * @return array
     */
    protected function mergeWithExistingConfiguration(array $defaultConfiguration, $extensionKey)
    {
        try {
            $currentExtensionConfig = unserialize(
                $this->objectManager->get(\TYPO3\CMS\Core\Configuration\ConfigurationManager::class)
                    ->getConfigurationValueByPath('EXT/extConf/' . $extensionKey)
            );
            if (!is_array($currentExtensionConfig)) {
                $currentExtensionConfig = [];
            }
        } catch (\RuntimeException $e) {
            $currentExtensionConfig = [];
        }
        $flatExtensionConfig = ArrayUtility::flatten($currentExtensionConfig);
        $valuedCurrentExtensionConfig = [];
        foreach ($flatExtensionConfig as $key => $value) {
            $valuedCurrentExtensionConfig[$key]['value'] = $value;
        }
        ArrayUtility::mergeRecursiveWithOverrule($defaultConfiguration, $valuedCurrentExtensionConfig);
        return $defaultConfiguration;
    }

    /**
     * Converts a hierarchic configuration array to an
     * hierarchic object storage structure
     *
     * @param array $configuration
     * @return \SplObjectStorage
     */
    protected function convertHierarchicArrayToObject(array $configuration)
    {
        $configurationObjectStorage = new \SplObjectStorage();
        foreach ($configuration as $category => $subcategory) {
            /** @var $configurationCategoryObject \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationCategory */
            $configurationCategoryObject = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationCategory::class);
            $configurationCategoryObject->setName($category);
            if ($subcategory['highlightText']) {
                $configurationCategoryObject->setHighlightText($subcategory['highlightText']);
                unset($subcategory['highlightText']);
            }
            foreach ($subcategory as $subcatName => $configurationItems) {
                /** @var $configurationSubcategoryObject \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory */
                $configurationSubcategoryObject = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory::class);
                $configurationSubcategoryObject->setName($subcatName);
                foreach ($configurationItems as $configurationItem) {
                    // Set sub category label if configuration item contains a subcat label.
                    // The sub category label is set multiple times if there is more than one item
                    // in a sub category, but that is ok since all items of one sub category
                    // share the same label.
                    if (array_key_exists('subcat_label', $configurationItem)) {
                        $configurationSubcategoryObject->setLabel($configurationItem['subcat_label']);
                    }

                    /** @var $configurationObject \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem */
                    $configurationObject = $this->objectManager->get(\TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem::class);
                    if (isset($configurationItem['generic'])) {
                        $configurationObject->setGeneric($configurationItem['generic']);
                    }
                    if (isset($configurationItem['cat'])) {
                        $configurationObject->setCategory($configurationItem['cat']);
                    }
                    if (isset($configurationItem['subcat_name'])) {
                        $configurationObject->setSubCategory($configurationItem['subcat_name']);
                    }
                    if (isset($configurationItem['labels']) && isset($configurationItem['labels'][0])) {
                        $configurationObject->setLabelHeadline($configurationItem['labels'][0]);
                    }
                    if (isset($configurationItem['labels']) && isset($configurationItem['labels'][1])) {
                        $configurationObject->setLabelText($configurationItem['labels'][1]);
                    }
                    if (isset($configurationItem['type'])) {
                        $configurationObject->setType($configurationItem['type']);
                    }
                    if (isset($configurationItem['name'])) {
                        $configurationObject->setName($configurationItem['name']);
                    }
                    if (isset($configurationItem['value'])) {
                        $configurationObject->setValue($configurationItem['value']);
                    }
                    if (isset($configurationItem['highlight'])) {
                        $configurationObject->setHighlight($configurationItem['highlight']);
                    }
                    $configurationSubcategoryObject->addItem($configurationObject);
                }
                $configurationCategoryObject->addSubcategory($configurationSubcategoryObject);
            }
            $configurationObjectStorage->attach($configurationCategoryObject);
        }
        return $configurationObjectStorage;
    }

    /**
     * Returns the localized label of the LOCAL_LANG key, $key.
     * Wrapper for the static call.
     *
     * @param string $key The key from the LOCAL_LANG array for which to return the value.
     * @param string $extensionName The name of the extension
     * @return string|NULL The value from LOCAL_LANG or NULL if no translation was found.
     */
    protected function translate($key, $extensionName)
    {
        $translation = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($key, $extensionName);
        if ($translation) {
            return $translation;
        }
        return null;
    }
}
