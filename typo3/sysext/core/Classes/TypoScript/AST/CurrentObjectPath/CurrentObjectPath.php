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

/**
 * Internal state class to track the current hierarchy in tree.
 * This is important in combination with block open "{" and block
 * close "}" brackets.
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
