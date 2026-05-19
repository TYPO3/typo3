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

namespace TYPO3\CMS\Form\Event;

/**
 * Listeners to this event are able to modify the form definition validation
 * configuration after it has been built from the form editor setup. Use this
 * event to add additional writable property paths for custom form editor
 * inspector editor implementations that do not declare their writable
 * property paths through the standard YAML configuration (e.g. propertyPath).
 */
final class AfterFormDefinitionValidationConfigurationIsBuiltEvent
{
    public function __construct(
        private readonly string $prototypeName,
        private array $configuration,
    ) {}

    public function getPrototypeName(): string
    {
        return $this->prototypeName;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }
}
