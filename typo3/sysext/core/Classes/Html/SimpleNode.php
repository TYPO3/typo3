<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Core\Html;

/**
 * @internal
 */
class SimpleNode
{
    // similar to https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
    public const TYPE_ELEMENT = 1;
    public const TYPE_TEXT = 3;
    public const TYPE_CDATA = 4;
    public const TYPE_COMMENT = 8;

    /**
     * @var int
     */
    protected $type;

    /**
     * @var int
     */
    protected $index;

    /**
     * @var string
     */
    protected $string;

    public static function fromString(int $type, int $index, string $string): self
    {
        return new self($type, $index, $string);
    }

    public function __construct(int $type, int $index, string $string)
    {
        $this->type = $type;
        $this->index = $index;
        $this->string = $string;
    }

    public function __toString(): string
    {
        return $this->string;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    public function getElementName(): ?string
    {
        if ($this->getType() !== self::TYPE_ELEMENT) {
            return null;
        }
        if (!preg_match('#^<(?P<name>[a-z][a-z0-9-]*)\b#i', $this->string, $matches)) {
            return null;
        }
        return $matches['name'];
    }
}
