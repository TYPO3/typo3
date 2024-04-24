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

namespace TYPO3\CMS\Core\Site\Set;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Service\DependencyOrderingService;

#[Autoconfigure(public: true)]
class SetRegistry
{
    /** @var list<SetDefinition>|null */
    protected ?array $orderedSets = null;

    public function __construct(
        protected DependencyOrderingService $dependencyOrderingService,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withPrefix("Sets").toString()')]
        protected readonly string $cacheIdentifier,
        #[Autowire(service: 'cache.core')]
        protected readonly PhpFrontend $cache,
        #[Autowire(lazy: true)]
        protected SetCollector $setCollector,
        protected LoggerInterface $logger,
    ) {}

    /**
     * Retrieve list of ordered sets, matched by
     * $setNames, including their dependencies (recursive)
     *
     * @return list<SetDefinition>
     */
    public function getSets(string ...$setNames): array
    {
        return array_values(array_filter(
            $this->getOrderedSets(),
            fn(SetDefinition $set): bool =>
                in_array($set->name, $setNames, true) ||
                $this->hasDependency($setNames, $set->name)
        ));
    }

    public function hasSet(string $setName): bool
    {
        return isset($this->getOrderedSets()[$setName]);
    }

    public function getSet(string $setName): ?SetDefinition
    {
        return $this->getOrderedSets()[$setName] ?? null;
    }

    /**
     * @return array<string, SetDefinition>
     */
    protected function getOrderedSets(): array
    {
        return $this->orderedSets ?? $this->getFromCache() ?? $this->computeOrderedSets();
    }

    /**
     * @return array<string, SetDefinition>
     */
    protected function getFromCache(): ?array
    {
        if (!$this->cache->has($this->cacheIdentifier)) {
            return null;
        }
        try {
            $orderedSets = $this->cache->require($this->cacheIdentifier);
            if ($orderedSets === false) {
                // Cache entry has been removed in the meantime
                return null;
            }
            if (!is_array($orderedSets)) {
                // An invalid result is to be ignored (cache will be recreated)
                return null;
            }
            $this->orderedSets = $orderedSets;
        } catch (\Error) {
            return null;
        }
        return $this->orderedSets;
    }

    /**
     * @return array<string, SetDefinition>
     */
    protected function computeOrderedSets(): array
    {
        $tmp = [];
        $sets = $this->setCollector->getSetDefinitions();
        foreach ($sets as $set) {
            foreach ($set->dependencies as $dependencyName) {
                if (isset($sets[$dependencyName])) {
                    continue;
                }
                $this->logger->error('Invalid set "{name}": Missing dependency "{dependency}"', [
                    'name' => $set->name,
                    'dependency' => $dependencyName,
                ]);
                continue 2;
            }
            $tmp[$set->name] = [
                'set' => $set,
                'after' => $set->dependencies,
                'after-resilient' => $set->optionalDependencies,
            ];
        }

        $this->orderedSets = array_map(
            static fn(array $data): SetDefinition => $data['set'],
            $this->dependencyOrderingService->orderByDependencies($tmp)
        );
        $this->cache->set($this->cacheIdentifier, 'return ' . var_export($this->orderedSets, true) . ';');
        return $this->orderedSets;
    }

    protected function hasDependency(array $setNames, string $dependency): bool
    {
        foreach ($setNames as $setName) {
            $set = $this->getSet($setName);
            if ($set === null) {
                continue;
            }

            if (in_array($dependency, $set->dependencies, true)) {
                return true;
            }

            if ($this->hasDependency($set->dependencies, $dependency)) {
                return true;
            }
        }
        return false;
    }

    #[AsEventListener('typo3-core/set-registry')]
    public function warmupCaches(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $this->computeOrderedSets();
        }
    }
}
