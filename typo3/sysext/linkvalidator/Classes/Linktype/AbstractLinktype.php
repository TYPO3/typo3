<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

/**
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
