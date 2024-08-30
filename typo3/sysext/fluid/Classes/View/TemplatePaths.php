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

namespace TYPO3\CMS\Fluid\View;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Custom implementation for template paths resolving, one which differs from the base
 * implementation in that it is capable of resolving template paths based on TypoScript
 * configuration when given a package name, and is aware of the Frontend/Backend contexts of TYPO3.
 *
 * @internal This is for internal Fluid use only.
 */
class TemplatePaths extends \TYPO3Fluid\Fluid\View\TemplatePaths
{
    /**
     * Fills the path arrays with defaults, by package name.
     * Reads those defaults from TypoScript if possible and
     * if not defined, uses fallback paths by convention.
     *
     * @param string $packageName
     * @deprecated since TYPO3 v13, will be removed in v14. Also removed in Fluid standalone v4.
     */
    public function fillDefaultsByPackageName($packageName): void
    {
        trigger_error(
            __CLASS__ . '->' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v14.'
            . ' Please calculate proper paths in the consuming class and use the setters'
            . ' to set template, partial and layout paths.',
            E_USER_DEPRECATED
        );
        $this->fillFromConfigurationArray($this->getContextSpecificViewConfiguration($packageName));
    }

    /**
     * Overridden setter with enforced sorting behavior
     */
    public function setTemplateRootPaths(array $templateRootPaths): void
    {
        parent::setTemplateRootPaths(ArrayUtility::sortArrayWithIntegerKeys($templateRootPaths));
    }

    /**
     * Overridden setter with enforced sorting behavior
     */
    public function setLayoutRootPaths(array $layoutRootPaths): void
    {
        parent::setLayoutRootPaths(ArrayUtility::sortArrayWithIntegerKeys($layoutRootPaths));
    }

    /**
     * Overridden setter with enforced sorting behavior
     */
    public function setPartialRootPaths(array $partialRootPaths): void
    {
        parent::setPartialRootPaths(ArrayUtility::sortArrayWithIntegerKeys($partialRootPaths));
    }

    /**
     * Get absolute path to template file
     *
     * @return string|null Returns the absolute path to a Fluid template file
     */
    public function getTemplatePathAndFilename(): ?string
    {
        return $this->templatePathAndFilename;
    }

    /**
     * Guarantees that $reference is turned into a
     * correct, absolute path. The input can be a
     * relative path or a FILE: or EXT: reference
     * but cannot be a FAL resource identifier.
     *
     * @param string $reference
     */
    protected function ensureAbsolutePath($reference): string
    {
        return PathUtility::isAbsolutePath($reference) ? $reference : GeneralUtility::getFileAbsFileName($reference);
    }

    /**
     * @deprecated Remove in v14 along with fillDefaultsByPackageName()
     */
    protected function getContextSpecificViewConfiguration(string $extensionKey): array
    {
        if (empty($extensionKey)) {
            return [];
        }
        $resources = $this->getExtensionPrivateResourcesPath($extensionKey);
        $paths = [
            self::CONFIG_TEMPLATEROOTPATHS => [$resources . 'Templates/'],
            self::CONFIG_PARTIALROOTPATHS => [$resources . 'Partials/'],
            self::CONFIG_LAYOUTROOTPATHS => [$resources . 'Layouts/'],
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
            $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
            $typoScript = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
            $signature = str_replace('_', '', $extensionKey);
            $isBackendMode = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend();
            $isFrontendMode = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
            if ($isBackendMode && isset($typoScript['module.']['tx_' . $signature . '.']['view.'])) {
                $configuredPaths = (array)$typoScript['module.']['tx_' . $signature . '.']['view.'];
                $configuredPaths = GeneralUtility::removeDotsFromTS($configuredPaths);
            } elseif ($isFrontendMode && isset($typoScript['plugin.']['tx_' . $signature . '.']['view.'])) {
                $configuredPaths = (array)$typoScript['plugin.']['tx_' . $signature . '.']['view.'];
                $configuredPaths = GeneralUtility::removeDotsFromTS($configuredPaths);
            }
        }
        if (empty($configuredPaths)) {
            return $paths;
        }
        foreach ($paths as $name => $defaultPaths) {
            if (!empty($configuredPaths[$name])) {
                $configured = ArrayUtility::sortArrayWithIntegerKeys((array)$configuredPaths[$name]);
                // calculate implicit default paths which have not been explicitly configured
                $implicitDefaultPaths = array_diff($defaultPaths, $configured);
                // prepend implicit default paths (which have not been found in configured paths), as fallbacks
                $paths[$name] = array_merge($implicitDefaultPaths, $configured);
            }
        }
        return $paths;
    }

    /**
     * @deprecated Remove in v14 along with fillDefaultsByPackageName()
     */
    protected function getExtensionPrivateResourcesPath(string $extensionKey): ?string
    {
        $extensionKey = trim($extensionKey);
        if ($extensionKey && ExtensionManagementUtility::isLoaded($extensionKey)) {
            return ExtensionManagementUtility::extPath($extensionKey) . 'Resources/Private/';
        }
        return null;
    }
}
