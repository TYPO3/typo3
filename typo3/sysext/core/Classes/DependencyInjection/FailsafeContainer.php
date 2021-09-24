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

namespace TYPO3\CMS\Core\DependencyInjection;

use Psr\Container\ContainerInterface;

/**
 * @internal
 */
class FailsafeContainer implements ContainerInterface
{
    /**
     * @var array
     */
    private $entries = [];

    /**
     * @var array
     */
    private $factories = [];

    /**
     * Instantiate the container.
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param iterable $providers The service providers to register.
     * @param array $entries The default parameters or objects.
     */
    public function __construct(iterable $providers = [], array $entries = [])
    {
        $this->entries = $entries;

        $factories = [];
        foreach ($providers as $provider) {
            /** @var ServiceProviderInterface $provider */
            $factories = $provider->getFactories() + $factories;
            foreach ($provider->getExtensions() as $id => $extension) {
                // Decorate a previously defined extension or if that is not available,
                // create a lazy lookup to a factory from the list of vanilla factories.
                // Lazy because we currently can not know whether a factory will only
                // become available due to a subsequent provider.
                $innerFactory = $this->factories[$id] ?? static function (ContainerInterface $c) use (&$factories, $id) {
                    return isset($factories[$id]) ? $factories[$id]($c) : null;
                };

                $this->factories[$id] = static function (ContainerInterface $container) use ($extension, $innerFactory) {
                    $previous = $innerFactory($container);
                    return $extension($container, $previous);
                };
            }
        }

        // Add factories to the list of factories for services that were not extended.
        // (i.e those that have not been specified in getExtensions)
        $this->factories += $factories;
    }

    /**
     * @param string $id
     * @return bool
     */
    public function has(string $id): bool
    {
        return array_key_exists($id, $this->entries) || array_key_exists($id, $this->factories);
    }

    /**
     * @param string $id
     * @return mixed
     */
    private function create(string $id)
    {
        $factory = $this->factories[$id] ?? null;

        if ((bool)$factory) {
            // Remove factory as it is no longer required.
            // Set factory to false to be able to detect
            // cyclic dependency loops.
            $this->factories[$id] = false;

            return $this->entries[$id] = $factory($this);
        }
        if (array_key_exists($id, $this->entries)) {
            // This condition is triggered in the unlikely case that the entry is null
            // Note: That is because the coalesce operator used in get() can not handle that
            return $this->entries[$id];
        }
        if ($factory === null) {
            throw new NotFoundException('Container entry "' . $id . '" is not available.', 1519978105);
        }
        // if ($factory === false)
        throw new ContainerException('Container entry "' . $id . '" is part of a cyclic dependency chain.', 1520175002);
    }

    /**
     * @param string $id
     * @return mixed
     */
    public function get(string $id)
    {
        return $this->entries[$id] ?? $this->create($id);
    }
}
