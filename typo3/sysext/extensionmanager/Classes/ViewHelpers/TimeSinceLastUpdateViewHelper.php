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
 * Shows the elapsed time since the last update of the extension repository
 * from TER in a readable manner.
 *
 * @internal
 */
class TimeSinceLastUpdateViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render method
	 *
	 * @param \DateTime $lastUpdateTime The date of the last update.
	 * @return string
	 */
	public function render($lastUpdateTime) {
		if (NULL === $lastUpdateTime) {
			return $GLOBALS['LANG']->sL(
				'LLL:EXT:extensionmanager/Resources/Private/Language/locallang.xlf:extensionList.updateFromTer.never'
			);
		}
		return \TYPO3\CMS\Backend\Utility\BackendUtility::calcAge(
			time() - $lastUpdateTime->format('U'),
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')
		);
	}
}
