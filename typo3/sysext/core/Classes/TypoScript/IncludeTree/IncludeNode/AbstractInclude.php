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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;

/**
 * Base implementation of IncludeInterface.
 *
 * @internal: Internal tree structure.
 */
abstract class AbstractInclude implements IncludeInterface
{
    private ?string $identifier = null;
    protected string $name = '';
    protected string $path = '';

    /**
     * @var array<int, IncludeInterface>
     */
    protected array $children = [];
    protected ?LineStream $lineStream = null;
    protected ?LineInterface $originalTokenLine = null;
    protected bool $isSplit = false;
    protected bool $root = false;
    protected bool $clear = false;
    protected ?int $pid = null;

    /**
     * When storing to cache, we only store FE relevant properties and skip
     * things like "name", "identifier" and friends. We also don't need the
     * LineStream when a node is split.
     */
    final public function __serialize(): array
    {
        return $this->serialize();
    }

    protected function serialize(): array
    {
        $result['children'] = $this->children;
        if ($this->isSplit()) {
            $result['isSplit'] = true;
        }
        if (!$this->isSplit()) {
            $result['lineStream'] = $this->lineStream;
        }
        if ($this->isRoot()) {
            $result['root'] = true;
        }
        if ($this->isClear()) {
            $result['clear'] = true;
        }
        return $result;
    }

    public function getType(): string
    {
        $classWithNamespace = static::class;
        $lastBackslash = strrpos($classWithNamespace, '\\');
        return substr($classWithNamespace, $lastBackslash + 1, -7);
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = hash('xxh3', $identifier);
        $childCounter = 0;
        foreach ($this->getNextChild() as $child) {
            $child->setIdentifier($this->identifier . $childCounter);
            $childCounter++;
        }
    }

    public function getIdentifier(): string
    {
        if ($this->identifier === null) {
            throw new \RuntimeException(
                'Identifier has not been initialized. This happens when getIdentifier() is called on'
                . ' trees retrieved from cache. The identifier is not supposed to be used in this context.',
                1673634853
            );
        }
        return $this->identifier;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function addChild(IncludeInterface $node): void
    {
        $this->children[] = $node;
    }

    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    public function getNextChild(): iterable
    {
        foreach ($this->children as $child) {
            yield $child;
        }
    }

    public function isSysTemplateRecord(): bool
    {
        return false;
    }

    public function setLineStream(?LineStream $lineStream): void
    {
        $this->lineStream = $lineStream;
    }

    public function getLineStream(): ?LineStream
    {
        return $this->lineStream;
    }

    public function setOriginalLine(LineInterface $line): void
    {
        $this->originalTokenLine = $line;
    }

    public function getOriginalLine(): ?LineInterface
    {
        return $this->originalTokenLine;
    }

    public function setSplit(): void
    {
        $this->isSplit = true;
    }

    public function isSplit(): bool
    {
        return $this->isSplit;
    }

    public function setRoot(bool $root): void
    {
        $this->root = $root;
    }

    public function isRoot(): bool
    {
        return $this->root;
    }

    public function setClear(bool $clear): void
    {
        $this->clear = $clear;
    }

    public function isClear(): bool
    {
        return $this->clear;
    }

    public function setPid(int $pid): void
    {
        $this->pid = $pid;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }
}
