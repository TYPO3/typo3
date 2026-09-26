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
 * Ordered collection of open form editor definitions.
 *
 * @implements \IteratorAggregate<int|string, EditorDefinition>
 * @internal
 */
final readonly class EditorDefinitionCollection implements \IteratorAggregate
{
    use RawConfigurationTrait;

    /**
     * @param array<int|string, EditorDefinition> $definitions
     * @param array<int|string, mixed> $raw
     */
    private function __construct(private array $definitions, public array $raw) {}

    /**
     * @param array<int|string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        $definitions = [];
        foreach ($configuration as $key => $editorConfiguration) {
            if (is_array($editorConfiguration)) {
                $definitions[$key] = EditorDefinition::fromArray($editorConfiguration);
            }
        }
        return new self($definitions, $configuration);
    }

    /**
     * @return \Traversable<int|string, EditorDefinition>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->definitions;
    }

    /**
     * @return list<string>
     */
    public function getPropertyPathsForTemplate(string $templateName): array
    {
        $propertyPaths = [];
        foreach ($this->definitions as $definition) {
            if ($definition->templateName === $templateName && $definition->propertyPath !== null) {
                $propertyPaths[] = $definition->propertyPath;
            }
        }
        return $propertyPaths;
    }
}
