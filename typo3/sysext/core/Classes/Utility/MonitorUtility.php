<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Jigal van Hemert <jigal@xs4all.nl>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class to handle monitoring actions.
 *
 * @author Jigal van Hemert <jigal@xs4all.nl>
 */
class MonitorUtility {

	/**
	 * Checks peak memory usage and stores data in cache for use in the report module
	 *
	 * @return void
	 */
	static public function peakMemoryUsage() {
		$peakUsage = memory_get_peak_usage(TRUE);
		$memoryLimit = \TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement(ini_get('memory_limit'));
		if (is_double($memoryLimit) && $memoryLimit != 0) {
			if ($peakUsage / $memoryLimit >= 0.9) {
				/** @var $registry \TYPO3\CMS\Core\Registry */
				$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
				$data = array(
					'used' => $peakUsage,
					'tstamp' => $GLOBALS['EXEC_TIME'],
					'url' => \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL')
				);
				$registry->set('core', 'reports-peakMemoryUsage', $data);
			}
		}
	}

}


?>