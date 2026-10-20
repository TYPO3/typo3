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

use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteFinder;

class SiteSetsProvider extends AbstractProvider
{
    public function __construct(
        protected readonly SiteFinder $siteFinder,
        protected readonly SetRegistry $setRegistry,
    ) {}

    public function getConfiguration(): array
    {
        $configuration = [
            'sites' => [],
            'siteSets' => [],
        ];
        foreach ($this->setRegistry->getAllSets() as $set) {
            $configuration['siteSets'][$set->name] = $this->getSetConfiguration($set);
        }
        foreach ($this->siteFinder->getAllSites() as $identifier => $site) {
            $configuration['sites'][$identifier] = [];
            foreach ($this->setRegistry->getSets(...$site->getSets()) as $set) {
                $configuration['sites'][$identifier][$set->name] = $configuration['siteSets'][$set->name];
            }
        }
        return $configuration;
    }

    private function getSetConfiguration(SetDefinition $set): array
    {
        $configuration = $set->toArray();
        foreach (['dependencies' => $set->dependencies, 'optionalDependencies' => $set->optionalDependencies] as $key => $dependencies) {
            if ($dependencies === []) {
                continue;
            }
            $configuration[$key] = [];
            foreach ($dependencies as $name) {
                $dependency = $this->setRegistry->getSet($name);
                $configuration[$key][$name] = $dependency !== null ? $this->getSetConfiguration($dependency) : [];
            }
        }
        return $configuration;
    }
}
