<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Steffen Ritter <steffen.ritter@typo3.org>
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



if (!class_exists(Tx_extjs3compat_Loader, FALSE)) {

	/**
	 * Provides ExtJS3 to the PageRenderer
	 *
	 * @author Steffen Ritter <steffen.ritter@typo3.org>
	 */
	class Tx_extjs3compat_Loader {

		public static function loadExtJS3() {
			/** @var t3lib_PageRenderer $pageRenderer */
			$pageRenderer = t3lib_div::makeInstance('t3lib_PageRenderer');
			$pageRenderer->setExtJsPath(t3lib_extMgm::extRelPath('extjs3_compat') . 'contrib/');
			$pageRenderer->setExtCorePath(t3lib_extMgm::extRelPath('extjs3_compat') . 'contrib/');
			$pageRenderer->loadExtJS(FALSE, FALSE);

			$pageRenderer->addCssFile(t3lib_extMgm::extRelPath('extjs3_compat') . 'contrib/resources/css/ext-all-notheme.css', 'stylesheet', 'all', '', TRUE, TRUE);
			$pageRenderer->addCssFile(t3lib_extMgm::extRelPath('extjs3_compat') . 't3skin/xtheme-t3skin.css');

			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess']['exts3'] =
					t3lib_extMgm::extPath('extjs3_compat', 'tx_extjs3compat_loader.php:&Tx_extjs3compat_Loader->rewriteJSFileArrays');

			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess']['exts3'] =
					t3lib_extMgm::extPath('extjs3_compat', 'tx_extjs3compat_loader.php:&Tx_extjs3compat_Loader->rewriteJSFileHTML');
		}

		public function rewriteJSFileArrays($params, $parentObject) {
			$replaceString = '../t3lib/js/extjs/';
			$replacement = t3lib_extMgm::extRelPath('extjs3_compat') . 't3lib_extjs/';
			$newArray = array();
			foreach ($params['jsFiles'] AS $fileName => $dataArray) {
				if (strpos($fileName, $replaceString) !== FALSE) {
					$newFilename = str_replace($replaceString, $replacement, $fileName);
					$dataArray['file'] = $newFilename;
					$newArray[$newFilename] = $dataArray;
				} elseif (strpos($fileName, 'js/extjs/') === FALSE) {
					$newArray[$fileName] = $dataArray;
				}
			}
			$params['jsFiles'] = $newArray;
		}

		public function rewriteJSFileHTML($params, $parentObject) {
			$params['jsLibs'] = str_replace(
				'prototype/prototype.js" type="text/javascript"></script>',
				'prototype/prototype.js" type="text/javascript"></script>' .
				'<script src="sysext/extjs3_compat/contrib/adapter/ext/ext-base.js" type="text/javascript"></script>',
				$params['jsLibs']
			);
		}
	}
}

?>