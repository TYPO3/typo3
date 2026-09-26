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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration\Prototype;

use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\RawConfigurationTrait;

/**
 * Identified, open configuration definition from a prototype registry.
 *
 * @internal
 */
final readonly class DefinitionConfiguration
{
    use RawConfigurationTrait;

    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public string $identifier,
        public array $raw,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(string $identifier, array $configuration): self
    {
        return new self($identifier, $configuration);
    }
}
