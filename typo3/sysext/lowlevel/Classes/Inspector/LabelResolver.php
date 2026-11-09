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

namespace TYPO3\CMS\Lowlevel\Inspector;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Core\Localization\TranslationDomainResolver;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Replaces label references in normalized data by their translation.
 *
 * Whole values are resolved if they start with "LLL:" or are a bare "domain:key" of a
 * registered translation domain. Inside longer strings only "LLL:" references are
 * resolved. A reference without translation stays unchanged. Keys are never touched.
 *
 * @internal
 */
final readonly class LabelResolver
{
    private const string EMBEDDED_REFERENCE = '/LLL:(?:EXT:[^\s:,;]+|[a-z0-9_]+(?:\.[a-z0-9_]+)+):[^\s,;]+/';
    private const string BARE_REFERENCE = '/^([a-z0-9_]+(?:\.[a-z0-9_]+)+):[^\s:,;]+$/';

    public function __construct(
        private TranslationDomainMapper $translationDomainMapper,
        private TranslationDomainResolver $translationDomainResolver,
        private PackageManager $packageManager,
    ) {}

    public function resolve(mixed $data, LanguageService $languageService): mixed
    {
        if (is_array($data)) {
            return array_map(fn(mixed $item): mixed => $this->resolve($item, $languageService), $data);
        }
        if (!is_string($data)) {
            return $data;
        }
        $text = $this->resolveWholeValue($data, $languageService)
            ?? $this->resolveEmbeddedReferences($data, $languageService);
        return $text !== null && $text !== $data ? new ResolvedLabel($text, $data) : $data;
    }

    private function resolveWholeValue(string $value, LanguageService $languageService): ?string
    {
        $value = trim($value);
        if (str_starts_with($value, 'LLL:')) {
            return $this->translate($value, $languageService);
        }
        if (preg_match(self::BARE_REFERENCE, $value, $matches) && $this->isRegisteredDomain($matches[1])) {
            return $this->translate($value, $languageService);
        }
        return null;
    }

    private function resolveEmbeddedReferences(string $value, LanguageService $languageService): ?string
    {
        if (!str_contains($value, 'LLL:')) {
            return null;
        }
        return preg_replace_callback(
            self::EMBEDDED_REFERENCE,
            fn(array $matches): string => $this->translate($matches[0], $languageService) ?? $matches[0],
            $value
        );
    }

    private function translate(string $reference, LanguageService $languageService): ?string
    {
        $label = $languageService->label($reference);
        return $label === null ? null : (string)$label;
    }

    private function isRegisteredDomain(string $domain): bool
    {
        if (!$this->translationDomainResolver->isValidDomainName($domain)) {
            return false;
        }
        $extensionKey = explode('.', $domain, 2)[0];
        return $this->packageManager->isPackageActive($extensionKey)
            && isset($this->translationDomainMapper->findLabelResourcesInPackage($extensionKey)[$domain]);
    }
}
