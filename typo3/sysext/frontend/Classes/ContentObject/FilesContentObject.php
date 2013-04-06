<?php
namespace TYPO3\CMS\Frontend\ContentObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Ingmar Schlecht <ingmar@typo3.org>
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
/**
 * Contains FILES content object
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class FilesContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject FILES
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		/** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
		$fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileObjects = array();
		// Getting the files
		if ($conf['references'] || $conf['references.']) {
			/*
			The TypoScript could look like this:# all items related to the page.media field:
			references {
			table = pages
			uid.data = page:uid
			fieldName = media
			}# or: sys_file_references with uid 27:
			references = 27
			 */
			$referencesUid = $this->stdWrapValue('references', $conf);
			$referencesUidArray = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $referencesUid, TRUE);
			foreach ($referencesUidArray as $referenceUid) {
				try {
					$this->addToArray($fileRepository->findFileReferenceByUid($referenceUid), $fileObjects);
				} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
					/** @var \TYPO3\CMS\Core\Log\Logger $logger */
					$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger();
					$logger->warning('The file-reference with uid  "' . $referenceUid . '" could not be found and won\'t be included in frontend output');
				}
			}

			// It's important that this always stays "fieldName" and not be renamed to "field" as it would otherwise collide with the stdWrap key of that name
			$referencesFieldName = $this->stdWrapValue('fieldName', $conf['references.']);
			if ($referencesFieldName) {
				$table = $this->cObj->getCurrentTable();
				if ($table === 'pages' && isset($this->cObj->data['_LOCALIZED_UID']) && intval($this->cObj->data['sys_language_uid']) > 0) {
					$table = 'pages_language_overlay';
				}
				$referencesForeignTable = $this->stdWrapValue('table', $conf['references.'], $table);
				$referencesForeignUid = $this->stdWrapValue('uid', $conf['references.'], isset($this->cObj->data['_LOCALIZED_UID']) ? $this->cObj->data['_LOCALIZED_UID'] : $this->cObj->data['uid']);
				$this->addToArray($fileRepository->findByRelation($referencesForeignTable, $referencesFieldName, $referencesForeignUid), $fileObjects);
			}
		}
		if ($conf['files'] || $conf['files.']) {
			/*
			The TypoScript could look like this:
			# with sys_file UIDs:
			files = 12,14,15# using stdWrap:
			files.field = some_field
			 */
			$fileUids = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->stdWrapValue('files', $conf), TRUE);
			foreach ($fileUids as $fileUid) {
				try {
					$this->addToArray($fileRepository->findByUid($fileUid), $fileObjects);
				} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
					/** @var \TYPO3\CMS\Core\Log\Logger $logger */
					$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger();
					$logger->warning('The file with uid  "' . $fileUid . '" could not be found and won\'t be included in frontend output');
				}
			}
		}
		if ($conf['collections'] || $conf['collections.']) {
			$collectionUids = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->stdWrapValue('collections', $conf), TRUE);
			/** @var \TYPO3\CMS\Core\Resource\FileCollectionRepository $collectionRepository */
			$collectionRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileCollectionRepository');
			foreach ($collectionUids as $collectionUid) {
				try {
					$fileCollection = $collectionRepository->findByUid($collectionUid);
					if ($fileCollection instanceof \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection) {
						$fileCollection->loadContents();
						$this->addToArray($fileCollection->getItems(), $fileObjects);
					}
				} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
					/** @var \TYPO3\CMS\Core\Log\Logger $logger */
					$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger();
					$logger->warning('The file-collection with uid  "' . $collectionUid . '" could not be found or contents could not be loaded and won\'t be included in frontend output');
				}
			}
		}
		if ($conf['folders'] || $conf['folders.']) {
			$folderIdentifiers = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->stdWrapValue('folders', $conf));
			/** @var \TYPO3\CMS\Core\Resource\ResourceFactory $fileFactory */
			$fileFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
			foreach ($folderIdentifiers as $folderIdentifier) {
				if ($folderIdentifier) {
					try {
						$folder = $fileFactory->getFolderObjectFromCombinedIdentifier($folderIdentifier);
						if ($folder instanceof \TYPO3\CMS\Core\Resource\Folder) {
							$this->addToArray($folder->getFiles(), $fileObjects);
						}
					} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
						/** @var \TYPO3\CMS\Core\Log\Logger $logger */
						$logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Core\Log\LogManager')->getLogger();
						$logger->warning('The folder with identifier  "' . $folderIdentifier . '" could not be found and won\'t be included in frontend output');
					}
				}
			}
		}
		// Rendering the files
		$content = '';
		// optionSplit applied to conf to allow differnt settings per file
		$splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($conf, count($fileObjects));
		// Enable sorting for multiple fileObjects
		$sortingProperty = '';
		if ($conf['sorting'] || $conf['sorting.']) {
			$sortingProperty = $this->stdWrapValue('sorting', $conf);
		}
		if ($sortingProperty !== '' && count($fileObjects) > 1) {
			usort($fileObjects, function(\TYPO3\CMS\Core\Resource\FileInterface $a, \TYPO3\CMS\Core\Resource\FileInterface $b) use($sortingProperty) {
				if ($a->hasProperty($sortingProperty) && $b->hasProperty($sortingProperty)) {
					return strnatcasecmp($a->getProperty($sortingProperty), $b->getProperty($sortingProperty));
				} else {
					return 0;
				}
			});
		}
		foreach ($fileObjects as $key => $fileObject) {
			$this->cObj->setCurrentFile($fileObject);
			$content .= $this->cObj->cObjGetSingle($splitConf[$key]['renderObj'], $splitConf[$key]['renderObj.']);
		}
		$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		return $content;
	}

	/**
	 * Adds $newItems to $theArray, which is passed by reference. Array must only consist of numerical keys.
	 *
	 * @param mixed	$newItems Array with new items or single object that's added.
	 * @param array	$theArray The array the new items should be added to. Must only contain numeric keys (for array_merge() to add items instead of replacing).
	 */
	protected function addToArray($newItems, array &$theArray) {
		if (is_array($newItems)) {
			$theArray = array_merge($theArray, $newItems);
		} elseif (is_object($newItems)) {
			$theArray[] = $newItems;
		}
	}

	/**
	 * Gets a configuration value by passing them through stdWrap first and taking a default value if stdWrap doesn't yield a result.
	 *
	 * @param string $key The config variable key (from TS array).
	 * @param array $config The TypoScript array.
	 * @param string $defaultValue Optional default value.
	 * @return string Value of the config variable
	 */
	protected function stdWrapValue($key, array $config, $defaultValue = '') {
		return $this->cObj->stdWrap($config[$key], $config[$key . '.']) ? $this->cObj->stdWrap($config[$key], $config[$key . '.']) : $defaultValue;
	}

}


?>