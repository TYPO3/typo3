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

namespace TYPO3\CMS\Core\TypoScript\AST\CurrentObjectPath;

use TYPO3\CMS\Core\TypoScript\AST\Node\NodeInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;

/**
 * Internal state class to track the current hierarchy in tree.
 * This is important in combination with block open "{" and block
 * close "}" brackets.
 * Also used in BE Template Object Browser tree rendering.
 *
 * @internal: Internal AST structure.
 */
final class CurrentObjectPath
{
    /**
     * @var NodeInterface[]
     */
    private array $path;

    public function __construct(NodeInterface ...$path)
    {
        $this->path = $path;
    }

    public function append(NodeInterface $node): void
    {
        $this->path[] = $node;
    }

    /**
     * @return NodeInterface[]
     */
    public function getAll(): array
    {
        return $this->path;
    }

    /**
     * Turn current object path into a string. Quote dots in keys.
     * Used in BE Template Object Browser tree, expand and search handling.
     * Not implementing __toString() here since Fluid can't call this.
     *
     * Example:
     * page.10.foo\.bar.baz
     */
    public function getPathAsString(): string
    {
        $flatArray = [];
        foreach ($this->getAll() as $pathNode) {
            if ($pathNode instanceof RootNode) {
                continue;
            }
            $name = $pathNode->getName();
            if ($name === '') {
                throw new \RuntimeException('Node names must not be empty string', 1658578645);
            }
            $flatArray[] = addcslashes($name, '.');
        }
        return implode('.', $flatArray);
    }

    public function getFirst(): NodeInterface
    {
        return reset($this->path);
    }

    public function getLast(): NodeInterface
    {
        return $this->path[array_key_last($this->path)];
    }

    public function getSecondLast(): NodeInterface
    {
        return array_slice($this->path, -2, 1)[0];
    }

    public function removeLast(): void
    {
        array_pop($this->path);
    }
}
