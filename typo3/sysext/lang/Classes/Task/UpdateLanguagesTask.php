<?php
namespace TYPO3\CMS\Lang\Task;

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
 * Update languages translation task
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
class UpdateLanguagesTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Public method, called by scheduler.
	 *
	 * @return boolean TRUE on success
	 */
	public function execute() {
		// Throws exceptions if something went wrong
		$this->updateLanguages();

		return TRUE;
	}

	/**
	 * Update language file for each extension
	 *
	 * @return void
	 */
	protected function updateLanguages() {
		/** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		/** @var $listUtility \TYPO3\CMS\Extensionmanager\Utility\ListUtility */
		$listUtility = $objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ListUtility');
		$availableExtensions = $listUtility->getAvailableExtensions();
		$extensions = $listUtility->getAvailableAndInstalledExtensions($availableExtensions);

		/** @var \TYPO3\CMS\Lang\Service\UpdateTranslationService */
		$updateTranslationService = $objectManager->get('TYPO3\\CMS\\Lang\Service\\UpdateTranslationService');
		/** @var \TYPO3\CMS\Lang\Domain\Repository\LanguageRepository */
		$languageRepository = $objectManager->get('TYPO3\\CMS\\Lang\\Domain\\Repository\\LanguageRepository');

		$locales = array();
		$languages = $languageRepository->findSelected();
		foreach ($languages as $language) {
			$locales[] = $language->getLocale();
		}

		foreach ($extensions as $extension) {
			$updateTranslationService->updateTranslation($extension['key'], $locales);
		}
	}

}
?>