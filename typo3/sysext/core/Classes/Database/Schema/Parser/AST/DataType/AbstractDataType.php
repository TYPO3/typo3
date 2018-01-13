<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Database\Schema\Parser\AST\DataType;

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

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length)
    {
        $this->length = $length;
    }

    /**
     * @return int
     */
    public function getPrecision(): int
    {
        return $this->precision;
    }

    /**
     * @param int $precision
     */
    public function setPrecision(int $precision)
    {
        $this->precision = $precision;
    }

    /**
     * @return int
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     */
    public function setScale(int $scale)
    {
        $this->scale = $scale;
    }

    /**
     * @return bool
     */
    public function isFixed(): bool
    {
        return $this->fixed;
    }

    /**
     * @param bool $fixed
     */
    public function setFixed(bool $fixed)
    {
        $this->fixed = $fixed;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->unsigned;
    }

    /**
     * @param bool $unsigned
     */
    public function setUnsigned(bool $unsigned)
    {
        $this->unsigned = $unsigned;
    }

    /**
     * @return array
     */
    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @param array $values
     */
    public function setValues(array $values)
    {
        $this->values = $values;
    }
}
