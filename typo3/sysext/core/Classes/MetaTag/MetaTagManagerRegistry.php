<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\MetaTag;

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

use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Holds all available meta tag managers
 */
class MetaTagManagerRegistry implements SingletonInterface
{
    protected $registry = [];

    public function __construct()
    {
        $this->registry['generic'] = [
            'module' => GenericMetaTagManager::class
        ];
    }

    /**
     * Add a MetaTagManager to the registry
     *
     * @param string $name
     * @param string $className
     * @param array $before
     * @param array $after
     */
    public function registerManager(string $name, string $className, array $before = ['generic'], array $after = [])
    {
        if (!count($before)) {
            $before[] = 'generic';
        }

        $this->registry[$name] = [
            'module' => $className,
            'before' => $before,
            'after' => $after
        ];
    }

    /**
     * Get the MetaTagManager for a specific property
     *
     * @param string $property
     * @return MetaTagManagerInterface
     */
    public function getManagerForProperty(string $property): MetaTagManagerInterface
    {
        $property = strtolower($property);
        foreach ($this->getAllManagers() as $manager) {
            if ($manager->canHandleProperty($property)) {
                return $manager;
            }
        }

        // Just a fallback because the GenericMetaTagManager is also registered in the list of MetaTagManagers
        return GeneralUtility::makeInstance(GenericMetaTagManager::class);
    }

    /**
     * Get an array of all registered MetaTagManagers
     *
     * @return MetaTagManagerInterface[]
     */
    public function getAllManagers(): array
    {
        $orderedManagers = GeneralUtility::makeInstance(DependencyOrderingService::class)->orderByDependencies(
            $this->registry
        );

        $managers = [];
        foreach ($orderedManagers as $manager => $managerConfiguration) {
            if (class_exists($managerConfiguration['module'])) {
                $managers[$manager] = GeneralUtility::makeInstance($managerConfiguration['module']);
            }
        }

        return $managers;
    }

    /**
     * Remove all registered MetaTagManagers
     */
    public function removeAllManagers()
    {
        unset($this->registry);
    }
}
