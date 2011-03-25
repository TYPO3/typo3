<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * A caching backend which forgets everything immediately
 * Used in t3lib_cache_FactoryTestCase
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage tests
 */
class t3lib_cache_backend_MockBackend extends t3lib_cache_backend_NullBackend {

	/**
	 * @var mixed
	 */
	protected $someOption;

	/**
	 * Sets some option
	 *
	 * @param mixed $value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setSomeOption($value) {
		$this->someOption = $value;
	}

	/**
	 * Returns the option value
	 *
	 * @return mixed
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSomeOption() {
		return $this->someOption;
	}

}

?>