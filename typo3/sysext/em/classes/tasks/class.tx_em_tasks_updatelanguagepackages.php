<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Xavier Perseguers <xavier@typo3.org>
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
 * Update language packages task.
 *
 * @author Xavier Perseguers <xavier@typo3.org>
 * @package TYPO3
 * @subpackage em
 */
class tx_em_Tasks_UpdateLanguagePackages extends tx_scheduler_Task {

	/**
	 * Must be public to be accessed by tx_em_Connection_Ter
	 * @var tx_em_Tools_XmlHandler
	 */
	public $xmlHandler;

	/**
	 * @var tx_em_Connection_Ter
	 */
	protected $terConnection;

	/**
	 * @var tx_em_Settings
	 */
	protected $emSettings;

	/**
	 * Public method, usually called by scheduler.
	 *
	 * @return boolean TRUE on success
	 */
	public function execute() {
			// Throws exceptions if something goes wrong
		$this->updateLanguagePackages();

		return TRUE;
	}

	/**
	 * Updates the language packages and flushes the cache afterwards.
	 *
	 * @return void
	 */
	protected function updateLanguagePackages() {
		$this->xmlHandler = t3lib_div::makeInstance('tx_em_Tools_XmlHandler');
		$this->terConnection = t3lib_div::makeInstance('tx_em_Connection_Ter', $this);
		$this->emSettings = t3lib_div::makeInstance('tx_em_Settings');

		$emSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['em']);
		$selectedLanguages = t3lib_div::trimExplode(',', $emSettings['selectedLanguages']);

		/** @var $extensionsList tx_em_Extensions_List */
		$extensionsList = t3lib_div::makeInstance('tx_em_Extensions_List');
		$installedExtensions = $extensionsList->getInstalledExtensions(TRUE);

		foreach ($installedExtensions as $extension) {
			$this->fetchTranslations($extension['extkey'], $selectedLanguages);
		}

		/** @var $cacheInstance t3lib_cache_frontend_StringFrontend */
		$cacheInstance = $GLOBALS['typo3CacheManager']->getCache('t3lib_l10n');
		$cacheInstance->flush();
	}

	/**
	 * Fetches translation from server.
	 *
	 * @param string $extensionKey
	 * @param array $languages
	 * @return void
	 * @see tx_em_Connection_ExtDirectServer::fetchTranslations()
	 */
	protected function fetchTranslations($extensionKey, array $languages) {
		if (count($languages) > 0) {
			$mirrorURL = $this->emSettings->getMirrorURL();

			foreach ($languages as $language) {
				$fetch = $this->terConnection->fetchTranslationStatus($extensionKey, $mirrorURL);

				$localmd5 = '';
				if (isset($fetch[$language])) {
					$zip = PATH_site . 'typo3temp/' . $extensionKey . '-l10n-' . $language . '.zip';
					if (is_file($zip)) {
						$localmd5 = md5_file($zip);
					}
					if ($localmd5 !== $fetch[$language]['md5']) {
							// Fetch translation
						$this->terConnection->updateTranslation($extensionKey, $language, $mirrorURL);
					}
				}
			}
		}
	}

}
?>