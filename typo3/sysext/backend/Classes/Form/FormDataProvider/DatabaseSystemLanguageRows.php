<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Fill the "systemLanguageRows" part of the result array
 */
class DatabaseSystemLanguageRows implements FormDataProviderInterface {

	/**
	 * Fetch available system languages and resolve iso code if necessary.
	 *
	 * @todo: This is similar to what TranslationConfigurationProvider->getSystemLanguages() does,
	 * @todo: use the method as soon as bugs have been fixed in there.
	 *
	 * @param array $result
	 * @return array
	 * @throws \UnexpectedValueException
	 */
	public function addData(array $result) {
		$database = $this->getDatabase();

		$languageRows = [
			0 => [
				'uid' => 0,
				'title' => 'Default Language',
				// Default "DEF" is a fallback preparation for flex form iso codes "lDEF"
				'iso' => 'DEF',
			],
		];

		$dbRows = $database->exec_SELECTgetRows(
			'uid,title,language_isocode,static_lang_isocode',
			'sys_language',
			'pid=0 AND hidden=0'
		);

		if ($dbRows === NULL) {
			throw new \UnexpectedValueException(
				'Database query error ' . $database->sql_error(),
				1438170741
			);
		}

		$isStaticInfoTablesLoaded = ExtensionManagementUtility::isLoaded('static_info_tables');
		foreach ($dbRows as $dbRow) {
			if (!empty($dbRow['language_isocode'])) {
				$dbRow['iso'] = $dbRow['language_isocode'];
			} elseif ($isStaticInfoTablesLoaded && !empty($dbRow['static_lang_isocode'])) {
				GeneralUtility::deprecationLog(
					'Usage of the field "static_lang_isocode" is discouraged, and will stop working with CMS 8. Use the built-in'
					. ' language field "language_isocode" in your sys_language records.'
				);
				$lg_iso_2 = BackendUtility::getRecord('static_languages', $dbRow['static_lang_isocode'], 'lg_iso_2');
				if ($lg_iso_2['lg_iso_2']) {
					$dbRow['iso'] = $lg_iso_2['lg_iso_2'];
				}
			} else {
				// No iso code could be found. This is currently possible in the system but discouraged.
				// So, code within FormEngine has to be suited to work with an empty iso code. However,
				// it may impact certain multi language scenarios, so we add a flash message hinting for
				// incomplete configuration here.
				// It might be possible to convert this to a non-catchable exception later if
				// it iso code is enforced on a different layer of the system (tca required + migration wizard).
				$message = sprintf(
					$this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:error.missingLanguageIsocode'),
					$dbRow['title'],
					$dbRow['uid']
				);
				/** @var FlashMessage $flashMessage */
				$flashMessage = GeneralUtility::makeInstance(
						FlashMessage::class,
						$message,
						'',
						FlashMessage::ERROR
				);
				/** @var $flashMessageService FlashMessageService */
				$flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
				$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
				$defaultFlashMessageQueue->enqueue($flashMessage);
				$dbRow['iso'] = '';
			}
			unset($dbRow['language_isocode']);
			unset($dbRow['static_lang_isocode']);
			$languageRows[(int)$dbRow['uid']] = $dbRow;
		}

		$result['systemLanguageRows'] = $languageRows;

		return $result;
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabase() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}
}
