<?php
namespace TYPO3\CMS\Backend\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Ingo Renner <ingo@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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
 * @author Ingo Renner <ingo@typo3.org>
 */
class LogoView {
	protected $logo = '';

	public function __construct() {
		$this->logo = 'gfx/typo3-topbar@2x.png';
	}

	/**
	 * renders the actual logo code
	 *
	 * @return string Logo html code snippet to use in the backend
	 */
	public function render() {
		$imgInfo = getimagesize(PATH_site . TYPO3_mainDir . $this->logo);
		$imgUrl = $this->logo;

		// Overwrite with custom logo
		if ($GLOBALS['TBE_STYLES']['logo']) {
			$imgInfo = @getimagesize(\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath((PATH_typo3 . $GLOBALS['TBE_STYLES']['logo']), 3));
			$imgUrl = $GLOBALS['TBE_STYLES']['logo'];
		}

		// High-res?
		$width = $imgInfo[0];
		$height = $imgInfo[1];

		if (strpos($imgUrl, '@2x.')) {
			$width = $width/2;
			$height = $height/2;
		}

		$logoTag = '<img src="' . $imgUrl . '" width="' . $width . '" height="' . $height . '" title="TYPO3 Content Management System" alt="" />';
		return '<a href="' . TYPO3_URL_GENERAL . '" target="_blank">' . $logoTag . '</a>';
	}

	/**
	 * Sets the logo
	 *
	 * @param string $logo Path to logo file as seen from typo3/
	 * @throws \InvalidArgumentException
	 */
	public function setLogo($logo) {
		if (!is_string($logo)) {
			throw new \InvalidArgumentException('parameter $logo must be of type string', 1194041104);
		}
		$this->logo = $logo;
	}

}
