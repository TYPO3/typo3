<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * Contains an abstract class for all tslib content class implementations.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
abstract class tslib_content_Abstract {

	/**
	 * @var $cObj tslib_cObj
	 */
	protected $cObj;

	/**
	 * Default constructor.
	 *
	 * @param tslib_cObj $cObj
	 */
	public function __construct(tslib_cObj $cObj) {
		$this->cObj = $cObj;
	}

	/**
	 * Renders the content object.
	 *
	 * @param array $conf
	 * @return string
	 */
	public abstract function render($conf = array());

	/**
	 * Getter for current cObj
	 *
	 * @return tslib_cObj
	 */
	public function getContentObject() {
		return $this->cObj;
	}
}

?>
