<?php
namespace TYPO3\CMS\Form\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Static methods for filtering
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class FilterUtility implements \TYPO3\CMS\Form\Filter\FilterInterface {

	/**
	 * Array with filter objects to use
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Constructor
	 * Adds the removeXSS filter by default
	 * Never remove these lines, otherwise the forms
	 * will be vulnerable for XSS attacks
	 */
	public function __construct() {
		$removeXssFilter = $this->makeFilter('removexss');
		$this->addFilter($removeXssFilter);
	}

	/**
	 * Add a filter object to the filter array
	 *
	 * @param \TYPO3\CMS\Form\Filter\FilterInterface $filter The filter
	 * @return \TYPO3\CMS\Form\Utility\FilterUtility
	 */
	public function addFilter(\TYPO3\CMS\Form\Filter\FilterInterface $filter) {
		$this->filters[] = $filter;
		return $this;
	}

	/**
	 * Create a filter object according to class
	 * and sent some arguments
	 *
	 * @param string $class Name of the filter
	 * @param array $arguments Configuration of the filter
	 * @return \TYPO3\CMS\Form\Filter\FilterInterface The filter object
	 */
	public function makeFilter($class, array $arguments = NULL) {
		return self::createFilter($class, $arguments);
	}

	/**
	 * Go through all filters added to the array
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function filter($value) {
		if (!empty($this->filters)) {
			/** @var $filter \TYPO3\CMS\Form\Filter\FilterInterface */
			foreach ($this->filters as $filter) {
				$value = $filter->filter($value);
			}
		}
		return $value;
	}

	/**
	 * Call filter through this class with automatic instantiation of filter
	 *
	 * @param string $class
	 * @param mixed $value
	 * @param array $arguments
	 * @return mixed
	 */
	static public function get($class, $value, array $arguments = array()) {
		return self::createFilter($class, $arguments)->filter($value);
	}

	/**
	 * Create a filter object according to class
	 * and sent some arguments
	 *
	 * @param string $class Name of the filter
	 * @param array $arguments Configuration of the filter
	 * @return \TYPO3\CMS\Form\Filter\FilterInterface The filter object
	 */
	static public function createFilter($class, array $arguments = NULL) {
		$class = strtolower((string) $class);
		$className = 'TYPO3\\CMS\\Form\\Filter\\' . ucfirst($class) . 'Filter';
		if (is_null($arguments)) {
			$filter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className);
		} else {
			$filter = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($className, $arguments);
		}
		return $filter;
	}

}

?>