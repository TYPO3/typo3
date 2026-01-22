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

/**
 * Pure utility class for resolving translation domains from file paths.
 *
 * This class contains stateless methods for:
 * - Converting file paths to translation domains
 * - Validating domain names
 * - Extracting locale prefixes from file names
 * - Stripping file extensions
 *
 * It has no dependencies on other localization classes, making it safe to
 * inject into both LabelFileResolver and TranslationDomainMapper without
 * creating circular dependencies.
 *
 * @internal not part of TYPO3's public API.
 */
readonly class TranslationDomainResolver
{
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
     * A valid domain consists of lowercase letters, numbers, underscores only
     * and at least one dot in between.
     */
    public function isValidDomainName(string $domain): bool
    {
        return (bool)preg_match('/^[a-z0-9_]+(\.[a-z0-9_]+)+$/', $domain);
    }

    /**
     * Strips the file extension from a file reference.
     *
     * Examples:
     * - "EXT:core/Resources/Private/Language/locallang.xlf" -> "EXT:core/Resources/Private/Language/locallang"
     * - "locallang.xlf" -> "locallang"
     */
    public function getFileReferenceWithoutExtension(string $fileReference): string
    {
        return preg_replace('/\\.[a-z0-9]+$/i', '', $fileReference) ?? $fileReference;
    }

    /**
     * Extracts the locale prefix from a language file name.
     *
     * If a file is called "de_AT.locallang.xlf", this method returns "de_AT".
     * If there is no locale prefix, NULL is returned.
     *
     * However, for files like "db.xlf", "db" should not be detected as locale.
     *
     * Examples:
     * - "de.locallang.xlf" -> "de"
     * - "de_AT.locallang.xlf" -> "de_AT"
     * - "fr-CA.messages.xlf" -> "fr-CA"
     * - "locallang.xlf" -> null
     * - "db.xlf" -> null
     */
    public function getLocaleFromLanguageFile(string $fileName): ?string
    {
        if (substr_count($fileName, '.') > 1 && preg_match('/^[a-z]{2}([_-][A-z]{2,3})?\./', $fileName)) {
            return substr($fileName, 0, strpos($fileName, '.'));
        }
        return null;
    }

    /**
     * Transforms a file path to a resource name (for domain).
     *
     * Examples:
     * - "Resources/Private/Language/locallang.xlf" -> "messages"
     * - "Resources/Private/Language/locallang_toolbar.xlf" -> "toolbar"
     * - "Resources/Private/Language/Form/locallang_tabs.xlf" -> "form.tabs"
     * - "Configuration/Sets/Felogin/labels.xlf" -> "sets.felogin"
     * - "EXT:example/ContentBlocks/ContentElements/simple-relation/language/labels.xlf" -> "content_blocks.content_elements.simple_relation.language.labels"
     */
    protected function transformFilePathToResource(string $filePath, string $extensionKey): string
    {
        $isExtensionPath = false;

        // Remove EXT:extensionKey/ prefix
        $prefix = 'EXT:' . $extensionKey . '/';
        if (str_starts_with($filePath, $prefix)) {
            $isExtensionPath = true;
            $filePath = substr($filePath, strlen($prefix));
        }

        // Remove locale prefix if present (e.g., "de.locallang.xlf" -> "locallang.xlf")
        $fileName = basename($filePath);
        $locale = $this->getLocaleFromLanguageFile($fileName);
        if ($locale !== null) {
            $fileName = substr($fileName, strlen($locale) + 1);
            $filePath = dirname($filePath) . '/' . $fileName;
        }

        // Handle site sets: Configuration/Sets/{Name}/labels.xlf -> sets.{name}
        if (preg_match('#Configuration/Sets/([^/]+)/labels\.xlf$#', $filePath, $matches)) {
            $setName = $this->upperCamelCaseToSnakeCase($matches[1]);
            return 'sets.' . $setName;
        }

        // Clean standard language files: Resources/Private/Language/...
        if (str_starts_with($filePath, 'Resources/Private/Language/')) {
            $isExtensionPath = true;
            $filePath = substr($filePath, strlen('Resources/Private/Language/'));
        }

        // Handle extension paths
        if ($isExtensionPath) {
            // Split into directory and filename
            $pathParts = explode('/', $filePath);
            $fileName = array_pop($pathParts);

            // Convert directories from UpperCamelCase to snake_case
            $resourceParts = array_map($this->upperCamelCaseToSnakeCase(...), $pathParts);

            // Transform filename
            $fileNameWithoutExtension = $this->getFileReferenceWithoutExtension($fileName);

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
        $fileNameWithoutExtension = $this->getFileReferenceWithoutExtension(basename($filePath));
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

    /**
     * Converts UpperCamelCase to snake_case.
     *
     * Examples: "SudoMode" -> "sudo_mode", "Form" -> "form"
     */
    protected function upperCamelCaseToSnakeCase(string $input): string
    {
        // Insert underscores before uppercase letters (except at start) and convert to lowercase
        $result = preg_replace('/([a-z0-9])([A-Z])/', '$1_$2', $input);
        $result = strtolower($result ?? $input);
        return str_replace('-', '_', $result);
    }
}
