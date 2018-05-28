<?php
namespace TYPO3\CMS\Core\Type;

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
 * Abstract class for Enumeration.
 * Inspired by SplEnum.
 *
 * The prefix "Abstract" has been left out by intention because
 * a "type" is abstract by definition.
 */
abstract class Enumeration implements TypeInterface
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var array
     */
    protected static $enumConstants;

    /**
     * @param mixed $value
     * @throws Exception\InvalidEnumerationValueException
     */
    public function __construct($value = null)
    {
        if ($value === null && !defined('static::__default')) {
            throw new Exception\InvalidEnumerationValueException(
                sprintf('A value for enumeration "%s" is required if no __default is defined.', static::class),
                1381512753
            );
        }
        if ($value === null) {
            $value = static::__default;
        }
        static::loadValues();
        if (!$this->isValid($value)) {
            throw new Exception\InvalidEnumerationValueException(
                sprintf('Invalid value "%s" for enumeration "%s"', $value, static::class),
                1381512761
            );
        }
        $this->setValue($value);
    }

    /**
     * @throws Exception\InvalidEnumerationValueException
     * @throws Exception\InvalidEnumerationDefinitionException
     * @internal param string $class
     */
    protected static function loadValues()
    {
        $class = get_called_class();

        if (isset(static::$enumConstants[$class])) {
            return;
        }

        $reflection = new \ReflectionClass($class);
        $constants = $reflection->getConstants();
        $defaultValue = null;
        if (isset($constants['__default'])) {
            $defaultValue = $constants['__default'];
            unset($constants['__default']);
        }
        if (empty($constants)) {
            throw new Exception\InvalidEnumerationValueException(
                sprintf(
                    'No constants defined in enumeration "%s"',
                    $class
                ),
                1381512807
            );
        }
        foreach ($constants as $constant => $value) {
            if (!is_int($value) && !is_string($value)) {
                throw new Exception\InvalidEnumerationDefinitionException(
                    sprintf(
                        'Constant value "%s" of enumeration "%s" must be of type integer or string, got "%s" instead',
                        $constant,
                        $class,
                        is_object($value) ? get_class($value) : gettype($value)
                    ),
                    1381512797
                );
            }
        }
        $constantValueCounts = array_count_values($constants);
        arsort($constantValueCounts, SORT_NUMERIC);
        $constantValueCount = current($constantValueCounts);
        $constant = key($constantValueCounts);
        if ($constantValueCount > 1) {
            throw new Exception\InvalidEnumerationDefinitionException(
                sprintf(
                    'Constant value "%s" of enumeration "%s" is not unique (defined %d times)',
                    $constant,
                    $class,
                    $constantValueCount
                ),
                1381512859
            );
        }
        if ($defaultValue !== null) {
            $constants['__default'] = $defaultValue;
        }
        static::$enumConstants[$class] = $constants;
    }

    /**
     * Set the Enumeration value to the associated enumeration value by a loose comparison.
     * The value, that is used as the enumeration value, will be of the same type like defined in the enumeration
     *
     * @param mixed $value
     * @throws Exception\InvalidEnumerationValueException
     */
    protected function setValue($value)
    {
        $enumKey = array_search((string)$value, static::$enumConstants[static::class]);
        if ($enumKey === false) {
            throw new Exception\InvalidEnumerationValueException(
                sprintf('Invalid value "%s" for enumeration "%s"', $value, __CLASS__),
                1381615295
            );
        }
        $this->value = static::$enumConstants[static::class][$enumKey];
    }

    /**
     * Check if the value on this enum is a valid value for the enum
     *
     * @param mixed $value
     * @return bool
     */
    protected function isValid($value)
    {
        $value = (string)$value;
        foreach (static::$enumConstants[static::class] as $constantValue) {
            if ($value === (string)$constantValue) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the valid values for this enum
     * Defaults to constants you define in your subclass
     * override to provide custom functionality
     *
     * @param bool $include_default
     * @return array
     */
    public static function getConstants($include_default = false)
    {
        static::loadValues();
        $enumConstants = static::$enumConstants[get_called_class()];
        if (!$include_default) {
            unset($enumConstants['__default']);
        }
        return $enumConstants;
    }

    /**
     * Cast value to enumeration type
     *
     * @param mixed $value Value that has to be casted
     * @return self
     */
    public static function cast($value)
    {
        $currentClass = get_called_class();
        if (!is_object($value) || get_class($value) !== $currentClass) {
            $value = new $currentClass($value);
        }
        return $value;
    }

    /**
     * Compare if the value of the current object value equals the given value
     *
     * @param mixed $value default
     * @return bool
     */
    public function equals($value)
    {
        $value = static::cast($value);
        return $this == $value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->value;
    }

    /**
     * Returns the constants name as is, without manipulation (usually all upper case)
     *
     * @param string|int $value
     * @return string
     */
    public static function getName($value)
    {
        $name = '';
        $constants = array_flip(static::getConstants());
        if (array_key_exists($value, $constants)) {
            $name = $constants[$value];
        }
        return $name;
    }

    /**
     * Returns the name of the constant, first char upper, underscores as spaces
     *
     * @param string|int $value
     * @return string
     */
    public static function getHumanReadableName($value)
    {
        $name = static::getName($value);
        return ucwords(strtolower(str_replace('_', ' ', $name)));
    }
}
