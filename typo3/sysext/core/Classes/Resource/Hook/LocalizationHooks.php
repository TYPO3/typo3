<?php
namespace TYPO3\CMS\Core\Resource\Hook;

use TYPO3\CMS\Core\Utility\LocaleUtility;

class LocalizationHooks {
	/**
	 * Hook to fill the locale list in the sys_file_metadata records.
	 *
	 * This could also be in a more generic place, as it will be required for all locale fields.
	 *
	 * @param array $parameters
	 */
	public function fillLocaleList($parameters) {
		$locales = LocaleUtility::getLocaleRecords();

		$localeItems = array();
		foreach ($locales as $key => $locale) {
			$localeItems[$locale['title']] = array($locale['title'], $key);
		}
		// we want the locale items to be sorted alphabetically
		ksort($localeItems);

		$parameters['items'] = array_merge($parameters['items'], array_values($localeItems));
	}
}