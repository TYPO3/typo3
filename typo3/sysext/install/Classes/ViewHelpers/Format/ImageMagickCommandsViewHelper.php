<?php
namespace TYPO3\CMS\Install\ViewHelpers\Format;

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
 * Display image magick commands
 */
class ImageMagickCommandsViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Display image magick commands
	 *
	 * @param array $commands Given commands
	 * @return string Formatted commands
	 */
	public function render(array $commands = array()) {
		$result = array();
		foreach ($commands as $commandGroup) {
			$result[] = 'Command: ' . $commandGroup[1];
			// If 3 elements: last one is result
			if (count($commandGroup) === 3) {
				$result[] = 'Result: ' . $commandGroup[2];
			}
		}
		return '<textarea rows="' . count($result) . '">' . implode(LF, $result) . '</textarea>';
	}
}
