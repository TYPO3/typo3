<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Daniel Sattler <daniel.sattler@b13.de>
*  (c) 2012 Benjamin Mack <benni@typo3.org>
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
 * This class serves as the main entry point and API for everything that
 * is requireJS related.
 *
 * @package TYPO3
 * @subpackage core
 */
class Typo3_Requirejs {

	/**
	 * Creates the dynamic configuration file that is loaded from requirejs
	 *
	 * @param array $params Always empty.
	 * @param TYPO3AJAX $ajaxObj The Ajax object used to return content and set content types
	 * @return void
	 */
	public function getConfigurationForAjaxRequest(array $params, TYPO3AJAX $ajaxObj) {
		$allConfiguration = $this->getConfiguration();

		$content = 'var require = ' . json_encode($allConfiguration) . ';';
		$ajaxObj->addContent('configuration', $content);
		$ajaxObj->setContentFormat('plain');
	}

	/**
	 * generates an array of the configuration, merging all paths
	 *
	 * @return array
	 */
	public function getConfiguration() {
		$paths = array();

			// add paths from extensions
		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS'] as $prefix => $pathName) {
			$fullPath = t3lib_div::getFileAbsFileName($pathName, FALSE, TRUE);
			$fullPath = t3lib_utility_Path::getRelativePath(PATH_typo3, $fullPath);
			$paths[$prefix] = rtrim($fullPath, '/');
		}


		$allConfiguration = array(
			'baseUrl' => t3lib_div::getIndpEnv('TYPO3_SITE_PATH') . TYPO3_mainDir,
			'paths' => $paths,
			'waitSeconds' => 15,	// seconds to wait until requirejs marks the request as a timeout
			'locale' => 'en-gb'
		);
		return $allConfiguration;
	}
}
