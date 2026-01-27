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

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * @internal
 */
class CacheWarmer
{
    public function __construct(
        protected readonly PackageManager $packageManager,
        protected readonly LabelFileResolver $labelFileResolver,
        protected readonly LocalizationFactory $localizationFactory,
        protected readonly Locales $locales,
    ) {}

    #[AsEventListener]
    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $activeLanguages = $this->locales->getActiveLanguages();
            $locales = [];
            foreach ($activeLanguages as $language) {
                $locales[$language] = $this->locales->createLocale($language);
            }

            // Collect all label files from all packages first to avoid repeated filesystem scans.
            $allBaseLocaleResources = [];
            $packages = $this->packageManager->getActivePackages();
            foreach ($packages as $package) {
                $baseLocaleResources = $this->labelFileResolver->getAllLabelFilesOfPackage($package->getPackageKey(), true)['default'] ?? [];
                foreach ($baseLocaleResources as $fileReference) {
                    $allBaseLocaleResources[] = $fileReference;
                }
            }

            // Phase 1: Add all resources to Symfony Translator without retrieving catalogues.
            // This avoids O(n²) behaviour where each getCatalogue() rebuilds from all previously
            // added resources. By batching all addResource() calls first, catalogue building
            // only happens once per locale in phase 2.
            foreach ($locales as $locale) {
                foreach ($allBaseLocaleResources as $fileReference) {
                    $this->localizationFactory->warmupTranslatorResource($fileReference, $locale);
                }
            }

            // Phase 2: Retrieve catalogues and write to system cache.
            // Resources are already loaded, so this just builds catalogues once per locale.
            foreach ($locales as $locale) {
                foreach ($allBaseLocaleResources as $fileReference) {
                    $this->localizationFactory->getParsedData($fileReference, $locale, true);
                }
            }
        }
    }
}
