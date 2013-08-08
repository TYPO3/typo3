<?php
namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class LocaleUtility {

	/**
	 * The locale records fetched from the database
	 *
	 * @var array
	 */
	protected static $locales;

	/**
	 * @var array
	 */
	protected static $fallbackChains;

	/**
	 * Returns the uid of the language record belonging to the given locale.
	 *
	 * @param $locale
	 * @return int
	 */
	public static function getLanguageUidForLocale($locale) {
		$languageRecord = self::getLocaleRecord($locale);

		return $languageRecord['uid'];
	}

	/**
	 * Brings a locale to the form mandated by RFC 1766 (https://tools.ietf.org/html/rfc1766):
	 * All lowercase, separated by a "-" and no whitespace around it.
	 *
	 * @param string $locale
	 * @return string
	 */
	public static function canonicalizeLocale($locale) {
		$locale = trim($locale);
		$locale = str_replace('_', '-', strtolower($locale));

		return $locale;
	}

	/**
	 * Returns all available locale records, indexed by their locale
	 *
	 * @return array
	 */
	public static function getLocaleRecords() {
		if (self::$locales === NULL) {
			self::loadLocaleCache();
		}

		return self::$locales;
	}

	/**
	 * Returns the database record for the given locale
	 *
	 * @param string $locale
	 * @return array
	 */
	public static function getLocaleRecord($locale) {
		if (self::$locales === NULL) {
			self::loadLocaleCache();
		}

		return self::$locales[$locale];
	}

	/**
	 * Returns the chain of fallbacks for a given locale.
	 *
	 * @param string $locale
	 */
	public static function getFallbackChainForLocale($locale) {
		if (!self::$fallbackChains[$locale]) {
			self::$fallbackChains[$locale] = array();
			$recordUid = self::getLanguageUidForLocale($locale);
			while (TRUE) {
				$languageRecord = self::getLanguageRecord($recordUid);
				if ($languageRecord['fallback'] === 0 || $languageRecord['locale'] === ''
					|| in_array($languageRecord['locale'], self::$fallbackChains[$locale])) {

					break;
				}

				self::$fallbackChains[$locale][] = $languageRecord['locale'];
				$recordUid = $languageRecord['uid'];
			}
		}

		return self::$fallbackChains[$locale];
	}

	/**
	 * Returns the sys_language record for the given uid
	 *
	 * @param $languageId
	 * @return array
	 */
	public static function getLanguageRecord($languageId) {
		foreach (self::$locales as $languageRecord) {
			if ($languageRecord['uid'] == $languageId) {
				return $languageRecord;
			}
		}
	}

	/**
	 * Loads the sys_language records from the database and populates this classes internal
	 * locale cache
	 */
	protected static function loadLocaleCache() {
		$sysLanguageRecords = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'sys_language', 'locale != ""');

		foreach ($sysLanguageRecords as $sysLanguage) {
			self::$locales[$sysLanguage['locale']] = $sysLanguage;
		}
	}
}