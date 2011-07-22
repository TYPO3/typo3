<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
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
 * @package TYPO3
 * @subpackage form
 */
class tx_form_system_filter implements tx_form_system_filter_interface {

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
	 *
	 * @param array $arguments Filter configuration
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct($arguments = array()) {
		$removeXssFilter = $this->makeFilter('removexss');
		$this->addFilter($removeXssFilter);
	}

	/**
	 * Add a filter object to the filter array
	 *
	 * @param string $class Name of the filter
	 * @param mixed $value Typoscript configuration
	 * @return tx_form_filter
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function addFilter($filter) {
		$this->filters[] = (object) $filter;

		return $this;
	}

	/**
	 * Create a filter object according to class
	 * and sent some arguments
	 *
	 * @param string $class Name of the filter
	 * @param array $arguments Configuration of the filter
	 * @return object The filter object
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function makeFilter($class, $arguments = array()) {
		$class = strtolower((string) $class);
		$className = 'tx_form_system_filter_' . $class;

		$filter = t3lib_div::makeInstance($className, $arguments);

		return $filter;
	}

	/**
	 * Go through all filters added to the array
	 *
	 * @param  mixed $value
	 * @return mixed
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function filter($value) {
		if(!empty($this->filters)) {
			foreach($this->filters as $filter) {
				$value = $filter->filter($value);
			}
		}
		return $value;
	}

	/**
	 * Call filter through this class with automatic instantiation of filter
	 *
	 * @param $class
	 * @param $value
	 * @param $arguments
	 * @return mixed
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public static function get($class, $value, array $arguments = array()) {
		$class = strtolower((string) $class);
		$className = 'tx_form_system_filter_' . $class;

		$object = t3lib_div::makeInstance($className, $arguments);

		return $object->filter($value);
	}
}
?>