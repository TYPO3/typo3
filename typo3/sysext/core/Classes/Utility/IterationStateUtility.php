<?php

namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Lars Peipmann <Lars@Peipmann.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class with helper functions for iteration states
 *
 * @author Lars Peipmann <Lars@Peipmann.de>
 */
class IterationStateUtility {
	/**
	 * The current iteration number
	 *
	 * @var integer
	 */
	protected $iteration = 0;

	/**
	 * The maximum of iterations
	 *
	 * @var integer
	 */
	protected $maximum;

	/**
	 * The key of the current iteration
	 *
	 * @var string
	 */
	protected $key;

	public function __construct($maximum) {
		$this->setMaximum($maximum);
	}

	/**
	 * Returns true if it is the first iteration.
	 *
	 * @return boolean
	 */
	public function isFirst() {
		return $this->iteration === 1;
	}

	/**
	 * Returns true if it is the last iteration.
	 *
	 * @return boolean
	 */
	public function isLast() {
		return $this->iteration === $this->maximum;
	}

	/**
	 * Returns true if it is a event iteration number.
	 *
	 * @return boolean
	 */
	public function isEven() {
		return $this->iteration % 2 === 0;
	}

	/**
	 * Returns true if it is a odd iteration number.
	 *
	 * @return boolean
	 */
	public function isOdd() {
		return !$this->isEven();
	}

	/**
	 * Returns the current number of the iteration.
	 * Starts with 1.
	 *
	 * @return integer
	 */
	public function getIteration() {
		return $this->iteration;
	}

	/**
	 * Returns the current index.
	 * Starts with 0.
	 *
	 * @return integer
	 */
	public function getIndex() {
		return $this->iteration - 1;
	}

	/**
	 * Increases the iteration with one.
	 *
	 * @param string|NULL $key
	 * @param integer $steps
	 * @return \TYPO3\CMS\Core\Utility\IterationStateUtility
	 */
	public function increase($key = NULL, $steps = 1) {
		$this->iteration += $steps;
		if ($key !== NULL) {
			$this->setKey($key);
		}
		return $this;
	}

	/**
	 * Returns the maximum of iterations.
	 *
	 * @return integer
	 */
	public function getMaximum() {
		return $this->maximum;
	}

	/**
	 * Sets the maximum of iterations;
	 *
	 * @param integer $maximum
	 * @return \TYPO3\CMS\Core\Utility\IterationStateUtility
	 */
	public function setMaximum($maximum) {
		$this->maximum = intval($maximum);
		return $this;
	}

	/**
	 * Returns the key of the current iteration.
	 *
	 * @return string
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * Sets the key of the current iteration.
	 *
	 * @param string $key
	 * @return \TYPO3\CMS\Core\Utility\IterationStateUtility
	 */
	protected function setKey($key) {
		$this->key = $key;
		return $this;
	}
}

?>