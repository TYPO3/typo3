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

namespace TYPO3\CMS\Lowlevel\Localization;

use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Lowlevel\Localization\Dto\DomainSearchResult;
use TYPO3\CMS\Lowlevel\Localization\Dto\LabelSearchResult;

final readonly class LabelFinder
{
    public function __construct(
        private LocalizationFactory $localizationFactory,
        private TranslationDomainMapper $translationDomainMapper,
    ) {}

    /**
     * Finds translation labels in the given packages filtered by locale, search string, and/or regex.
     *
     * If both $searchString and $regex are given, the regex is applied only to labels or references
     * that already contain the search string.
     * @returns DomainSearchResult[]|LabelSearchResult[]
     */
    public function findLabels(array $packages, string $locale = 'en', string $searchString = '', string $regex = '', bool $searchInIdentifierOnly = false, int $limit = 0, bool $flatList = false): array
    {
        // Collect all language resource files with their translations and label counts
        $labelInDomain = [];
        $totalLabelData = [];
        $count = 0;
        foreach ($packages as $package) {
            $resources = [];
            $resourcesByLocale = $this->translationDomainMapper->findLabelResourcesInPackageGroupedByLocale($package->getPackageKey());
            if ($locale !== 'default') {
                // Merge default base resources on a requested non-english locale, otherwise we would only access a subset of labels.
                $resources = array_merge($resources, $resourcesByLocale['default'] ?? []);
            }

            // Merge the specific locale resources
            $resources = array_merge($resources, $resourcesByLocale[$locale] ?? []);
            foreach ($resources as $domain => $resource) {
                $labels = $this->localizationFactory->getParsedData($resource, $locale);
                $labelData = [];
                foreach ($labels as $reference => $label) {
                    if (is_array($label)) {
                        // This can happen with plural forms
                        $label = json_encode($label);
                    }
                    if (!$this->matchesSearchCriteria((string)$reference, $label, $searchString, $regex, $searchInIdentifierOnly)) {
                        continue;
                    }
                    $totalLabelData[] = $labelData[] = new LabelSearchResult((string)$domain, (string)$reference, (string)$label);
                    $count++;
                    if ($limit > 0 && $count >= $limit) {
                        break;
                    }
                }
                if ($labelData !== []) {
                    $labelInDomain[] = new DomainSearchResult((string)$domain, (string)$resource, $labelData);
                }
                if ($limit > 0 && $count >= $limit) {
                    break 2;
                }
            }
        }
        if ($flatList) {
            return $totalLabelData;
        }
        return $labelInDomain;
    }

    private function matchesSearchCriteria(
        string $reference,
        string $label,
        string $searchString,
        string $regex,
        bool $searchInIdentifierOnly
    ): bool {
        $referenceStringMatch = $searchString === '' || str_contains($reference, $searchString);
        $referenceRegexMatch = $regex === '' || preg_match($regex, $reference) === 1;

        if ($searchInIdentifierOnly) {
            return $referenceStringMatch && $referenceRegexMatch;
        }

        $labelStringMatch = $searchString === '' || str_contains($label, $searchString);
        $labelRegexMatch = $regex === '' || preg_match($regex, $label) === 1;

        return ($referenceStringMatch || $labelStringMatch)
            && ($referenceRegexMatch || $labelRegexMatch);
    }

}
