<?php
namespace TYPO3\CMS\Backend\Backend\Avatar;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\ProcessedFile;

/**
 * Default Avatar class
 */
class Avatar {

	/**
	 * @var array
	 */
	protected $defaultConfiguration = array();

	/**
	 * Render avatar tag
	 *
	 * @param array $backendUser
	 * @param int $size width and height of the image
	 * @param bool $showIcon show the record icon
	 * @return string
	 */
	public function render(array $backendUser = NULL, $size = 32, $showIcon = FALSE) {
		if (!is_array($backendUser)) {
			$backendUser = $this->getBackendUser()->user;
		}

		$fileUid = $this->getAvatarFileUid($backendUser['uid']);

		// Get file object
		try {
			$file = ResourceFactory::getInstance()->getFileObject($fileUid);
			$processedImage = $file->process(
				ProcessedFile::CONTEXT_IMAGECROPSCALEMASK,
				array('width' => $size . 'c', 'height' => $size . 'c')
			);
			$imageUri = $processedImage->getPublicUrl(TRUE);

			// Image
			$image = '<img src="' . htmlspecialchars($imageUri) . '"' .
				'width="' . $processedImage->getProperty('width') . '" ' .
				'height="' . $processedImage->getProperty('height') . '">';
		} catch (FileDoesNotExistException $e) {
			// don't show an image
			$image = '';
		}

		// Icon
		$icon = '';
		if ($showIcon) {
			$icon = '<span class="avatar-icon">' . IconUtility::getSpriteIconForRecord('be_users', $backendUser) . '</span>';
		}

		return '<span class="avatar"><span class="avatar-image">' . $image . '</span>' . $icon . '</span>';
	}

	/**
	 * Get Avatar fileUid
	 *
	 * @param int $beUserId
	 * @return int
	 */
	protected function getAvatarFileUid($beUserId) {
		$file = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'uid_local',
			'sys_file_reference',
			'tablenames = \'be_users\' AND fieldname = \'avatar\' AND ' .
			'table_local = \'sys_file\' AND uid_foreign = ' . (int)$beUserId .
			BackendUtility::BEenableFields('sys_file_reference') . BackendUtility::deleteClause('sys_file_reference')
		);
		return $file ? $file['uid_local'] : 0;
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
