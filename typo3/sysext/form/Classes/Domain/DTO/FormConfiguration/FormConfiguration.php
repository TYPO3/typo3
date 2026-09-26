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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration;

use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype\PrototypeCollection;

/**
 * Typed root representation of the form YAML configuration.
 *
 * @internal
 */
final readonly class FormConfiguration
{
    public function __construct(
        public PersistenceManagerConfiguration $persistenceManager,
        public FormManagerConfiguration $formManager,
        public PrototypeCollection $prototypes,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            persistenceManager: PersistenceManagerConfiguration::fromArray(
                is_array($configuration['persistenceManager'] ?? null) ? $configuration['persistenceManager'] : []
            ),
            formManager: FormManagerConfiguration::fromArray(
                is_array($configuration['formManager'] ?? null) ? $configuration['formManager'] : []
            ),
            prototypes: PrototypeCollection::fromArray(
                is_array($configuration['prototypes'] ?? null) ? $configuration['prototypes'] : []
            ),
        );
    }
}
