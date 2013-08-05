<?php
namespace TYPO3\CMS\Core\Utility;

use TYPO3\CMS\Backend\Utility\BackendUtility;

class LocaleUtility {

	/**
	 * @param $locale
	 * @return array
	 */
	public static function getLanguageRecordForLocale($locale) {
		return BackendUtility::getRecordRaw('sys_language', 'locale = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($locale, 'sys_language'));
	}

	/**
	 * @param $locale
	 * @return int
	 */
	public static function getLanguageUidForLocale($locale) {
		$languageRecord = self::getLanguageRecordForLocale($locale);

		return $languageRecord['uid'];
	}

	/**
	 *
	 */
//	public static function get
}