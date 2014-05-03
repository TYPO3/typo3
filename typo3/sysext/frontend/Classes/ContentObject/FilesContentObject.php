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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Contains FILES content object
 *
 * @author Ingmar Schlecht <ingmar@typo3.org>
 */
class FilesContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileCollectionRepository|NULL
	 */
	protected $collectionRepository = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceFactory|NULL
	 */
	protected $fileFactory = NULL;

	/**
	 * @var \TYPO3\CMS\Core\Resource\FileRepository|NULL
	 */
	protected $fileRepository = NULL;

	/**
	 * Rendering the cObject FILES
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
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
			$referencesUid = $this->cObj->stdWrapValue('references', $conf);
			$referencesUidArray = GeneralUtility::intExplode(',', $referencesUid, TRUE);
			foreach ($referencesUidArray as $referenceUid) {
				try {
					$this->addToArray(
						$this->getFileFactory()->getFileReferenceObject($referenceUid),
						$fileObjects
					);
				} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
					/** @var \TYPO3\CMS\Core\Log\Logger $logger */
					$logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
					$logger->warning('The file-reference with uid  "' . $referenceUid . '" could not be found and won\'t be included in frontend output');
				}
			}

			// It's important that this always stays "fieldName" and not be renamed to "field" as it would otherwise collide with the stdWrap key of that name
			if (!empty($conf['references.'])) {
				$referencesFieldName = $this->cObj->stdWrapValue('fieldName', $conf['references.']);
				if ($referencesFieldName) {
					$table = $this->cObj->getCurrentTable();
					if ($table === 'pages' && isset($this->cObj->data['_LOCALIZED_UID']) && (int)$this->cObj->data['sys_language_uid'] > 0) {
						$table = 'pages_language_overlay';
					}
					$referencesForeignTable = $this->cObj->stdWrapValue('table', $conf['references.'], $table);
					$referencesForeignUid = $this->cObj->stdWrapValue('uid', $conf['references.'], isset($this->cObj->data['_LOCALIZED_UID']) ? $this->cObj->data['_LOCALIZED_UID'] : $this->cObj->data['uid']);
					$this->addToArray($this->getFileRepository()->findByRelation($referencesForeignTable, $referencesFieldName, $referencesForeignUid), $fileObjects);
				}
			}
		}
		if ($conf['files'] || $conf['files.']) {
			/*
			The TypoScript could look like this:
			# with sys_file UIDs:
			files = 12,14,15# using stdWrap:
			files.field = some_field
			 */
			$fileUids = GeneralUtility::intExplode(',', $this->cObj->stdWrapValue('files', $conf), TRUE);
			foreach ($fileUids as $fileUid) {
				try {
					$this->addToArray($this->getFileFactory()->getFileObject($fileUid), $fileObjects);
				} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
					/** @var \TYPO3\CMS\Core\Log\Logger $logger */
					$logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
					$logger->warning('The file with uid  "' . $fileUid . '" could not be found and won\'t be included in frontend output');
				}
			}
		}
		if ($conf['collections'] || $conf['collections.']) {
			$collectionUids = GeneralUtility::intExplode(',', $this->cObj->stdWrapValue('collections', $conf), TRUE);
			foreach ($collectionUids as $collectionUid) {
				try {
					$fileCollection = $this->getCollectionRepository()->findByUid($collectionUid);
					if ($fileCollection instanceof \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection) {
						$fileCollection->loadContents();
						$this->addToArray($fileCollection->getItems(), $fileObjects);
					}
				} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
					/** @var \TYPO3\CMS\Core\Log\Logger $logger */
					$logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
					$logger->warning('The file-collection with uid  "' . $collectionUid . '" could not be found or contents could not be loaded and won\'t be included in frontend output');
				}
			}
		}
		if ($conf['folders'] || $conf['folders.']) {
			$folderIdentifiers = GeneralUtility::trimExplode(',', $this->cObj->stdWrapValue('folders', $conf));
			foreach ($folderIdentifiers as $folderIdentifier) {
				if ($folderIdentifier) {
					try {
						$folder = $this->getFileFactory()->getFolderObjectFromCombinedIdentifier($folderIdentifier);
						if ($folder instanceof \TYPO3\CMS\Core\Resource\Folder) {
							$this->addToArray(array_values($folder->getFiles()), $fileObjects);
						}
					} catch (\TYPO3\CMS\Core\Resource\Exception $e) {
						/** @var \TYPO3\CMS\Core\Log\Logger $logger */
						$logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
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
			$sortingProperty = $this->cObj->stdWrapValue('sorting', $conf);
		}
		if ($sortingProperty !== '' && count($fileObjects) > 1) {
			@usort($fileObjects, function(\TYPO3\CMS\Core\Resource\FileInterface $a, \TYPO3\CMS\Core\Resource\FileInterface $b) use($sortingProperty) {
				if ($a->hasProperty($sortingProperty) && $b->hasProperty($sortingProperty)) {
					return strnatcasecmp($a->getProperty($sortingProperty), $b->getProperty($sortingProperty));
				} else {
					return 0;
				}
			});
			if (is_array($conf['sorting.']) && isset($conf['sorting.']['direction']) && strtolower($conf['sorting.']['direction']) === 'desc') {
				$fileObjects = array_reverse($fileObjects);
			}
		}

		$availableFileObjectCount = count($fileObjects);

		$start = 0;
		if (!empty($conf['begin'])) {
			$start = (int)$conf['begin'];
		}
		if (!empty($conf['begin.'])) {
			$start = (int)$this->cObj->stdWrap($start, $conf['begin.']);
		}
		$start = MathUtility::forceIntegerInRange($start, 0, $availableFileObjectCount);

		$limit = $availableFileObjectCount;
		if (!empty($conf['maxItems'])) {
			$limit = (int)$conf['maxItems'];
		}
		if (!empty($conf['maxItems.'])) {
			$limit = (int)$this->cObj->stdWrap($limit, $conf['maxItems.']);
		}

		$end = MathUtility::forceIntegerInRange($start + $limit, $start, $availableFileObjectCount);

		$GLOBALS['TSFE']->register['FILES_COUNT'] = min($limit, $availableFileObjectCount);
		$fileObjectCounter = 0;
		$keys = array_keys($fileObjects);
		for ($i = $start; $i < $end; $i++) {
			$key = $keys[$i];
			$fileObject = $fileObjects[$key];

			$GLOBALS['TSFE']->register['FILE_NUM_CURRENT'] = $fileObjectCounter;
			$this->cObj->setCurrentFile($fileObject);
			$content .= $this->cObj->cObjGetSingle($splitConf[$key]['renderObj'], $splitConf[$key]['renderObj.']);
			$fileObjectCounter++;
		}
		$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		return $content;
	}

	/**
	 * Sets the file factory.
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceFactory $fileFactory
	 * @return void
	 */
	public function setFileFactory($fileFactory) {
		$this->fileFactory = $fileFactory;
	}

	/**
	 * Returns the file factory.
	 *
	 * @return \TYPO3\CMS\Core\Resource\ResourceFactory
	 */
	public function getFileFactory() {
		if ($this->fileFactory === NULL) {
			$this->fileFactory = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
		}

		return $this->fileFactory;
	}

	/**
	 * Sets the file repository.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileRepository $fileRepository
	 * @return void
	 */
	public function setFileRepository($fileRepository) {
		$this->fileRepository = $fileRepository;
	}

	/**
	 * Returns the file repository.
	 *
	 * @return \TYPO3\CMS\Core\Resource\FileRepository
	 */
	public function getFileRepository() {
		if ($this->fileRepository === NULL) {
			$this->fileRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		}

		return $this->fileRepository;
	}

	/**
	 * Sets the collection repository.
	 *
	 * @param \TYPO3\CMS\Core\Resource\FileCollectionRepository $collectionRepository
	 * @return void
	 */
	public function setCollectionRepository($collectionRepository) {
		$this->collectionRepository = $collectionRepository;
	}

	/**
	 * Returns the collection repository.
	 *
	 * @return \TYPO3\CMS\Core\Resource\FileCollectionRepository
	 */
	public function getCollectionRepository() {
		if ($this->collectionRepository === NULL) {
			$this->collectionRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileCollectionRepository');
		}

		return $this->collectionRepository;
	}

	/**
	 * Adds $newItems to $theArray, which is passed by reference. Array must only consist of numerical keys.
	 *
	 * @param mixed $newItems Array with new items or single object that's added.
	 * @param array $theArray The array the new items should be added to. Must only contain numeric keys (for array_merge() to add items instead of replacing).
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
	 * @deprecated since TYPO3 CMS 6.2, use ContentObjectRenderer::stdWrapValue() instead. Will be removed two versions later.
	 */
	protected function stdWrapValue($key, array $config, $defaultValue = '') {
		return $this->cObj->stdWrapValue($key, $config, $defaultValue);
	}

}
