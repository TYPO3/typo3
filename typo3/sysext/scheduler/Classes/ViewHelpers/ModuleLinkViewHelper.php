<?php
namespace TYPO3\CMS\Scheduler\ViewHelpers;

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
 * Create internal link within backend app
 * @TODO: Later I want to be an AbstractTagBasedViewHelper
 */
class ModuleLinkViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Generate module link
	 *
	 * @param string $function
	 * @param string $cms
	 * @param array $arguments
	 * @return string
	 */
	public function render($function = '', $cmd = '', array $arguments = array()) {
		$link = $GLOBALS['MCONF']['_'] . '&SET[function]=' . $function . '&CMD=' . $cmd;

		if (!empty($arguments)) {
			foreach ($arguments as $key => $value) {
				$link .= '&tx_scheduler[' . $key . ']=' . $value;
			}
		}

		return htmlspecialchars($link);
	}

}