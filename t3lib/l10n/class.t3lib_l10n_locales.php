<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Xavier Perseguers <typo3@perseguers.ch>
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
 * Locales.
 *
 * Defining backend system languages
 * When adding new keys, remember to:
 * 		- Update pages.lang item array (t3lib/stddb/tbl_be.php)
 * 		- Add character encoding for lang. key in t3lib/class.t3lib_cs.php (default for new languages is "utf-8")
 * 		- Add mappings for language in t3lib/class.t3lib_cs.php (TYPO3/ISO, language/script, script/charset)
 * 		- Update 'setup' extension labels (sysext/setup/mod/locallang.xml)
 * 		- Using translation server? Create new user with username = "language key", member of "translator" group, set to "language key" language.
 * That's it!
 *
 * @package	Core
 * @subpackage	t3lib
 * @author	Xavier Perseguers <typo3@perseguers.ch>
 */
class t3lib_l10n_Locales implements t3lib_Singleton {

	/**
	 * Supported TYPO3 locales
	 * @var array
	 */
	protected $locales = array(
		'default',	// English
		'ar',		// Arabic
		'bs',		// Bosnian
		'bg',		// Bulgarian
		'ca',		// Catalan; Valencian
		'ch',		// Chinese (Simplified)
		'cs',		// Czech
		'da',		// Danish
		'de',		// German
		'el',		// Greek
		'eo',		// Esperanto
		'es',		// Spanish; Castilian
		'et',		// Estonian
		'eu',		// Basque
		'fa',		// Persian
		'fi',		// Finnish
		'fo',		// Faroese
		'fr',		// French
		'fr_CA',	// French (Canada)
		'gl',		// Galician
		'he',		// Hebrew
		'hi',		// Hindi
		'hr',		// Croatian
		'hu',		// Hungarian
		'is',		// Icelandic
		'it',		// Italian
		'ja',		// Japanese
		'ka',		// Georgian
		'kl',		// Greenlandic
		'km',		// Khmer
		'ko',		// Korean
		'lt',		// Lithuanian
		'lv',		// Latvian
		'ms',		// Malay
		'nl',		// Dutch
		'no',		// Norwegian
		'pl',		// Polish
		'pt',		// Portuguese
		'pt_BR',	// Portuguese (Brazil)
		'ro',		// Romanian
		'ru',		// Russian
		'sk',		// Slovak
		'sl',		// Slovenian
		'sq',		// Albanian
		'sr',		// Serbian
		'sv',		// Swedish
		'th',		// Thai
		'tr',		// Turkish
		'uk',		// Ukrainian
		'vi',		// Vietnamese
		'zh',		// Chinese (China)
	);

	/**
	 * Mapping with codes used by TYPO3 4.5 and below and still in use on TER
	 * @var array
	 */
	protected $terLocaleMapping = array(
		'bs' => 'ba',		// Bosnian
		'cs' => 'cz',		// Czech
		'da' => 'dk',		// Danish
		'el' => 'gr',		// Greek
		'fr_CA' => 'qc',	// French (Canada)
		'gl' => 'ga',		// Galician
		'ja' => 'jp',		// Japanese
		'ka' => 'ge',		// Georgian
		'kl' => 'gl',		// Greenlandic
		'ko' => 'kr',		// Korean
		'ms' => 'my',		// Malay
		'pt_BR' => 'br',	// Portuguese (Brazil)
		'sl' => 'si',		// Slovenian
		'sv' => 'se',		// Swedish
		'uk' => 'ua',		// Ukrainian
		'vi' => 'vn',		// Vietnamese
		'zh' => 'hk',		// Chinese (China)
	);

	/**
	 * @var array
	 */
	protected $terLocaleReverseMapping;

	/**
	 * Dependencies for locales
	 * @var array
	 */
	protected $localeDependencies = array(
		'fr_CA' => array('fr'),
		'pt_BR' => array('pt'),
	);

	/**
	 * Initializes the languages.
	 *
	 * @static
	 * @return void
	 */
	public static function initialize() {
		/** @var $instance t3lib_l10n_Locales */
		$instance = t3lib_div::makeInstance('t3lib_l10n_Locales');
		$instance->terLocaleReverseMapping = array_flip($instance->terLocaleMapping);

		/**
		 * @deprecated since TYPO3 4.6, will be removed in TYPO3 4.8
		 */
		define('TYPO3_languages', implode('|', $instance->getLocales()));
	}

	/**
	 * Returns the locales.
	 *
	 * @return array
	 */
	public function getLocales() {
		return $this->locales;
	}

	/**
	 * Returns the locales as referenced by the TER and TYPO3 localization files.
	 *
	 * @return array
	 */
	public function getTerLocales() {
		return $this->convertToTerLocales($this->locales);
	}

	/**
	 * Returns the dependencies of a given locale, if any.
	 *
	 * @param string $locale
	 * @return array
	 */
	public function getLocaleDependencies($locale) {
		return isset($this->localeDependencies[$locale])
				? $this->localeDependencies[$locale]
				: array();
	}

	/**
	 * Returns the dependencies of a given locale using TER compatible locale codes.
	 *
	 * @param string $locale
	 * @return array
	 */
	public function getTerLocaleDependencies($locale) {
		$terLocale = isset($this->terLocaleReverseMapping[$locale])
				? $this->terLocaleReverseMapping[$locale]
				: $locale;
		return $this->convertToTerLocales($this->getLocaleDependencies($terLocale));
	}

	/**
	 * Converts an array of ISO locale codes into their TER equivalent.
	 *
	 * @param array $locales
	 * @return array
	 */
	protected function convertToTerLocales(array $locales) {
		$terLocales = array();
		foreach ($locales as $locale) {
			$terLocales[] = isset($this->terLocaleMapping[$locale]) ? $this->terLocaleMapping[$locale] : $locale;
		}
		return $terLocales;
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/l10n/class.t3lib_l10n_locales.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/l10n/class.t3lib_l10n_locales.php']);
}

?>