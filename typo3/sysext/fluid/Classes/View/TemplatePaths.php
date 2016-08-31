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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
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
     * @var array
     */
    protected $typoScript = [];

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
     * @return string|NULL
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
        $cache = $this->getRuntimeCache();
        $cacheIdentifier = 'viewpaths_' . $extensionKey;
        $configuration = $cache->get($cacheIdentifier);
        if (!empty($configuration)) {
            return $configuration;
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
            if (empty($this->typoScript)) {
                $this->typoScript = GeneralUtility::removeDotsFromTS(
                    $this->getConfigurationManager()->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
                );
            }
            $signature = str_replace('_', '', $extensionKey);
            if (TYPO3_MODE === 'BE' && isset($this->typoScript['module']['tx_' . $signature]['view'])) {
                $configuredPaths = (array)$this->typoScript['module']['tx_' . $signature]['view'];
            } elseif (TYPO3_MODE === 'FE' && isset($this->typoScript['plugin']['tx_' . $signature]['view'])) {
                $configuredPaths = (array)$this->typoScript['plugin']['tx_' . $signature]['view'];
            }
        }

        foreach ($paths as $name => $defaultPaths) {
            if (!empty($configuredPaths[$name])) {
                $paths[$name] = (array)$configuredPaths[$name] + $defaultPaths;
            }
        }

        $cache->set($cacheIdentifier, $paths);
        return $paths;
    }

    /**
     * @return VariableFrontend
     */
    protected function getRuntimeCache()
    {
        return GeneralUtility::makeInstance(CacheManager::class)->getCache('cache_runtime');
    }

    /**
     * @param string|array $path
     * @return string
     */
    protected function sanitizePath($path)
    {
        if (is_array($path)) {
            $paths = array_map([$this, 'sanitizePath'], $path);
            return array_unique($paths);
        }
        $path = $this->ensureAbsolutePath($path);
        if (is_dir($path)) {
            $path = $this->ensureSuffixedPath($path);
        }
        return $path;
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
        if (false === is_array($reference)) {
            $filename = PathUtility::isAbsolutePath($reference) ? $reference : GeneralUtility::getFileAbsFileName($reference);
        } else {
            foreach ($reference as &$subValue) {
                $subValue = $this->ensureAbsolutePath($subValue);
            }

            return $reference;
        }

        return $filename;
    }

    /**
     * Fills the path arrays with defaults, by package name.
     * Reads those defaults from TypoScript if possible and
     * if not defined, uses fallback paths by convention.
     *
     * @param string $packageName
     * @return void
     */
    public function fillDefaultsByPackageName($packageName)
    {
        $this->fillFromConfigurationArray($this->getContextSpecificViewConfiguration($packageName));
    }

    /**
     * Overridden setter with enforced sorting behavior
     *
     * @param array $templateRootPaths
     * @return void
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
     * @return void
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
     * @return void
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
}
