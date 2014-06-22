<?php
namespace TYPO3\CMS\Install\ViewHelpers\File;

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
 * Simple view helper to check if given file is a regular file
 *
 * = Examples =
 *
 * <code title="Default">
 * <f:file.exists file="Absolute-path" />
 * </code>
 *
 * <output>
 * TRUE or FALSE
 * </output>
 */
class ExistsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Check if given file is a regular file
	 *
	 * @param string $file Absolute path
	 * @return boolean
	 */
	public function render($file) {
		$result = FALSE;
		if (file_exists($file) && is_file($file)) {
			$result = TRUE;
		}
		return $result;
	}
}
