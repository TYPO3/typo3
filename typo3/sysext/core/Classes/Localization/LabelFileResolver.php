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

namespace TYPO3\CMS\Core\Localization;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\Exception\FileNotFoundException;
use TYPO3\CMS\Core\Package\Exception\UnknownPackageException;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Service class for resolving label file paths and determining loading order.
 *
 * This class handles:
 * - Path resolving for localization files
 * - File name resolving with language variants
 * - Detecting resource override files via $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']
 * - Determining file loading order without merging content
 *
 * This class does not handle reading of file contents, and also returns the full path,
 * so it does not care about caching. Should be handled at a more outer stage.
 *
 * @internal not part of TYPO3's public API.
 */
#[Autoconfigure(public: true)]
readonly class LabelFileResolver
{
    public function __construct(
        protected PackageManager $packageManager,
    ) {}

    /**
     * Find all label files in a package, but also find overrides.
     * All files are returned in the order they should be loaded.
     */
    public function getAllLabelFilesOfPackage(string $packageKey, $defaultLocaleOnlyForCacheWarmup = false): array
    {
        $result = [];
        try {
            $packagePath = $this->packageManager->getPackage($packageKey)->getPackagePath();
        } catch (UnknownPackageException) {
            throw new \InvalidArgumentException(sprintf('Package with key "%s" not found', $packageKey), 1760479988);
        }
        $directoriesToSearch = [
            'Resources/Private/Language/',
            'Configuration/Sets/',
        ];
        $allowedFileExtensions = $this->getSupportedExtensions();
        $allowedFileExtensions = implode(',', $allowedFileExtensions);
        foreach ($directoriesToSearch as $searchPath) {
            $searchPath = $packagePath . $searchPath;
            $files = GeneralUtility::getAllFilesAndFoldersInPath([], $searchPath, $allowedFileExtensions);
            foreach ($files as $file) {
                $fileName = PathUtility::basename($file);
                $locale = $this->getLocaleFromLanguageFile($fileName);
                if ($locale === null) {
                    $locale = 'default';
                }
                if ($defaultLocaleOnlyForCacheWarmup && $locale !== 'default') {
                    continue;
                }

                $relativeFilePath = substr($file, strlen($packagePath));
                $fileReference = 'EXT:' . $packageKey . '/' . $relativeFilePath;

                if ($defaultLocaleOnlyForCacheWarmup) {
                    $result[$locale][] = $fileReference;
                    continue;
                }

                try {
                    $orderedFiles = $this->getOrderedFileResources($fileReference, $locale);
                    if ($orderedFiles !== []) {
                        if (!isset($result[$locale])) {
                            $result[$locale] = [];
                        }
                        $result[$locale] = array_merge($result[$locale], $orderedFiles);
                    }
                } catch (FileNotFoundException $e) {
                }
            }
        }
        return $result;
    }

    /**
     * If a file is called "de_AT.locallang.xlf", this method returns "de_AT".
     * If there is no suffix, NULL is returned.
     *
     * However, for files like "db.xlf", "db" should not be detected as locale
     */
    public function getLocaleFromLanguageFile(string $fileName): ?string
    {
        if (substr_count($fileName, '.') > 1 && preg_match('/^[a-z]{2}([_-][A-z]{2,3})?\./', $fileName)) {
            return substr($fileName, 0, strpos($fileName, '.'));
        }
        return null;
    }

    /**
     * Finds the actual files needed to resolve a file resource.
     * This should be used later-on directly in LocalizationFactory.
     */
    protected function getOrderedFileResources(string $fileReference, string $locale): array
    {
        $result = [];
        try {
            $baseFile = $this->resolveFileReference($fileReference, $locale);
            if ($baseFile !== null) {
                $result[] = $baseFile;
            }
        } catch (FileNotFoundException $e) {

        }
        $overrideFiles = $this->getOverrideFilePaths($fileReference, $locale);
        if ($overrideFiles !== []) {
            $result = array_merge($result, $overrideFiles);
        }
        return $result;
    }

    /**
     * @throws FileNotFoundException
     */
    public function resolveFileReference(string $fileReference, string $locale): ?string
    {
        $actualSourcePath = $this->getAbsoluteFileReference($fileReference);
        if (PathUtility::isExtensionPath($fileReference)) {
            $actualSourcePath = $this->resolveExtensionResourcePath($actualSourcePath, $locale, $fileReference);
        }

        if ($locale === 'default') {
            // The "default" (=base) locale must not contain other language entries.
            // Otherwise, when checking for a base locale file, it will hold an array of ALL
            // other language variants, and then due to alphabetical sorting, any language with a
            // first character AFTER "l" (locallang) would be regarded as the base entry.
            return $actualSourcePath;
        }

        // Find localized file. If no localized version exists, return null.
        $localizedSourcePath = $this->resolveLocalizedFilePath($actualSourcePath, $locale);
        return $localizedSourcePath;
    }

    /**
     * Get override file paths for localization
     *
     * This method returns an array of override file paths that should be loaded
     * for the given file reference and language key
     *
     * @return array<string> Array of absolute file paths to override files
     */
    public function getOverrideFilePaths(string $fileReference, string $locale): array
    {
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'])) {
            return [];
        }

        $validOverrideFiles = [];
        $fileReferenceWithoutExtension = $this->getFileReferenceWithoutExtension($fileReference);
        $overrideFiles = $GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides'];
        $supportedExtensions = $this->getSupportedExtensions();

        foreach ($supportedExtensions as $extension) {
            $fullFileReference = $fileReferenceWithoutExtension . '.' . $extension;

            // Check language-specific overrides first
            if (isset($overrideFiles[$locale][$fullFileReference]) && is_array($overrideFiles[$locale][$fullFileReference])) {
                $validOverrideFiles = array_merge($validOverrideFiles, $overrideFiles[$locale][$fullFileReference]);
            }
            // Check general overrides (applies to all languages)
            elseif (isset($overrideFiles[$fullFileReference]) && is_array($overrideFiles[$fullFileReference])) {
                $validOverrideFiles = array_merge($validOverrideFiles, $overrideFiles[$fullFileReference]);
            }
        }

        // Convert relative paths to absolute paths
        $absoluteOverrideFiles = [];
        foreach ($validOverrideFiles as $overrideFile) {
            if (PathUtility::isExtensionPath($overrideFile)) {
                $absoluteOverrideFiles[] = $overrideFile;
            } else {
                $absolutePath = GeneralUtility::getFileAbsFileName($overrideFile);
                if ($absolutePath) {
                    $absoluteOverrideFiles[] = $absolutePath;
                }
            }
        }

        return $absoluteOverrideFiles;
    }

    /**
     * Get absolute file reference
     *
     * @throws FileNotFoundException Source localization file not found.
     */
    protected function getAbsoluteFileReference(string $fileReference): string
    {
        $fileReferenceWithoutExtension = $this->getFileReferenceWithoutExtension($fileReference);
        $supportedExtensions = $this->getSupportedExtensions();

        foreach ($supportedExtensions as $extension) {
            $fullPath = GeneralUtility::getFileAbsFileName($fileReferenceWithoutExtension . '.' . $extension);
            if (@is_file($fullPath)) {
                return $fullPath;
            }
        }

        throw new FileNotFoundException(sprintf('Source localization file (%s) not found', $fileReference), 1306410755);
    }

    public function getFileReferenceWithoutExtension(string $fileReference): string
    {
        return preg_replace('/\\.[a-z0-9]+$/i', '', $fileReference) ?? $fileReference;
    }

    /**
     * Get localized labels path pattern for extensions
     */
    protected function getLocalizedLabelsPathPattern(string $fileReference): string
    {
        if (!PathUtility::isExtensionPath($fileReference)) {
            throw new \InvalidArgumentException(sprintf('Invalid file reference configuration for the current file (%s)', $fileReference), 1635863703);
        }

        $packageKey = $this->packageManager->extractPackageKeyFromPackagePath($fileReference);
        $relativeFileName = substr($fileReference, strlen($packageKey) + 5);
        $directory = dirname($relativeFileName);
        $fileName = basename($relativeFileName);

        return sprintf(
            '/%%1$s/%s/%s%%1$s.%s',
            $packageKey,
            ($directory !== '.' ? $directory . '/' : ''),
            $fileName
        );
    }

    protected function getSupportedExtensions(): array
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['LANG']['format']['priority']) && trim($GLOBALS['TYPO3_CONF_VARS']['LANG']['format']['priority']) !== '') {
            return GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['LANG']['format']['priority']);
        }
        return ['xlf'];
    }

    protected function resolveExtensionResourcePath(string $sourcePath, string $locale, string $fileReference): string
    {
        $localizedLabelsPathPattern = $this->getLocalizedLabelsPathPattern($fileReference);
        $fileName = Environment::getLabelsPath() . sprintf($localizedLabelsPathPattern, $locale);

        if (@is_file($fileName)) {
            return $fileName;
        }

        // Fallback to source path if localized version doesn't exist
        return $sourcePath;
    }

    /**
     * Resolve localized file path by trying multiple location strategies.
     *
     * This method attempts to find localized versions of files in the following order:
     * 1. Check if the file already has the correct locale prefix (early return)
     * 2. Try same directory with locale prefix: "de.locallang.xlf" in same folder
     * 3. Try TYPO3 labels directory structure: "/var/labels/de/extension_key/path/de.filename.xlf"
     *
     * Language variant handling:
     * - Supports both underscore and hyphen variants: "de_CH" <-> "de-CH"
     * - Tests both formats when resolving files
     *
     * Examples:
     * - Source: "/ext/core/Resources/Private/Language/locallang.xlf"
     * - For locale "de": tries "de.locallang.xlf" in same dir, then "/var/labels/de/core/Resources/Private/Language/de.locallang.xlf"
     * - For locale "de-CH": tries both "de-CH.locallang.xlf" and "de_CH.locallang.xlf" variants
     *
     * @param string $sourcePath Absolute path to the source localization file
     * @param string $locale Language locale (e.g., "de", "de-CH", "de_AT")
     * @return ?string Absolute path to the localized file, or null if no localized version found
     */
    protected function resolveLocalizedFilePath(string $sourcePath, string $locale): ?string
    {
        $possiblePrefixes = [$locale];
        if (str_contains($locale, '_')) {
            $possiblePrefixes[] = str_replace('_', '-', $locale);
        } elseif (str_contains($locale, '-')) {
            $possiblePrefixes[] = str_replace('-', '_', $locale);
        }
        $packageRootPaths = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $packageRootPaths[$package->getPackageKey()] = $package->getPackagePath();
        }

        foreach ($possiblePrefixes as $fileNamePrefix) {
            $fileName = PathUtility::basename($sourcePath);
            if (str_starts_with($fileName, $fileNamePrefix . '.')) {
                return $sourcePath;
            }

            // Try same location first
            $sameLocationPath = str_replace($fileName, $fileNamePrefix . '.' . $fileName, $sourcePath);
            if (@is_file($sameLocationPath)) {
                return $sameLocationPath;
            }

            // Try labels directory structure
            $relativePathInPackagePath = '';
            $extensionKey = null;
            foreach ($packageRootPaths as $packageKey => $packageRootPath) {
                if (str_starts_with($sourcePath, $packageRootPath)) {
                    $relativePathInPackagePath = substr($sourcePath, strlen($packageRootPath));
                    $extensionKey = $packageKey;
                    break;
                }
            }
            if ($relativePathInPackagePath === '' || $extensionKey === null) {
                continue;
            }

            [$relativePathInPackagePath, $baseName] = GeneralUtility::revExplode('/', $relativePathInPackagePath, 2);
            $localizedPath = Environment::getLabelsPath() . '/' . $fileNamePrefix . '/' . $extensionKey . '/' . ($relativePathInPackagePath ? $relativePathInPackagePath . '/' : '') . $fileNamePrefix . '.' . $baseName;

            if (@is_file($localizedPath)) {
                return $localizedPath;
            }
        }

        return null;
    }
}
