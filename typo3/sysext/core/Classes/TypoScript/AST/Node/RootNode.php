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

namespace TYPO3\CMS\Core\TypoScript\AST\Node;

/**
 * AST entry node.
 *
 * @internal: Internal AST structure.
 */
final class RootNode extends AbstractNode
{
    /**
     * Attempting to clone the RootNode indicates a bug in AstBuilder.
     * It should never happen.
     */
    public function __clone(): void
    {
        throw new \LogicException('Can not clone RootNode', 1655988945);
    }

    /**
     * RootNode has no properties to cache, just children.
     */
    protected function serialize(): array
    {
        return [
            'children' => $this->children,
        ];
    }

    public function getName(): ?string
    {
        return null;
    }

    public function updateName(string $name): void
    {
        throw new \RuntimeException('RootNode has no name. Don\'t call updateName().', 1653743453);
    }

    public function setValue(?string $value): void
    {
        throw new \RuntimeException('RootNode has no value. Don\'t call setValue().', 1653743454);
    }

    public function appendValue(string $value): void
    {
        throw new \RuntimeException('RootNode has no value. Don\'t call appendValue().', 1653743455);
    }

    public function getValue(): ?string
    {
        return null;
    }

    public function isValueNull(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = [];
        foreach ($this->getNextChild() as $child) {
            $childName = $child->getName();
            if ($child instanceof ReferenceChildNode) {
                // Hack for b/w compat parsing of `=<` operator. See ContentObjectRenderer cObjGetSingle() and mergeTSRef()
                $childValue = '< ' . $child->getReferenceSourceStream();
            } else {
                $childValue = $child->getValue();
            }
            if ($childValue !== null) {
                $result[$childName] = $childValue;
            }
            $grandChildren = $child->toArray();
            if ($grandChildren !== null) {
                $result[$childName . '.'] = $grandChildren;
            }
        }
        return $result;
    }

    public function flatten(string $prefix = ''): array
    {
        $flatArray = [];
        foreach ($this->getNextChild() as $child) {
            $flatArray = array_merge($flatArray, $child->flatten(''));
        }
        return $flatArray;
    }
}
