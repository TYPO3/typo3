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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Lowlevel\Event\ModifyBlindedConfigurationOptionsEvent;

class SitesYamlConfigurationProvider extends AbstractProvider
{
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SiteFinder $siteFinder
    ) {}

    public function getConfiguration(): array
    {
        $blindedConfigurationOptions = $this
            ->eventDispatcher
            ->dispatch(new ModifyBlindedConfigurationOptionsEvent([], $this->identifier))
            ->getBlindedConfigurationOptions();

        $configurationArray = [];
        foreach ($this->siteFinder->getAllSites() as $identifier => $site) {
            $configurationArray[$identifier] = $site->getConfiguration();
            if (!isset($blindedConfigurationOptions[$identifier])) {
                continue;
            }
            ArrayUtility::mergeRecursiveWithOverrule(
                $configurationArray[$identifier],
                ArrayUtility::intersectRecursive($blindedConfigurationOptions[$identifier], $configurationArray[$identifier])
            );
        }

        ArrayUtility::naturalKeySortRecursive($configurationArray);
        return $configurationArray;
    }
}
