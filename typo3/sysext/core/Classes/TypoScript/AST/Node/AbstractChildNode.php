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
 * Generic child node. Implements common methods of NodeInterface used
 * in all Node classes.
 *
 * @internal: Internal AST structure.
 */
abstract class AbstractChildNode extends AbstractNode implements ChildNodeInterface
{
    public function __construct(protected string $name) {}

    /**
     * Dereference children on clone().
     * Used with '<' operator to create a deep-copy of the tree to copy.
     */
    public function __clone(): void
    {
        foreach ($this->children as $childName => $child) {
            $this->children[$childName] = clone $child;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toArray(): ?array
    {
        if (!$this->hasChildren()) {
            return null;
        }
        $result = [];
        foreach ($this->getNextChild() as $child) {
            $childName = $child->getName();
            $childValue = $child->getValue();
            if ($child instanceof ReferenceChildNode) {
                // Hack for b/w compat parsing of `=<` operator. See ContentObjectRenderer cObjGetSingle() and mergeTSRef()
                // @todo: adding the whitespace after '<' is another bit of a hack here ... maybe solve in tokenizer?
                //        compare this for what happens when doing 'foo = bar' in old parser: Is the whitespace kept for
                //        value to not trigger the ref lookup to often if doing 'foo = <div...' ?
                // @todo: same situation in RootNode!
                $childValue = '< ' . $child->getReferenceSourceStream();
            }
            if ($childValue !== null) {
                $result[$child->getName()] = $childValue;
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
        $prefixedQuotedNodeName = $prefix . addcslashes($this->getName(), '.');
        if (!$this->isValueNull()) {
            $flatArray[$prefixedQuotedNodeName] = $this->getValue();
        }
        foreach ($this->getNextChild() as $child) {
            $flatArray = array_merge($flatArray, $child->flatten($prefixedQuotedNodeName . '.'));
        }
        return $flatArray;
    }
}
