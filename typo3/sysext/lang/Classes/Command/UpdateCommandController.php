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
	 * @deprecated Use LanguageCommandController (language:update) instead. will be removed two versions after 6.2
	 */
	public function updateCommand($localesToUpdate = '') {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$this->outputLine('Calling update:update is deprecated since 6.2, use language:update instead');
		$languageCommandController = $this->objectManager->get('TYPO3\\CMS\\Lang\\Command\\LanguageCommandController');
		$languageCommandController->updateCommand($localesToUpdate);
	}
}
