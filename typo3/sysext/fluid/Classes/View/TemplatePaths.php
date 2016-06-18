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
    protected $typoScript = array();

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
            return array();
        }
        if (empty($this->typoScript)) {
            $this->typoScript = GeneralUtility::removeDotsFromTS(
                $this->getConfigurationManager()->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT)
            );
        }
        $signature = str_replace('_', '', $extensionKey);
        $resources = $this->getExtensionPrivateResourcesPath($extensionKey);
        $configuration = array();
        $paths = array(
            self::CONFIG_TEMPLATEROOTPATHS => array($resources . 'Templates/'),
            self::CONFIG_PARTIALROOTPATHS => array($resources . 'Partials/'),
            self::CONFIG_LAYOUTROOTPATHS => array($resources . 'Layouts/')
        );
        if (TYPO3_MODE === 'BE' && isset($this->typoScript['module']['tx_' . $signature]['view'])) {
            $configuration = (array) $this->typoScript['module']['tx_' . $signature]['view'];
        } elseif (TYPO3_MODE === 'FE' && isset($this->typoScript['plugin']['tx_' . $signature]['view'])) {
            $configuration = (array) $this->typoScript['plugin']['tx_' . $signature]['view'];
        }
        foreach ($paths as $name => $values) {
            $paths[$name] = $values + (array) $configuration[$name];
        }
        return $paths;
    }

    /**
     * @param string|array $path
     * @return string
     */
    protected function sanitizePath($path)
    {
        if (is_array($path)) {
            $paths = array_map(array($this, 'sanitizePath'), $path);
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
}
