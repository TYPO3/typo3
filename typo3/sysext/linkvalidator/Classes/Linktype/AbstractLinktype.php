<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 - 2013 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Check Base plugin implementation
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
abstract class AbstractLinktype implements \TYPO3\CMS\Linkvalidator\Linktype\LinktypeInterface {

	/**
	 * Contains parameters needed for the rendering of the error message
	 *
	 * @var array
	 */
	protected $errorParams = array();

	/**
	 * Base type fetching method, based on the type that softRefParserObj returns
	 *
	 * @param array $value Reference properties
	 * @param string $type Current type
	 * @param string $key Validator hook name
	 * @return string Fetched type
	 */
	public function fetchType($value, $type, $key) {
		if ($value['type'] == $key) {
			$type = $value['type'];
		}
		return $type;
	}

	/**
	 * Set the value of the protected property errorParams
	 *
	 * @param array $value All parameters needed for the rendering of the error message
	 * @return void
	 */
	protected function setErrorParams($value) {
		$this->errorParams = $value;
	}

	/**
	 * Get the value of the private property errorParams
	 *
	 * @return array All parameters needed for the rendering of the error message
	 */
	public function getErrorParams() {
		return $this->errorParams;
	}

	/**
	 * Construct a valid Url for browser output
	 *
	 * @param array $row Broken link record
	 * @return string Parsed broken url
	 */
	public function getBrokenUrl($row) {
		return $row['url'];
	}

}

?>