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
 * Update languages translation command
 */
class UpdateCommandController extends \TYPO3\CMS\Extbase\Mvc\Controller\CommandController {

	/**
	 * Update language file for each extension
	 *
	 * @param string $localesToUpdate Comma separated list of locales that needs to be updated
	 * @return void
	 */
	public function updateCommand($localesToUpdate = '') {
		/** @var $listUtility \TYPO3\CMS\Extensionmanager\Utility\ListUtility */
		$listUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$availableExtensions = $listUtility->getAvailableExtensions();
		$extensions = $listUtility->getAvailableAndInstalledExtensions($availableExtensions);

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
				$locales[] = $language->getLocale();
			}
		}

		foreach ($extensions as $extension) {
			$updateTranslationService->updateTranslation($extension['key'], $locales);
		}
	}

}
