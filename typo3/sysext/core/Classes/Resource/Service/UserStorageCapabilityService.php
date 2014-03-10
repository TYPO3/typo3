<?php
namespace TYPO3\CMS\Core\Resource\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Fabien Udriot <fabien.udriot@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Utility class to render capabilities of the storage.
 */
class UserStorageCapabilityService {

	/**
	 * UserFunc function for rendering field "is_public".
	 * There are some edge cases where "is_public" can never be marked as true in the BE,
	 * for instance for storage located outside the document root or
	 * for storages driven by special driver such as Flickr, ...
	 *
	 * @param array $propertyArray the array with additional configuration options.
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj the TCEforms parent object
	 * @return string
	 */
	public function renderIsPublic(array $propertyArray, \TYPO3\CMS\Backend\Form\FormEngine $tceformsObj) {

		$isPublic = $GLOBALS['TCA']['sys_file_storage']['columns']['is_public']['config']['default'];
		$fileRecord = $propertyArray['row'];

		// Makes sure the storage object can be retrieved which is not the case when new storage.
		if ((int)$propertyArray['row']['uid'] > 0) {
			$storage = ResourceFactory::getInstance()->getStorageObject($fileRecord['uid']);
			$storageRecord = $storage->getStorageRecord();
			$isPublic = $storage->isPublic();

			// Display a warning to the BE User in case settings is not inline with storage capability.
			if ($storageRecord['is_public'] != $storage->isPublic()) {
				$message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.message.storage_is_no_public'),
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.header.storage_is_no_public'),
					\TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
				);

				\TYPO3\CMS\Core\Messaging\FlashMessageQueue::addMessage($message);
			}
		}

		return $this->renderFileInformationContent($fileRecord, $isPublic);
	}

	/**
	 * Renders a HTML block containing the checkbox for field "is_public".
	 *
	 * @param array $fileRecord
	 * @param bool $isPublic
	 * @return string
	 */
	protected function renderFileInformationContent(array $fileRecord, $isPublic) {
		$template = '
		<div class="t3-form-field-item">
			<input name="data[sys_file_storage][{uid}][is_public]" value="0" type="hidden">
			<input class="checkbox" value="1" name="data[sys_file_storage][{uid}][is_public]_0" type="checkbox" %s>
		</div>';

		$content = sprintf($template,
			$isPublic ? 'checked="checked"' : ''
		);

		return str_replace('{uid}', $fileRecord['uid'], $content);
	}

}
