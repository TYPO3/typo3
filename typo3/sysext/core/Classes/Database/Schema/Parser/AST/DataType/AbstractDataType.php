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
 */
abstract class AbstractDataType
{
    /**
     * Used by most field types for length/precision information
     *
     * @var int
     */
    protected $length = 0;

    /**
     * Used for floating point type columns
     * -1 is used to indicate that no value has been set.
     *
     * @var int
     */
    protected $precision = -1;

    /**
     * Used for floating point type columns
     * -1 is used to indicate that no value has been set.
     *
     * @var int
     */
    protected $scale = -1;

    /**
     * Differentiate between CHAR/VARCHAR and BINARY/VARBINARY
     *
     * @var bool
     */
    protected $fixed = false;

    /**
     * Unsigned flag for numeric columns
     *
     * @var bool
     */
    protected $unsigned = false;

    /**
     * Extra options for a column that control specific features/flags
     *
     * @var array
     */
    protected $options = [];

    /**
     * Options for ENUM/SET data types
     *
     * @var array
     */
    protected $values;

    public function getLength(): int
    {
        return $this->length;
    }

    public function setLength(int $length)
    {
        $this->length = $length;
    }

    public function getPrecision(): int
    {
        return $this->precision;
    }

    public function setPrecision(int $precision)
    {
        $this->precision = $precision;
    }

    public function getScale(): int
    {
        return $this->scale;
    }

    public function setScale(int $scale)
    {
        $this->scale = $scale;
    }

    public function isFixed(): bool
    {
        return $this->fixed;
    }

    public function setFixed(bool $fixed)
    {
        $this->fixed = $fixed;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    public function setUnsigned(bool $unsigned)
    {
        $this->unsigned = $unsigned;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function setValues(array $values)
    {
        $this->values = $values;
    }
}
