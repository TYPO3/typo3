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

namespace TYPO3\CMS\Form\Mvc\Persistence\Event;

/**
 * Listeners are able to modify the loaded form definition
 */
final class AfterFormDefinitionLoadedEvent
{
    public function __construct(
        private array $formDefinition,
        private readonly string $persistenceIdentifier,
        private readonly string $cacheKey
    ) {}

    public function getFormDefinition(): array
    {
        return $this->formDefinition;
    }

    public function setFormDefinition(array $formDefinition): void
    {
        $this->formDefinition = $formDefinition;
    }

    public function getPersistenceIdentifier(): string
    {
        return $this->persistenceIdentifier;
    }

    public function getCacheKey(): string
    {
        return $this->cacheKey;
    }
}
