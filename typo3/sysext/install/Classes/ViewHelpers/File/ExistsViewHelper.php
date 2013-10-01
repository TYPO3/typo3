<?php
namespace TYPO3\CMS\Install\ViewHelpers\File;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
