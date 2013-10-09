<?php
namespace TYPO3\CMS\Lang\Command;

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
 * Language command controller updates translation packages
 */
class LanguageCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController {

	/**
	 * @var \TYPO3\CMS\Core\Package\PackageManager
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 * @inject
	 */
	protected $signalSlotDispatcher;

	/**
	 * Update language file for each extension
	 *
	 * @param string $localesToUpdate Comma separated list of locales that needs to be updated
	 * @return void
	 * @deprecated Use LanguageCommandController (language:update) instead. will be removed two versions after 6.2
	 */
	public function updateCommand($localesToUpdate = '') {
		/** @var $updateTranslationService \TYPO3\CMS\Lang\Service\UpdateTranslationService */
		$updateTranslationService = $this->objectManager->get('TYPO3\\CMS\\Lang\Service\\UpdateTranslationService');
		/** @var $languageRepository \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository */
		$languageRepository = $this->objectManager->get('TYPO3\\CMS\\Lang\\Domain\\Repository\\LanguageRepository');

		$locales = array();
		if (!empty($localesToUpdate)) {
			$locales = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $localesToUpdate, TRUE);
		} else {
			$languages = $languageRepository->findSelected();
			foreach ($languages as $language) {
				/** @var $language \TYPO3\CMS\Lang\Domain\Model\Language */
				$locales[] = $language->getLocale();
			}
		}
		$this->packageManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Package\\PackageManager');
		$this->emitPackagesMayHaveChanged();
		foreach ($this->packageManager->getAvailablePackages() as $package) {
			$updateTranslationService->updateTranslation($package->getPackageKey(), $locales);
		}
	}

	/**
	 * Emits packages may have changed signal
	 */
	protected function emitPackagesMayHaveChanged() {
		$this->signalSlotDispatcher->dispatch('PackageManagement', 'packagesMayHaveChanged');
	}
}
