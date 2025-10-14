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

namespace TYPO3\CMS\Lowlevel\ConfigurationModuleProvider;

use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Core\Package\PackageManager;

class TranslationDomainListProvider extends AbstractProvider
{
    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly TranslationDomainMapper $translationDomainMapper,
        private readonly LocalizationFactory $localizationFactory,
    ) {}

    public function getConfiguration(): array
    {
        $packages = $this->packageManager->getActivePackages();

        foreach ($packages as $package) {
            $resourcesByLocale = $this->translationDomainMapper->findLabelResourcesInPackageGroupedByLocale($package->getPackageKey());
            // Get English resources (base files)
            $resources = $resourcesByLocale['en'] ?? [];
            foreach ($resources as $domain => $resource) {
                $labelData[] = [
                    'domain' => $domain,
                    'resource' => $resource,
                    'labelCount' => $this->countLabelsInResource($resource),
                ];
            }
        }

        if (empty($labelData)) {
            return [];
        }

        // Sort by domain name
        usort($labelData, fn($a, $b) => strcmp($a['domain'], $b['domain']));

        $configuration = [];
        foreach ($labelData as $data) {
            $configuration[$data['domain']] = [
                'path' => $data['resource'],
                'count' => $data['labelCount'],
            ];
        }
        return $configuration;
    }

    /**
     * Count the number of labels in a label resource file.
     * Uses LocalizationFactory to parse the file and count entries.
     */
    protected function countLabelsInResource(string $fileReference): int
    {
        try {
            $labels = $this->localizationFactory->getParsedData($fileReference, 'en');
            return count($labels);
        } catch (\Exception $e) {
            return 0;
        }
    }
}
