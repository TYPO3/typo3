<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Ingmar Schlecht <ingmar@typo3.org>
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
class tslib_content_Files extends tslib_content_Abstract {
	/**
	 * Rendering the cObject FILES
	 *
	 * @param array Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		/** @var t3lib_file_Repository_FileRepository $fileRepository */
		$fileRepository = t3lib_div::makeInstance('t3lib_file_Repository_FileRepository');

		$fileObjects = array();

			// Getting the files
		if ($conf['references'] || $conf['references.']) {
			/*
			The TypoScript could look like this:

			# all items related to the page.media field:
			references {
				table = pages
				uid.data = page:uid
				fieldName = media
			}

			# or: sys_file_references with uid 27:
			references = 27
			*/

			$referencesUid = $this->stdWrapValue('references', $conf);
			if ($referencesUid) {
				$this->addToArray($fileRepository->findFileReferenceByUid($referencesUid), $fileObjects);
			}

				// It's important that this always stays "fieldName" and not be renamed to "field" as it would otherwise collide with the stdWrap key of that name
			$referencesFieldName = $this->stdWrapValue('fieldName', $conf['references.']);
			if ($referencesFieldName) {
				$referencesForeignTable = $this->stdWrapValue('table', $conf['references.'], $this->cObj->getCurrentTable());
				$referencesForeignUid = $this->stdWrapValue('uid', $conf['references.'], $this->cObj->data['uid']);

				$this->addToArray($fileRepository->findByRelation($referencesForeignTable, $referencesFieldName, $referencesForeignUid), $fileObjects);
			}
		}

		if ($conf['files'] || $conf['files.']) {
			/*
			The TypoScript could look like this:
			# with sys_file UIDs:
			files = 12,14,15

			# using stdWrap:
			files.field = some_field
			*/

			$fileUids = t3lib_div::trimExplode(',', $this->stdWrapValue('files', $conf), TRUE);
			foreach ($fileUids as $fileUid) {
				$this->addToArray($fileRepository->findByUid($fileUid), $fileObjects);
			}
		}

		if ($conf['collections'] || $conf['collections.']) {
			$collectionUids = t3lib_div::trimExplode(',', $this->stdWrapValue('collections', $conf), TRUE);

			/** @var t3lib_file_Repository_FileCollectionRepository $collectionRepository */
			$collectionRepository = t3lib_div::makeInstance('t3lib_file_Repository_FileCollectionRepository');

			foreach ($collectionUids as $collectionUid) {
				$fileCollection = $collectionRepository->findByUid($collectionUid);
				if ($fileCollection instanceof t3lib_file_Collection_AbstractFileCollection) {
					$fileCollection->loadContents();
					$this->addToArray($fileCollection->getItems(), $fileObjects);
				}
			}
		}

		if ($conf['folders'] || $conf['folders.']) {
			$folderIdentifiers = t3lib_div::trimExplode(',', $this->stdWrapValue('folders', $conf));

			/** @var t3lib_file_Factory $fileFactory */
			$fileFactory = t3lib_div::makeInstance('t3lib_file_Factory');

			foreach ($folderIdentifiers as $folderIdentifier) {
				if ($folderIdentifier) {
					$folder = $fileFactory->getFolderObjectFromCombinedIdentifier($folderIdentifier);
					if ($folder instanceof t3lib_file_Folder) {
						$this->addToArray($folder->getFiles(), $fileObjects);
					}
				}
			}
		}

			// Rendering the files
		$content = '';

			// optionSplit applied to conf to allow differnt settings per file
		$splitConf = $GLOBALS['TSFE']->tmpl->splitConfArray($conf, count($fileObjects));

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
	 * @param array $config	 The TypoScript array.
	 * @param string $defaultValue	Optional default value.
	 * @return string Value of the config variable
	 */
	protected function stdWrapValue($key, array $config, $defaultValue = '') {
		return $this->cObj->stdWrap($config[$key], $config[$key.'.'])?
			$this->cObj->stdWrap($config[$key], $config[$key.'.']):
			$defaultValue;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_files.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_files.php']);
}

?>