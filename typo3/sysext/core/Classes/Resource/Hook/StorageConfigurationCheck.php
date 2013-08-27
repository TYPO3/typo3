<?php
namespace TYPO3\CMS\Core\Resource\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Andreas Wolf <andreas.wolf@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\Utility\StorageUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hooks for the rendering of storage records in FormEngine.
 *
 * @author Andreas Wolf <andreas.wolf@typo3.org>
 */
class StorageConfigurationCheck {

	/**
	 * @var array
	 */
	protected $messages;

	/**
	 * Renders a configuration check for a Resource Storage record.
	 *
	 * @param array $parameters
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $formEngine
	 * @return string
	 */
	public function renderConfigurationCheck(array $parameters, \TYPO3\CMS\Backend\Form\FormEngine $formEngine) {
		$isNewRecord = !is_numeric($parameters['row']['uid']);

		/** @var ResourceFactory $resourceFactory */
		$resourceFactory = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');

		$this->messages = array();
		try {
			if (!$isNewRecord) {
				$storageObject = $resourceFactory->createStorageObject($parameters['row']);

				$this->messages[] = self::checkFileSystemCaseSensitivity($storageObject);
				$this->checkLocalStorageContainsOtherStorages($parameters['row']);
			} else {
				$this->messages[] = self::renderMessage('Record not saved', 'Save the record to have your configuration checked.', 'warning');
			}
		} catch (\Exception $e) {
			$this->messages[] = self::renderMessage(
				'Error while creating storage object',
				sprintf('Message: %s (%d)', $e->getMessage(), $e->getCode()),
				'error'
			);
		}

		return implode('', $this->messages);
	}


	/**
	 * @param array $row The database row of the current storage
	 * @return void
	 */
	protected function checkLocalStorageContainsOtherStorages(array $row) {
		if ($row['driver'] !== 'Local') {
			return;
		}

		/** @var $resourceFactory \TYPO3\CMS\Core\Resource\ResourceFactory */
		$resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		$currentConfiguration = $resourceFactory->convertFlexFormDataToConfigurationArray($row['configuration']);

		/** @var StorageRepository $storageRepository */
		$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');

		$storages = $storageRepository->findByStorageType('Local');
		foreach ($storages as $storage) {
			if ($storage->getUid() === (int)$row['uid']) {
				continue;
			}
			$configuration = $storage->getConfiguration();
			if ($currentConfiguration['pathType'] !== $configuration['pathType']) {
				continue;
			}

			if (StorageUtility::isLocalStorageContainedInOtherLocalStorage($currentConfiguration, $configuration)) {
				$this->messages[] = self::renderMessage(
					'Storage contains another storage',
					sprintf('This storage contains the storage "%s" (%d). This might lead to problems with the file indexing and should be fixed.', $storage->getName(), $storage->getUid()),
					'error'
				);
			} else if (StorageUtility::isLocalStorageContainedInOtherLocalStorage($configuration, $currentConfiguration)) {
				$this->messages[] = self::renderMessage(
					'Storage is contained in another storage',
					sprintf('This storage is contained in the storage "%s" (%d). This might lead to problems with the file indexing and should be fixed.', $storage->getName(), $storage->getUid()),
					'error'
				);
			}
		}
	}

	/**
	 * Checks if the given storage's file system distinguishes upper and lower
	 * case in file paths.
	 *
	 * @param ResourceStorage $storageObject
	 * @return array
	 */
	protected static function checkFileSystemCaseSensitivity(ResourceStorage $storageObject) {
		if ($storageObject->isWritable()) {
			$caseSensitive = StorageUtility::isFilesystemCaseSensitive($storageObject);

			$message = self::renderMessage('File system case-sensitivity', 'Your file system is' . ($caseSensitive ? '' : ' not') . ' case sensitive.', 'notice');
		} else {
			$message = self::renderMessage('Storage not writable', 'The storage is read-only, so we could not check if the underlying file system is case-sensitive or not.', 'notice');
		}

		return $message;
	}

	/**
	 * Renders a message.
	 *
	 * @param string $title
	 * @param string $text
	 * @param string $class
	 * @return string
	 */
	protected static function renderMessage($title, $text = '', $class = 'ok') {
		$class = in_array($class, array('ok', 'notice', 'warning', 'error')) ? $class : 'ok';

		// class: ok, notice, warning, error
		return sprintf('
			<div class="typo3-message message-%s"><div class="header-container">
				<div class="message-header">%s</div>
				</div>
				<div class="message-body">%s</div>
			</div>',
			$class, $title, $text
		);
	}
}