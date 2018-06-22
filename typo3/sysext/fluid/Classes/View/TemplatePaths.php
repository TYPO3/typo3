<?php
namespace TYPO3\CMS\Fluid\View;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class TemplatePaths
 *
 * Custom implementation for template paths resolving, one which differs from the base
 * implementation in that it is capable of resolving template paths based on TypoScript
 * configuration when given a package name, and is aware of the Frontend/Backend contexts of TYPO3.
 */
class TemplatePaths extends \TYPO3Fluid\Fluid\View\TemplatePaths
{
    /**
     * @var string
     */
    protected $templateSource;

    /**
     * @var string
     */
    protected $templatePathAndFilename;

    /**
     * @param string $extensionKey
     * @return string|null
     */
    protected function getExtensionPrivateResourcesPath($extensionKey)
    {
        $extensionKey = trim($extensionKey);
        if ($extensionKey && ExtensionManagementUtility::isLoaded($extensionKey)) {
            return ExtensionManagementUtility::extPath($extensionKey) . 'Resources/Private/';
        }
        return null;
    }

    /**
     * @return ConfigurationManagerInterface
     */
    protected function getConfigurationManager()
    {
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        return $configurationManager;
    }

    /**
     * @param string $extensionKey
     * @return array
     */
    protected function getContextSpecificViewConfiguration($extensionKey)
    {
        if (empty($extensionKey)) {
            return [];
        }

        $resources = $this->getExtensionPrivateResourcesPath($extensionKey);
        $paths = [
            self::CONFIG_TEMPLATEROOTPATHS => [$resources . 'Templates/'],
            self::CONFIG_PARTIALROOTPATHS => [$resources . 'Partials/'],
            self::CONFIG_LAYOUTROOTPATHS => [$resources . 'Layouts/']
        ];

        $configuredPaths = [];
        if (!empty($this->templateRootPaths) || !empty($this->partialRootPaths) || !empty($this->layoutRootPaths)) {
            // The view was configured already
            $configuredPaths = [
                self::CONFIG_TEMPLATEROOTPATHS => $this->templateRootPaths,
                self::CONFIG_PARTIALROOTPATHS => $this->partialRootPaths,
                self::CONFIG_LAYOUTROOTPATHS => $this->layoutRootPaths,
            ];
        } else {
            $typoScript = (array)$this->getConfigurationManager()->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            $signature = str_replace('_', '', $extensionKey);
            if ($this->isBackendMode() && isset($typoScript['module.']['tx_' . $signature . '.']['view.'])) {
                $configuredPaths = (array)$typoScript['module.']['tx_' . $signature . '.']['view.'];
                $configuredPaths = GeneralUtility::removeDotsFromTS($configuredPaths);
            } elseif ($this->isFrontendMode() && isset($typoScript['plugin.']['tx_' . $signature . '.']['view.'])) {
                $configuredPaths = (array)$typoScript['plugin.']['tx_' . $signature . '.']['view.'];
                $configuredPaths = GeneralUtility::removeDotsFromTS($configuredPaths);
            }
        }

        if (empty($configuredPaths)) {
            return $paths;
        }

        foreach ($paths as $name => $defaultPaths) {
            if (!empty($configuredPaths[$name])) {
                $paths[$name] = array_merge($defaultPaths, ArrayUtility::sortArrayWithIntegerKeys((array)$configuredPaths[$name]));
            }
        }

        return $paths;
    }

    /**
     * Fills the path arrays with defaults, by package name.
     * Reads those defaults from TypoScript if possible and
     * if not defined, uses fallback paths by convention.
     *
     * @param string $packageName
     */
    public function fillDefaultsByPackageName($packageName)
    {
        $this->fillFromConfigurationArray($this->getContextSpecificViewConfiguration($packageName));
    }

    /**
     * Overridden setter with enforced sorting behavior
     *
     * @param array $templateRootPaths
     */
    public function setTemplateRootPaths(array $templateRootPaths)
    {
        parent::setTemplateRootPaths(
            ArrayUtility::sortArrayWithIntegerKeys($templateRootPaths)
        );
    }

    /**
     * Overridden setter with enforced sorting behavior
     *
     * @param array $layoutRootPaths
     */
    public function setLayoutRootPaths(array $layoutRootPaths)
    {
        parent::setLayoutRootPaths(
            ArrayUtility::sortArrayWithIntegerKeys($layoutRootPaths)
        );
    }

    /**
     * Overridden setter with enforced sorting behavior
     *
     * @param array $partialRootPaths
     */
    public function setPartialRootPaths(array $partialRootPaths)
    {
        parent::setPartialRootPaths(
            ArrayUtility::sortArrayWithIntegerKeys($partialRootPaths)
        );
    }

    /**
     * Public API for currently protected method. Can be dropped when switching to
     * Fluid 1.1.0 or above.
     *
     * @param string $partialName
     * @return string
     */
    public function getPartialPathAndFilename($partialName)
    {
        return parent::getPartialPathAndFilename($partialName);
    }

    /**
     * Get absolute path to template file
     *
     * @return string Returns the absolute path to a Fluid template file
     */
    public function getTemplatePathAndFilename()
    {
        return $this->templatePathAndFilename;
    }

    /**
     * Guarantees that $reference is turned into a
     * correct, absolute path. The input can be a
     * relative path or a FILE: or EXT: reference
     * but cannot be a FAL resource identifier.
     *
     * @param mixed $reference
     * @return string
     */
    protected function ensureAbsolutePath($reference)
    {
        if (!is_array($reference)) {
            return PathUtility::isAbsolutePath($reference) ? $reference : GeneralUtility::getFileAbsFileName($reference);
        }
        foreach ($reference as &$subValue) {
            $subValue = $this->ensureAbsolutePath($subValue);
        }
        return $reference;
    }

    /**
     * @return bool
     */
    protected function isBackendMode()
    {
        return TYPO3_MODE === 'BE';
    }

    /**
     * @return bool
     */
    protected function isFrontendMode()
    {
        return TYPO3_MODE === 'FE';
    }
}
