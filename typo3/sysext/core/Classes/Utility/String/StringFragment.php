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
class StringFragment implements \Stringable
{
    public readonly int $length;
    public readonly string $ident;

    public static function raw(string $value): self
    {
        return new self($value, StringFragmentSplitter::TYPE_RAW);
    }

    public static function expression(string $value): self
    {
        return new self($value, StringFragmentSplitter::TYPE_EXPRESSION);
    }

    public function __construct(
        public readonly string $value,
        public readonly string $type
    ) {
        if ($this->value === '') {
            throw new \LogicException('Value must not be empty', 1651671582);
        }
        $this->length = strlen($this->value);
        $this->ident = md5($this->type . '::' . ($this->value));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
