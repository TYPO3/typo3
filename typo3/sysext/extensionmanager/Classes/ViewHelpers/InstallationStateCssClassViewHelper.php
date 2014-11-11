<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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
 * Returns a string meant to be used as css class stating whether an extension is
 * available or installed
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 * @internal
 */
class InstallationStateCssClassViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Returns string meant to be used as css class
	 * 'installed' => if an extension is installed
	 * 'available' => if an extension is available in the system
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
