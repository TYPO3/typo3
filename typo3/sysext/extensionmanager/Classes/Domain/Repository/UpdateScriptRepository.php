<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Francois Suter, <francois.suter@typo3.org>
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
 * A repository for extension update script
 *
 * @author Francois Suter <francois.suter@typo3.org>
 * @author Nicole Cordes <typo3@cordes.co>
 */
class UpdateScriptRepository {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Find an extension's update script (if any) and return it as a model object
	 *
	 * @param array $extension Array with extension information
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\UpdateScript
	 */
	public function findByExtension(array $extension) {
		if ($this->updateFileExists($extension)) {
			require_once(PATH_site . $extension['siteRelPath'] . '/class.ext_update.php');
			if (class_exists('ext_update')) {
				/** @var $updateScript \TYPO3\CMS\Extensionmanager\Domain\Model\UpdateScript */
				$updateScript = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\UpdateScript');
				$updateObject = new \ext_update;

				$content = '';
				// Check if an update is needed at all
				if ($this->needsUpdateScript($updateObject)) {
					if (method_exists($updateObject, 'main')) {
						$content = $updateObject->main();
					}
				}
				// If the content is empty, issue message that no update is needed
				if (empty($content)) {
					$content = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('updateScript.none', 'extensionmanager');
				}
				// Set the content and return the object
				$updateScript->setContent($content);
				return $updateScript;
			}
		}

		return NULL;
	}

	/**
	 * Checks if an update class file exists
	 *
	 * @param array $extension Array with extension information
	 * @return boolean
	 */
	protected function updateFileExists(array $extension) {
		return file_exists(PATH_site . $extension['siteRelPath'] . '/class.ext_update.php');
	}

	/**
	 * Checks if the update script needs to run
	 *
	 * @param object $updateObject The update script object
	 * @return boolean
	 */
	protected function needsUpdateScript($updateObject) {
		// If the access method does not exist or if it exists and returns TRUE,
		// return TRUE to indicated that the update script should be run
		if (!method_exists($updateObject, 'access') || $updateObject->access()) {
			return TRUE;
		}
		return FALSE;
	}
}

?>