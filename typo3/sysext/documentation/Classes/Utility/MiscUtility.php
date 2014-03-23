<?php
namespace TYPO3\CMS\Documentation\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Xavier Perseguers <xavier@typo3.org>
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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/**
 * Misc utility.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 */
class MiscUtility {

	/**
	 * Returns meta-data for a given extension.
	 *
	 * @param string $extensionKey
	 * @return array
	 */
	static public function getExtensionMetaData($extensionKey) {
		$_EXTKEY = $extensionKey;
		$EM_CONF = array();
		$extPath = ExtensionManagementUtility::extPath($extensionKey);
		include($extPath . 'ext_emconf.php');

		$release = $EM_CONF[$_EXTKEY]['version'];
		list($major, $minor, $_) = explode('.', $release, 3);
		if (($pos = strpos($minor, '-')) !== FALSE) {
			// $minor ~ '2-dev'
			$minor = substr($minor, 0, $pos);
		}
		$EM_CONF[$_EXTKEY]['version'] = $major . '.' . $minor;
		$EM_CONF[$_EXTKEY]['release'] = $release;
		$EM_CONF[$_EXTKEY]['extensionKey'] = $extensionKey;

		return $EM_CONF[$_EXTKEY];
	}

	/**
	 * Returns the icon associated to a given document key.
	 *
	 * @param string $documentKey
	 * @return string
	 */
	static public function getIcon($documentKey) {
		$basePath = 'typo3conf/Documentation/';
		$documentPath = $basePath . $documentKey . '/';

		// Fallback icon
		$icon = ExtensionManagementUtility::siteRelPath('documentation') . 'ext_icon.gif';

		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($documentKey, 'typo3cms.extensions.')) {
			// Standard extension icon
			$extensionKey = substr($documentKey, 20);
			if (ExtensionManagementUtility::isLoaded($extensionKey)) {
				$extensionPath = ExtensionManagementUtility::extPath($extensionKey);
				$siteRelativePath = ExtensionManagementUtility::siteRelPath($extensionKey);
				$icon = $siteRelativePath . ExtensionManagementUtility::getExtensionIcon($extensionPath);
			}
		} elseif (is_file(PATH_site . $documentPath . 'icon.png')) {
			$icon = $documentPath . 'icon.png';
		} elseif (is_file(PATH_site . $documentPath . 'icon.gif')) {
			$icon = $documentPath . 'icon.gif';
		}

		return $icon;
	}

}
