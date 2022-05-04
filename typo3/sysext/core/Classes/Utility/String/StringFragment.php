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

namespace TYPO3\CMS\Core\Utility\String;

/**
 * @internal
 */
class StringFragment
{
    protected string $value;
    protected string $type;
    protected int $length;
    protected string $ident;

    public static function raw(string $value): self
    {
        return new self($value, StringFragmentSplitter::TYPE_RAW);
    }

    public static function expression(string $value): self
    {
        return new self($value, StringFragmentSplitter::TYPE_EXPRESSION);
    }

    public function __construct(string $value, string $type)
    {
        if ($value === '') {
            throw new \LogicException('Value must not be empty', 1651671582);
        }
        $this->value = $value;
        $this->type = $type;
        $this->length = strlen($value);
        $this->ident = md5($type . '::' . ($value));
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getIdent(): string
    {
        return $this->ident;
    }
}
