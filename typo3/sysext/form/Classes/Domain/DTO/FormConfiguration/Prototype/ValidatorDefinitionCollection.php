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
 * Typed registry for the open "validatorsDefinition" prototype section.
 *
 * @implements \IteratorAggregate<string, ValidatorDefinition>
 * @internal
 */
final readonly class ValidatorDefinitionCollection implements \IteratorAggregate
{
    use RawConfigurationTrait;

    /**
     * @param array<string, ValidatorDefinition> $definitions
     * @param array<int|string, mixed> $raw
     */
    private function __construct(private array $definitions, public array $raw) {}

    /**
     * @param array<int|string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $definitions = [];
        foreach ($configuration as $identifier => $definitionConfiguration) {
            if (is_string($identifier) && is_array($definitionConfiguration)) {
                $definitions[$identifier] = ValidatorDefinition::fromArray($identifier, $definitionConfiguration);
            }
        }
        return new self($definitions, $configuration);
    }

    public function get(string $identifier): ?ValidatorDefinition
    {
        return $this->definitions[$identifier] ?? null;
    }

    /**
     * @return \Traversable<string, ValidatorDefinition>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->definitions;
    }
}
