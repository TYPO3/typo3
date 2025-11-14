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

namespace TYPO3\CMS\Core\Upgrades\RowUpdater;

use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Attribute\AsRowUpdater;

/**
 * Registry for row updaters. The registry receives all services, tagged with {}
 * The tagging of row updaters is automatically done based on the PHP Attribute
 *
 * @internal for `EXT:core` internal use and not part of public API.
 */
final class RowUpdaterRegistry
{
    public function __construct(
        #[AutowireLocator(services: AsRowUpdater::TAG_NAME, indexAttribute: 'identifier')]
        private ServiceLocator $rowUpdaters,
    ) {}

    /**
     * Verify that a registered row updater exists for the given identifier.
     */
    public function hasRowUpdater(string $identifier): bool
    {
        return $this->rowUpdaters->has($identifier);
    }

    /**
     * Get registered row updater by identifier.
     */
    public function getRowUpdater(string $identifier): RowUpdaterInterface
    {
        if (!$this->hasRowUpdater($identifier)) {
            throw new \UnexpectedValueException(
                sprintf(
                    'RowUpdater with identifier "%s" is not registered.',
                    $identifier,
                ),
                1763107214,
            );
        }
        return $this->rowUpdaters->get($identifier);
    }

    /**
     * @return array<string, class-string>
     */
    public function getRowUpdaters(): array
    {
        return $this->rowUpdaters->getProvidedServices();
    }

    /**
     * @return string[]
     */
    public function getRowUpdaterIdentifiers(): array
    {
        return array_keys($this->getRowUpdaters());
    }
}
