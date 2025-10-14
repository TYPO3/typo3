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
        protected readonly LocalizationFactory $localizationFactory
    ) {}

    #[AsEventListener]
    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $packages = $this->packageManager->getActivePackages();
            foreach ($packages as $package) {
                $resources = $this->labelFileResolver->getAllLabelFilesOfPackage($package->getPackageKey());
                foreach ($resources as $language => $filesForLocale) {
                    // @todo: Force cache renewal
                    foreach ($filesForLocale as $fileReference) {
                        $this->localizationFactory->getParsedData($fileReference, $language);
                    }
                }
            }
        }
    }
}
