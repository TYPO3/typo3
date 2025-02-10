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

use TYPO3\CMS\Core\DataHandling\PageDoktypeRegistry;

class PageDoktypeProvider extends AbstractProvider
{
    public function __construct(
        protected readonly PageDoktypeRegistry $doktypeRegistry,
    ) {}

    public function getConfiguration(): array
    {
        $configuration = [];
        $allLabels = $this->prepareLabelsForAllTypes();
        $providerConfiguration = $this->doktypeRegistry->exportConfiguration();
        ksort($providerConfiguration);
        foreach ($providerConfiguration as $pageType => $typeConfiguration) {
            if (isset($allLabels[$pageType])) {
                $typeConfiguration['name'] = $allLabels[$pageType];
            }
            $configuration[$pageType] = $typeConfiguration;
        }
        return $configuration;
    }

    protected function prepareLabelsForAllTypes(): array
    {
        $types = [];
        $languageService = $this->getLanguageService();
        foreach ($this->doktypeRegistry->getAllDoktypes() as $item) {
            if (!$item->isDivider()) {
                $types[(int)$item->getValue()] = $languageService->sL($item->getLabel());
            }
        }
        return $types;
    }
}
