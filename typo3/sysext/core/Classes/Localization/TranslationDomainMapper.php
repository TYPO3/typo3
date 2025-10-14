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

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Localization\Event\BeforeLabelResourceResolvedEvent;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Maps between translation domains and label resource file paths.
 *
 * Translation domains provide a shorter, semantic alternative to verbose file paths for
 * referencing label resources (XLF files).
 *
 * Domain Format: package[.subdomain[.subdomain...]].resource
 *
 * Domain Generation Rules:
 * - Extension language files: [package].[subdir].[filename]
 * - Site Set files: [package].sets.[setName]
 * - Subdirectories are represented as dot-separated parts
 * - Special handling for locale-prefixed files
 *
 * - subdirectory "Resources/Private/Language" is omitted
 * - subdirectory "Configuration/Sets/{set-name}/labels.xlf" is replaced with "sets.{set-name}"
 *
 * UpperCamelCase is converted to snake_case.
 *
 * "locallang.xlf" is mapped to "messages"
 * "locallang_{mysuffix}.xlf" is replaced via "mysuffix" (keeping underscores)
 *
 * Locale prefixes (e.g. "de.locallang.xlf") are ignored in the domain name.
 *
 * Package Identifier Resolution:
 * - Uses extension keys: "core", "backend"
 * - Composer package names can be used as input and will be resolved to extension keys
 *
 * Once a domain for a package is requested, the cache for this package is built.
 *
 * @internal not part of TYPO3's public API.
 */
readonly class TranslationDomainMapper
{
    public function __construct(
        protected PackageManager $packageManager,
        protected LabelFileResolver $labelFileResolver,
        #[Autowire(service: 'cache.l10n')]
        protected FrontendInterface $labelCache,
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Fetches all found label files, this way, we can use the domain to populate the label cache
     * of a Package, as it also COULD include the package.
     *
     * When multiple files map to the same domain (e.g., locallang.xlf and messages.xlf both map
     * to ".messages"), files without the "locallang" prefix/suffix have precedence.
     *
     * Precedence order:
     * 1. Files without "locallang" prefix (e.g., messages.xlf, tabs.xlf)
     * 2. Files with "locallang_" prefix (e.g., locallang_toolbar.xlf)
     * 3. Plain locallang.xlf
     */
    public function findLabelResourcesInPackage(string $packageKey): array
    {
        $packageKey = $this->packageManager->getPackageKeyFromComposerName($packageKey);
        $cacheIdentifier = 'translation-domains-of-package-' . $packageKey;
        $allLabelFilesOfPackage = $this->labelCache->get($cacheIdentifier);
        if (is_array($allLabelFilesOfPackage)) {
            return $allLabelFilesOfPackage;
        }
        $allLabelFilesOfPackage = $this->labelFileResolver->getAllLabelFilesOfPackage($packageKey);
        $domains = [];
        $domainPriorities = [];
        $package = $this->packageManager->getPackage($packageKey);
        foreach ($allLabelFilesOfPackage['en'] ?? [] as $file) {
            // Make path relative
            $file = $this->getRelativePath($file, $packageKey, $package);

            $domain = $this->mapFileNameToDomain($file);
            $priority = $this->getFilePriority($file);

            // Only set/overwrite if this file has higher or equal priority
            if (!isset($domains[$domain]) || $priority >= $domainPriorities[$domain]) {
                $domains[$domain] = $file;
                $domainPriorities[$domain] = $priority;
            }
        }
        $event = new BeforeLabelResourceResolvedEvent(
            $packageKey,
            $domains
        );
        $event = $this->eventDispatcher->dispatch($event);
        $this->labelCache->set($cacheIdentifier, $event->domains);
        return $event->domains;
    }

    /**
     * Find label resources in a package grouped by locale.
     *
     * Returns an array where keys are locale codes and values are arrays of domain => resource mappings.
     * Applies the same precedence rules as findLabelResourcesInPackage().
     *
     * @return array<string, array<string, string>> Array grouped by locale, then domain => resource
     */
    public function findLabelResourcesInPackageGroupedByLocale(string $packageKey): array
    {
        $packageKey = $this->packageManager->getPackageKeyFromComposerName($packageKey);
        $package = $this->packageManager->getPackage($packageKey);
        $allLabelFilesOfPackage = $this->labelFileResolver->getAllLabelFilesOfPackage($packageKey);

        $domainsByLocale = [];
        $prioritiesByLocale = [];

        foreach ($allLabelFilesOfPackage as $locale => $files) {
            $domainsByLocale[$locale] = [];
            $prioritiesByLocale[$locale] = [];

            foreach ($files as $file) {
                $file = $this->getRelativePath($file, $packageKey, $package);
                $domain = $this->mapFileNameToDomain($file);
                $priority = $this->getFilePriority($file);

                // Only set/overwrite if this file has higher or equal priority
                if (!isset($domainsByLocale[$locale][$domain]) || $priority >= $prioritiesByLocale[$locale][$domain]) {
                    $domainsByLocale[$locale][$domain] = $file;
                    $prioritiesByLocale[$locale][$domain] = $priority;
                }
            }
        }

        return $domainsByLocale;
    }

    /**
     * Determine file priority for domain collision resolution.
     *
     * Higher priority wins when multiple files map to the same domain.
     *
     * Priority levels:
     * - 3: Files without "locallang" prefix (messages.xlf, tabs.xlf)
     * - 2: Files with "locallang_" prefix (locallang_toolbar.xlf)
     * - 1: Plain locallang.xlf
     *
     * This ensures modern naming (messages.xlf) takes precedence over legacy (locallang.xlf).
     */
    protected function getFilePriority(string $filePath): int
    {
        $fileName = basename($filePath);
        $fileNameWithoutExtension = $this->labelFileResolver->getFileReferenceWithoutExtension($fileName);

        // Remove locale prefix if present (e.g., "de.locallang.xlf" -> "locallang.xlf")
        $locale = $this->labelFileResolver->getLocaleFromLanguageFile($fileName);
        if ($locale !== null) {
            $fileNameWithoutExtension = substr($fileNameWithoutExtension, strlen($locale) + 1);
        }

        // Priority 3: Files without "locallang" prefix (e.g., messages.xlf, tabs.xlf)
        if (!str_contains($fileNameWithoutExtension, 'locallang')) {
            return 3;
        }

        // Priority 2: Files with "locallang_" prefix (e.g., locallang_toolbar.xlf)
        if (str_starts_with($fileNameWithoutExtension, 'locallang_')) {
            return 2;
        }

        // Priority 1: Plain locallang.xlf
        return 1;
    }

    /**
     * Maps a translation domain to a label resource file reference.
     *
     * Examples:
     * - "core.messages" -> "EXT:core/Resources/Private/Language/locallang.xlf"
     * - "backend.toolbar" -> "EXT:backend/Resources/Private/Language/locallang_toolbar.xlf"
     * - "core.form.tabs" -> "EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf"
     * - "felogin.sets.felogin" -> "EXT:felogin/Configuration/Sets/Felogin/labels.xlf"
     *
     * Invalid domain names return their values directly.
     * 1. More than one ":" included
     * 2. Domain Name contains other than [a-z0-9_.] characters
     */
    public function mapDomainToFileName(string $domain): string
    {
        // In order to be deterministic, we need to find the proper file name to reference
        // now. It IS possible that the incoming domain is actually valid, so we skip it.

        // If it's already an EXT: reference or absolute path, return as-is
        if (str_starts_with($domain, 'EXT:') || GeneralUtility::isAllowedAbsPath($domain)) {
            return $domain;
        }
        if (!$this->isValidDomainName($domain)) {
            return $domain;
        }

        // Parse domain into extension key part and resource part
        [$extensionKey, $resourcePart] = explode('.', $domain, 2);

        $allDomainsInPackage = $this->findLabelResourcesInPackage($extensionKey);

        // Fall back to domain, in case it's just a file reference.
        return $allDomainsInPackage[$domain] ?? $domain;
    }

    /**
     * Maps a label resource file reference to a translation domain.
     *
     * Examples:
     * - "EXT:core/Resources/Private/Language/locallang.xlf" -> "core.messages"
     * - "EXT:backend/Resources/Private/Language/locallang_toolbar.xlf" -> "backend.toolbar"
     * - "EXT:felogin/Configuration/Sets/Felogin/labels.xlf" -> "felogin.sets.felogin"
     */
    public function mapFileNameToDomain(string $fileName): string
    {
        // Extract extension key from EXT: path
        try {
            $extensionKey = $this->extractExtensionKey($fileName);
        } catch (\InvalidArgumentException) {
            return $fileName;
        }

        // Transform file path to resource name
        $resourceName = $this->transformFilePathToResource($fileName, $extensionKey);
        return $extensionKey . '.' . $resourceName;
    }

    /**
     * Transforms a file path to a resource name (for domain).
     *
     * Examples:
     * - "Resources/Private/Language/locallang.xlf" -> "messages"
     * - "Resources/Private/Language/locallang_toolbar.xlf" -> "toolbar"
     * - "Resources/Private/Language/Form/locallang_tabs.xlf" -> "form.tabs"
     * - "Configuration/Sets/Felogin/labels.xlf" -> "sets.felogin"
     */
    protected function transformFilePathToResource(string $filePath, string $extensionKey): string
    {
        // Remove EXT:extensionKey/ prefix
        $prefix = 'EXT:' . $extensionKey . '/';
        if (str_starts_with($filePath, $prefix)) {
            $filePath = substr($filePath, strlen($prefix));
        }

        // Remove locale prefix if present (e.g., "de.locallang.xlf" -> "locallang.xlf")
        $fileName = basename($filePath);
        $locale = $this->labelFileResolver->getLocaleFromLanguageFile($fileName);
        if ($locale !== null) {
            $fileName = substr($fileName, strlen($locale) + 1);
            $filePath = dirname($filePath) . '/' . $fileName;
        }

        // Handle site sets: Configuration/Sets/{Name}/labels.xlf -> sets.{name}
        if (preg_match('#Configuration/Sets/([^/]+)/labels\.xlf$#', $filePath, $matches)) {
            $setName = $this->upperCamelCaseToSnakeCase($matches[1]);
            return 'sets.' . $setName;
        }

        // Handle standard language files: Resources/Private/Language/...
        if (str_starts_with($filePath, 'Resources/Private/Language/')) {
            $relativePath = substr($filePath, strlen('Resources/Private/Language/'));

            // Split into directory and filename
            $pathParts = explode('/', $relativePath);
            $fileName = array_pop($pathParts);

            // Convert directories from UpperCamelCase to snake_case
            $resourceParts = array_map([$this, 'upperCamelCaseToSnakeCase'], $pathParts);

            // Transform filename
            $fileNameWithoutExtension = $this->labelFileResolver->getFileReferenceWithoutExtension($fileName);

            if ($fileNameWithoutExtension === 'locallang') {
                $resourceParts[] = 'messages';
            } elseif (str_starts_with($fileNameWithoutExtension, 'locallang_')) {
                // Remove locallang_ prefix (keep snake_case as-is)
                $suffix = substr($fileNameWithoutExtension, strlen('locallang_'));
                $suffix = $this->upperCamelCaseToSnakeCase($suffix);
                $resourceParts[] = $suffix;
            } else {
                // Use filename as-is, converted to snake_case
                $resourceParts[] = $this->upperCamelCaseToSnakeCase($fileNameWithoutExtension);
            }

            return implode('.', $resourceParts);
        }

        // Fallback: use filename without extension
        $fileNameWithoutExtension = $this->labelFileResolver->getFileReferenceWithoutExtension(basename($filePath));
        return $this->upperCamelCaseToSnakeCase($fileNameWithoutExtension);
    }

    /**
     * Extracts the extension key from an EXT: file reference.
     */
    protected function extractExtensionKey(string $filePath): string
    {
        if (!str_starts_with($filePath, 'EXT:')) {
            throw new \InvalidArgumentException('File path must start with "EXT:"', 1729000001);
        }

        $withoutPrefix = substr($filePath, 4);
        $slashPos = strpos($withoutPrefix, '/');
        if ($slashPos === false) {
            throw new \InvalidArgumentException('Invalid EXT: reference format', 1729000002);
        }

        return substr($withoutPrefix, 0, $slashPos);
    }

    protected function getRelativePath(string $file, string $packageKey, PackageInterface $package): string
    {
        if (str_starts_with($file, $package->getPackagePath())) {
            $file = substr($file, strlen($package->getPackagePath()));
            $file = 'EXT:' . $packageKey . '/' . $file;
        } elseif (str_starts_with($file, Environment::getProjectPath())) {
            $file = substr($file, strlen(Environment::getProjectPath()) + 1);
        }
        return $file;
    }

    /**
     * Converts UpperCamelCase to snake_case.
     *
     * Examples: "SudoMode" -> "sudo_mode", "Form" -> "form"
     */
    protected function upperCamelCaseToSnakeCase(string $input): string
    {
        // Insert underscores before uppercase letters (except at start) and convert to lowercase
        $result = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $input);
        return strtolower($result ?? $input);
    }

    /**
     * A valid domain consists of lowercase letters, numbers, underscores only
     * and at least one dot in between.
     */
    public function isValidDomainName(string $domain): bool
    {
        return (bool)preg_match('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $domain);
    }
}
