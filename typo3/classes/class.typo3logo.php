<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Ingo Renner <ingo@typo3.org>
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
 * class to render the TYPO3 logo in the backend
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage core
 */
class TYPO3Logo {

	protected $logo;

	/**
	 * constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->logo = NULL;
	}

	/**
	 * renders the actual logo code
	 *
	 * @return	string	logo html code snippet to use in the backend
	 */
	public function render() {

		$logoFile = 'gfx/alt_backend_logo.gif'; // default
		if(is_string($this->logo)) {
				// overwrite
			$logoFile = $this->logo;
		}
		$imgInfo = getimagesize(PATH_site . TYPO3_mainDir . $logoFile);


		$logo = '<a href="' . TYPO3_URL_GENERAL . '" target="_blank">' .
				'<img' . t3lib_iconWorks::skinImg('', $logoFile, $imgInfo[3]) . ' title="TYPO3 Content Management System" alt="" />' .
				'</a>';

			// overwrite with custom logo
		if($GLOBALS['TBE_STYLES']['logo'])	{
			$imgInfo = @getimagesize(t3lib_div::resolveBackPath(PATH_typo3 . $GLOBALS['TBE_STYLES']['logo'], 3));
			$logo = '<a href="' . TYPO3_URL_GENERAL . '" target="_blank">' .
				'<img src="' . $GLOBALS['TBE_STYLES']['logo'] . '" ' . $imgInfo[3] . ' title="TYPO3 Content Management System" alt="" />' .
				'</a>';
		}

		return $logo;
	}

	/**
	 * sets the logo
	 *
	 * @param	string		path to logo file as seen from typo3/
	 */
	public function setLogo($logo) {
		if(!is_string($logo)) {
			throw new InvalidArgumentException('parameter $logo must be of type string', 1194041104);
		}

		$this->logo = $logo;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/classes/class.typo3logo.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/classes/class.typo3logo.php']);
}

?>