<?php
namespace TYPO3\CMS\Core\Type;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Thomas Maroschik <tmaroschik@dfau.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Abstract class for Enumeration.
 * Inspired by SplEnum.
 *
 * The prefix "Abstract" has been left out by intention because
 * a "type" is abstract by definition.
 */
abstract class Enumeration {

	/**
	 * @var string
	 */
	protected $value;

	/**
	 * @var array
	 */
	protected static $enumConstants;

	/**
	 * @param mixed $value
	 * @throws \DomainException
	 * @throws \UnexpectedValueException
	 */
	public function __construct($value = NULL) {
		if (!defined('static::__default')) {
			throw new \DomainException(sprintf("Required constant __default for Enum %s is not defined", __CLASS__), 1381512753);
		}
		if ($value === NULL) {
			$value = static::__default;
		}
		$this->loadValues();
		if (!$this->isValid($value)) {
			throw new \UnexpectedValueException(sprintf("Invalid enumeration %s for Enum %s", $value, __CLASS__), 1381512761);
		}
		$this->setValue($value);
	}

	/**
	 * @throws \Exception
	 * @internal param string $class
	 */
	protected function loadValues() {
		$class = get_called_class();

		if (isset(static::$enumConstants[$class])) {
			return;
		}

		$reflection = new \ReflectionClass($class);
		$constants  = $reflection->getConstants();
		if (isset($constants['__default'])) {
			unset($constants['__default']);
		}
		if (empty($constants)) {
			throw new \Exception(
				sprintf(
					'No enumeration constants defined for "%s"', $class
				),
				1381512807
			);
		}
		foreach ($constants as $constant => $value) {
			if (!is_int($value) && !is_string($value)) {
				throw new \Exception(
					sprintf(
						'Constant value must be of type integer or string; constant=%s; type=%s',
						$constant,
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
			throw new \Exception(
				sprintf(
					'Constant value is not unique; constant=%s; value=%s; enum=%s',
					$constant, $constantValueCount, $class
				),
				1381512859
			);
		}
		static::$enumConstants[$class] = $constants;
	}

	/**
	 * @param mixed $value
	 */
	protected function setValue($value) {
		$this->value = $value;
	}

	/**
	 * Check if the value on this enum is a valid value for the enum
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	protected function isValid($value) {
		return in_array($value, static::$enumConstants[get_called_class()]);
	}

	/**
	 * Get the valid values for this enum
	 * Defaults to constants you define in your subclass
	 * override to provide custom functionality
	 *
	 * @param boolean $include_default
	 * @return array
	 */
	public function getConstants($include_default = FALSE) {
		$enumConstants = static::$enumConstants[get_called_class()];
		if (!$include_default) {
			unset($enumConstants['__default']);
		}
		return $enumConstants;
	}

	/**
	 * Compare if the value of the current object value equals the given value
	 *
	 * @param mixed $value default
	 * @return boolean
	 */
	public function equals($value) {
		$currentClass = get_class($this);
		if (!is_object($value) || get_class($value) !== $currentClass) {
			$value = new $currentClass($value);
		}
		return $this === $value;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return (string)$this->value;
	}
}