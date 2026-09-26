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

use TYPO3\CMS\Form\Domain\Configuration\Exception\PrototypeNotFoundException;

/**
 * Typed registry for the open "prototypes" section of form YAML configuration.
 *
 * @implements \IteratorAggregate<string, PrototypeConfiguration>
 * @internal
 */
final readonly class PrototypeCollection implements \IteratorAggregate
{
    /**
     * @param array<string, PrototypeConfiguration> $prototypes
     */
    private function __construct(
        private array $prototypes,
    ) {}

    /**
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $prototypes = [];
        foreach ($configuration as $identifier => $prototypeConfiguration) {
            if (is_string($identifier) && is_array($prototypeConfiguration)) {
                $prototypes[$identifier] = PrototypeConfiguration::fromArray($prototypeConfiguration);
            }
        }
        return new self($prototypes);
    }

    public function get(string $identifier): ?PrototypeConfiguration
    {
        return $this->prototypes[$identifier] ?? null;
    }

    /**
     * @throws PrototypeNotFoundException
     */
    public function require(string $identifier): PrototypeConfiguration
    {
        return $this->get($identifier) ?? throw new PrototypeNotFoundException(
            sprintf('The Prototype "%s" was not found.', $identifier),
            1475924277,
        );
    }

    /**
     * @return \Traversable<string, PrototypeConfiguration>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->prototypes;
    }
}
