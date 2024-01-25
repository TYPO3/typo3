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

namespace TYPO3\CMS\Core\Package\Event;

use Psr\Container\ContainerInterface;
use TYPO3\CMS\Core\Package\Exception\InvalidPackageInitializationResultIdentifierException;
use TYPO3\CMS\Core\Package\PackageInitializationResult;
use TYPO3\CMS\Core\Package\PackageInterface;

/**
 * Event that is triggered after a package has been activated (or required in composer
 * mode), allowing listeners to execute initialization tasks, such as importing static data.
 */
final class PackageInitializationEvent
{
    /**
     * @param PackageInitializationResult[] $storage
     */
    public function __construct(
        private readonly string $extensionKey,
        private readonly PackageInterface $package,
        private readonly ?ContainerInterface $container = null,
        private readonly ?object $emitter = null,
        private array $storage = [],
    ) {}

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function getPackage(): PackageInterface
    {
        return $this->package;
    }

    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    public function getEmitter(): ?object
    {
        return $this->emitter;
    }

    public function hasStorageEntry(string $identifier): bool
    {
        return isset($this->storage[$identifier]);
    }

    public function getStorageEntry(string $identifier): PackageInitializationResult
    {
        if (!$this->hasStorageEntry($identifier)) {
            throw new InvalidPackageInitializationResultIdentifierException('No package initialization result entry exists for ' . $identifier, 1706203511);
        }

        return $this->storage[$identifier];
    }

    public function addStorageEntry(string $identifier, mixed $data): void
    {
        $this->storage[$identifier] = new PackageInitializationResult($identifier, $data);
    }

    public function removeStorageEntry(string $identifier): void
    {
        unset($this->storage[$identifier]);
    }
}
