<?php
namespace TYPO3\CMS\Lang\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Wouter Wolters <typo3@wouterwolters.nl>
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
 * Update languages service
 */
class UpdateTranslationService {

	/**
	 * Status codes for AJAX response
	 */
	const TRANSLATION_NOT_AVAILABLE = 0;
	const TRANSLATION_AVAILABLE = 1;
	const TRANSLATION_FAILED = 2;
	const TRANSLATION_OK = 3;
	const TRANSLATION_INVALID = 4;
	const TRANSLATION_UPDATED = 5;

	/**
	 * @var \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository
	 * @inject
	 */
	protected $languageRepository;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Utility\Repository\Helper
	 * @inject
	 */
	protected $repositoryHelper;

	/**
	 * @var \TYPO3\CMS\Lang\Utility\Connection\Ter
	 * @inject
	 */
	protected $terConnection;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * @var array
	 */
	protected $translationStates = array();

	/**
	 * Update translation for given extension
	 *
	 * @param string $extension The extension key
	 * @param string $locales Comma separated list of locales to update
	 * @return array
	 */
	public function updateTranslation($extension, $locales) {
		if (is_string($locales)) {
			$locales = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $locales);
		}
		$locales = array_flip((array) $locales);

		foreach ($locales as $locale => $key) {
			$state = static::TRANSLATION_INVALID;
			try {
				$state = $this->getTranslationStateForExtension($extension, $locale);
				if ($state === static::TRANSLATION_AVAILABLE) {
					$state = $this->updateTranslationForExtension($extension, $locale);
				}
			} catch (\Exception $exception) {
				$error = $exception->getMessage();
			}
			$locales[$locale] = array(
				'state'  => $state,
				'error'  => $error,
			);
		}
		return $locales;
	}

	/**
	 * Returns the translation state for an extension
	 *
	 * @param string $extensionKey The extension key
	 * @param string $locale Locale to return
	 * @return integer Translation state
	 */
	protected function getTranslationStateForExtension($extensionKey, $locale) {
		if (empty($extensionKey) || empty($locale)) {
			return static::TRANSLATION_INVALID;
		}

		$identifier = $extensionKey . '-' . $locale;
		if (isset($this->translationStates[$identifier])) {
			return $this->translationStates[$identifier];
		}

		$selectedLanguages = $this->languageRepository->findSelected();
		if (empty($selectedLanguages) || !is_array($selectedLanguages)) {
			return static::TRANSLATION_INVALID;
		}

		$mirrorUrl = $this->getMirrorUrl($extensionKey);
		$status = $this->terConnection->fetchTranslationStatus($extensionKey, $mirrorUrl);

		foreach ($selectedLanguages as $language) {
			$stateLocale = $language->getLocale();
			$stateIdentifier = $extensionKey . '-' . $stateLocale;
			$this->translationStates[$stateIdentifier] = static::TRANSLATION_INVALID;

			if (empty($status[$stateLocale]) || !is_array($status[$stateLocale])) {
				$this->translationStates[$stateIdentifier] = static::TRANSLATION_NOT_AVAILABLE;
				continue;
			}

			$md5 = $this->getTranslationFileMd5($extensionKey, $stateLocale);
			if ($md5 !== $status[$stateLocale]['md5']) {
				$this->translationStates[$stateIdentifier] = static::TRANSLATION_AVAILABLE;
				continue;
			}

			$this->translationStates[$stateIdentifier] = static::TRANSLATION_OK;
		}

		return $this->translationStates[$identifier];
	}

	/**
	 * Returns the md5 of a translation file
	 *
	 * @param string $extensionKey The extension key
	 * @param string $locale The locale
	 * @return string The md5 value
	 */
	protected function getTranslationFileMd5($extensionKey, $locale) {
		if (empty($extensionKey) || empty($locale)) {
			return '';
		}
		$fileName = PATH_site . 'typo3temp' . DIRECTORY_SEPARATOR . $extensionKey . '-l10n-' . $locale . '.zip';
		if (is_file($fileName)) {
			return md5_file($fileName);
		}
		return '';
	}

	/**
	 * Update the translation for an extension
	 *
	 * @param string $extensionKey The extension key
	 * @param string $locale Locale to update
	 * @return integer Translation state
	 */
	protected function updateTranslationForExtension($extensionKey, $locale) {
		if (empty($extensionKey) || empty($locale)) {
			return static::TRANSLATION_INVALID;
		}

		$state = static::TRANSLATION_FAILED;
		$mirrorUrl = $this->getMirrorUrl($extensionKey);
		$updateResult = $this->terConnection->updateTranslation($extensionKey, $locale, $mirrorUrl);
		if ($updateResult === TRUE) {
			$state = static::TRANSLATION_UPDATED;
		}

		return $state;
	}

	/**
	 * Returns the mirror URL for a given extension.
	 *
	 * @param string $extensionKey
	 * @return string
	 */
	protected function getMirrorUrl($extensionKey) {
		$mirrorUrl = $this->repositoryHelper->getMirrors()->getMirrorUrl();

		$this->signalSlotDispatcher->dispatch(
			__CLASS__,
			'postProcessMirrorUrl',
			array(
				'extensionKey' => $extensionKey,
				'mirrorUrl' => &$mirrorUrl,
			)
		);

		return $mirrorUrl;
	}

}
