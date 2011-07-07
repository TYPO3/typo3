<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Dominique Feyer <dfeyer@reelpeek.net>
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
 * Provides a language parser factory.
 *
 * @package	TYPO3
 * @subpackage	tx_lang
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
class tx_lang_Factory implements t3lib_Singleton {

	/**
	 * @var tx_lang_cache_Abstract
	 */
	protected $cache;

	/**
	 * @var integer
	 */
	protected $errorMode;

	/**
	 * @var tx_lang_Store
	 */
	public $store;

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->supportedExtension = array();
		$this->ressourceConfiguration = array();
		$this->store = t3lib_div::makeInstance('tx_lang_Store');

		$this->initialize();
	}

	/**
	 * Sets factory configuration.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->cache = t3lib_div::makeInstance('tx_lang_cache_CachingFramework');
	}

	/**
	 * Returns parsed data from a given file and language key.
	 *
	 * @param string $fileReference Input is a file-reference (see t3lib_div::getFileAbsFileName). That file is expected to be a supported locallang file format
	 * @param string $languageKey Language key
	 * @param string $charset Character set (option); if not set, determined by the language key
	 * @param int $errorMode Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception
	 * @return array|bool
	 */
	public function getParsedData($fileReference, $languageKey, $charset, $errorMode) {
		$hash = md5($fileReference . $languageKey . $charset);
		$this->errorMode = $errorMode;

			// English is the default language
		$languageKey = ($languageKey === 'en') ? 'default' : $languageKey;

			// Check if the default language is processed before processing other language
		if (!$this->store->hasData($fileReference, 'default') && $languageKey !== 'default') {
			$this->getParsedData($fileReference, 'default', $charset, $this->errorMode);
		}

			// If the content is parsed (local cache), use it
		if ($this->store->hasData($fileReference, $languageKey)) {
			return $this->store->getData($fileReference);
		}

			// If the content is in cache (system cache), use it
		if ($parsedData = $this->cache->get($hash)) {
			return $this->store->setData($fileReference, $languageKey, $parsedData)
					->getData($fileReference, $languageKey);
		}

		try {
			$this->store->setConfiguration($fileReference, $languageKey, $charset);

			/** @var $parser tx_lang_parser */
			$parser = $this->store->getParserInstance($fileReference);

			try {
					// Get parsed data
				$LOCAL_LANG = $parser->getParsedData(
					$this->store->getAbsoluteFileReference($fileReference),
					$languageKey,
					$charset
				);

					// Hooks to override localization
				$overrides = array();
				$fileReferenceWithoutExtension = $this->store->getFileReferenceWithoutExtension($fileReference);

				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$fileReferenceWithoutExtension . '.php'])) {
					$overrides = array_merge($overrides, $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$fileReferenceWithoutExtension . '.php']);
				}
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$fileReferenceWithoutExtension . '.xml'])) {
					$overrides = array_merge($overrides, $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$fileReferenceWithoutExtension . '.xml']);
				}
				if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$fileReferenceWithoutExtension . '.xlf'])) {
					$overrides = array_merge($overrides, $GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride'][$fileReferenceWithoutExtension . '.xlf']);
				}

				if (count($overrides) > 0) {
					foreach ($overrides as $overrideFile) {
						$languageOverrideFileName = t3lib_div::getFileAbsFileName($overrideFile);
						$LOCAL_LANG = t3lib_div::array_merge_recursive_overrule(
							$LOCAL_LANG,
							$this->getParsedData($languageOverrideFileName, $languageKey, $charset, $errorMode)
						);
					}
				}

					// Save parsed data in cache
				$this->store->setData(
					$fileReference,
					$languageKey,
					$LOCAL_LANG[$languageKey]
				);

					// Cache processed data
				$this->cache->set($hash, $this->store->getDataByLanguage($fileReference, $languageKey));
			} catch (tx_lang_exception_FileNotFound $exception) {
					// Target localization file not found
				$this->store->setData($fileReference, $languageKey, array());
			}
		} catch (tx_lang_exception_FileNotFound $exception) {
				// Source localization file not found
			$this->store->setData($fileReference, $languageKey, array());
		}

		return $this->store->getData($fileReference);
	}

}

?>