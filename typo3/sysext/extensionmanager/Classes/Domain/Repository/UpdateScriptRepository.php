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
	 * @param array $extension array with extension information
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\UpdateScript
	 */
	public function findByExtension(array $extension) {
		// Check if an "update" class file exists. If yes, require and instantiate.
		if (file_exists(PATH_site . $extension['siteRelPath'] . '/class.ext_update.php')) {
			require_once(PATH_site . $extension['siteRelPath'] . '/class.ext_update.php');
			if (class_exists('ext_update')) {
				/** @var $updateScript \TYPO3\CMS\Extensionmanager\Domain\Model\UpdateScript */
				$updateScript = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\UpdateScript');
				$updateObject = new \ext_update;

				$content = '';
				// Check if an update is needed and try to get content from the update script
				if (!method_exists($updateObject, 'access') || $updateObject->access()) {
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
}

?>