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

namespace TYPO3\CMS\Core\MetaTag;

/**
 * Holds all available meta tag managers, which are registered as tagged
 * services via the #[AsMetaTagManager] attribute or the 'metatag.manager'
 * service tag. Managers are ordered by their "before" and "after"
 * constraints at container compile time.
 */
class MetaTagManagerRegistry
{
    /**
     * @var MetaTagManagerInterface[]|null keyed by manager identifier
     */
    private ?array $managers = null;

    /**
     * @param iterable<string, MetaTagManagerInterface> $registeredManagers keyed by manager identifier
     */
    public function __construct(protected readonly iterable $registeredManagers = []) {}

    /**
     * @deprecated since TYPO3 v15.0, this method is a no-op and will be removed in TYPO3 v16.0. Register the meta tag manager as a tagged service using the #[AsMetaTagManager] attribute instead.
     */
    public function registerManager(string $name, string $className, array $before = ['generic'], array $after = []): void {}

    /**
     * Get the MetaTagManager for a specific property
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
        return new GenericMetaTagManager();
    }

    /**
     * Get an array of all registered MetaTagManagers, keyed by their identifier
     *
     * @return MetaTagManagerInterface[]
     */
    public function getAllManagers(): array
    {
        if ($this->managers === null) {
            $this->managers = [];
            foreach ($this->registeredManagers as $identifier => $manager) {
                $this->managers[$identifier] = $manager;
            }
        }
        return $this->managers;
    }

    /**
     * @internal
     */
    public function updateState(array $state): void
    {
        foreach ($this->getInstancesPerClass() as $className => $instance) {
            $instance->updateState($state['instances'][$className] ?? []);
        }
    }

    /**
     * @internal
     */
    public function getState(): array
    {
        return [
            'instances' => array_map(
                static fn(MetaTagManagerInterface $instance): array => $instance->getState(),
                $this->getInstancesPerClass(),
            ),
        ];
    }

    /**
     * @return MetaTagManagerInterface[] keyed by class name, as the same instance
     *                                   may be registered under multiple identifiers
     */
    private function getInstancesPerClass(): array
    {
        $instances = [];
        foreach ($this->getAllManagers() as $manager) {
            $instances[$manager::class] = $manager;
        }
        return $instances;
    }
}
