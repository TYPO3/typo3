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

namespace TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType;

/**
 * Base class for all data types that contains properties
 * common to all data types.
 *
 * @internal
 */
abstract class AbstractDataType
{
    /** Used by most field types for length/precision information */
    protected int $length = 0;
    /** Used for floating point type columns. -1 is used to indicate no value has been set. */
    protected int $precision = -1;
    /** Used for floating point type columns. -1 is used to indicate that no value has been set. */
    protected int $scale = -1;
    /** Differentiate between CHAR/VARCHAR and BINARY/VARBINARY */
    protected bool $fixed = false;
    /** Unsigned flag for numeric columns */
    protected bool $unsigned = false;
    /** Extra options for a column that control specific features/flags */
    protected array $options = [];
    /** Options for ENUM/SET data types */
    protected array $values = [];

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length): void
    {
        $this->length = $length;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision): void
    {
        $this->precision = $precision;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function setScale(int $scale): void
    {
        $this->scale = $scale;
    }

    public function isFixed(): bool
    {
        return $this->fixed;
    }

    public function setFixed(bool $fixed): void
    {
        $this->fixed = $fixed;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function setUnsigned(bool $unsigned): void
    {
        $this->unsigned = $unsigned;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values): void
    {
        $this->values = $values;
    }
}
