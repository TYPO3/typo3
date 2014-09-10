<?php
namespace TYPO3\CMS\IndexedSearch\ViewHelpers\Format;

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
 * FlagValue viewhelper
 */
class FlagValueViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render additional flag information
	 *
	 * @param int $flags
	 * @return string
	 */
	public function render($flags) {
		$flags = (int)$flags;
		if ($flags > 0) {
			$content = ($flags & 128 ? '<title>' : '')
				. ($flags & 64 ? '<meta/keywords>' : '')
				. ($flags & 32 ? '<meta/description>' : '');

			return htmlspecialchars($content);
		}
		return '';
	}

}
