<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog <susanne.moog@typo3.org>
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
 * Returns a string meant to be used as css class stating whether an extension is
 * available or installed
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class InstallationStateCssClassViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Returns string meant to be used as css class
	 * 'installed' => if an extension is installed
	 * 'available' => if an extension is available in the sytem
	 * '' (empty string) => if neither installed nor available
	 *
	 * @param string $needle
	 * @param array $haystack
	 * @return string the rendered a tag
	 */
	public function render($needle, array $haystack) {
		if (array_key_exists($needle, $haystack)) {
			if (isset($haystack[$needle]['installed']) && $haystack[$needle]['installed'] === TRUE) {
				return 'installed';
			} else {
				return 'available';
			}
		}
		return '';
	}

}


?>