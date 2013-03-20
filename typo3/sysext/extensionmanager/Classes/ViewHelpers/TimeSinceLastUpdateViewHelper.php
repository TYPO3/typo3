<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;
use \TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/*****************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Jost Baron <j.baron@netzkoenig.de>
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
 *****************************************************************/

/**
 * Shows the elapsed time since the last update of the extension repository
 * from TER in a readable manner.
 */
class TimeSinceLastUpdateViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render method
	 *
	 * @param \DateTime $lastUpdateTime The date of the last update.
	 * @return string
	 */
	public function render($lastUpdateTime) {

		$now = new \DateTime();
		$timeSinceLastUpdate = $lastUpdateTime->diff($now, TRUE);

		$result = '';

		if ($timeSinceLastUpdate->days > 1) {
			$label = LocalizationUtility::translate('extensionList.updateFromTer.lastUpdate.days.plural', 'extensionmanager');
			$result = $timeSinceLastUpdate->format('%a') . $label;

		} elseif ($timeSinceLastUpdate->days === 1) {
			$label = LocalizationUtility::translate('extensionList.updateFromTer.lastUpdate.days.singular', 'extensionmanager');
			$result = '1' . $label;

		} elseif ($timeSinceLastUpdate->h > 1) {
			$label = LocalizationUtility::translate('extensionList.updateFromTer.lastUpdate.hours.plural', 'extensionmanager');
			$result = $timeSinceLastUpdate->format('%h') . $label;

		} elseif ($timeSinceLastUpdate->h === 1) {
			$label = LocalizationUtility::translate('extensionList.updateFromTer.lastUpdate.hours.singular', 'extensionmanager');
			$result = '1' . $label;

		} else {
			$label = LocalizationUtility::translate('extensionList.updateFromTer.lastUpdate.lessThanOneHour', 'extensionmanager');
			$result = $label;
		}

		return $result;
	}
}

?>