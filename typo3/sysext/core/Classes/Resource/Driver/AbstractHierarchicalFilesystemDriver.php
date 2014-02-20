<?php
namespace TYPO3\CMS\Core\Resource\Driver;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class AbstractHierarchicalFilesystemDriver
 *
 * @package TYPO3\CMS\Core\Resource\Driver
 */
abstract class AbstractHierarchicalFilesystemDriver extends AbstractDriver {

	/**
	 * Wrapper for \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
	 *
	 * @param string $theFile Filepath to evaluate
	 * @return boolean TRUE if no '/', '..' or '\' is in the $theFile
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr()
	 */
	protected function isPathValid($theFile) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::validPathStr($theFile);
	}

	/**
	 * Makes sure the Path given as parameter is valid
	 *
	 * @param string $filePath The file path (including the file name!)
	 * @return string
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InvalidPathException
	 */
	protected function canonicalizeAndCheckFilePath($filePath) {
		$filePath = \TYPO3\CMS\Core\Utility\PathUtility::getCanonicalPath($filePath);

		// filePath must be valid
		// Special case is required by vfsStream in Unit Test context
		if (!$this->isPathValid($filePath) && substr($filePath, 0, 6) !== 'vfs://') {
			throw new \TYPO3\CMS\Core\Resource\Exception\InvalidPathException('File ' . $filePath . ' is not valid (".." and "//" is not allowed in path).', 1320286857);
		}
		return $filePath;
	}

	/**
	 * Makes sure the Path given as parameter is valid
	 *
	 * @param string $fileIdentifier The file path (including the file name!)
	 * @return string
	 */
	protected function canonicalizeAndCheckFileIdentifier($fileIdentifier) {
		if ($fileIdentifier !== '') {
			$fileIdentifier = $this->canonicalizeAndCheckFilePath($fileIdentifier);
			$fileIdentifier = '/' . ltrim($fileIdentifier, '/');
			if (!$this->isCaseSensitiveFileSystem()) {
				$fileIdentifier = strtolower($fileIdentifier);
			}
		}
		return $fileIdentifier;
	}

	/**
	 * Makes sure the Path given as parameter is valid
	 *
	 * @param string $folderPath The file path (including the file name!)
	 * @return string
	 */
	protected function canonicalizeAndCheckFolderIdentifier($folderPath) {
		if ($folderPath === '/') {
			$canonicalizedIdentifier = $folderPath;
		} else {
			$canonicalizedIdentifier = $this->canonicalizeAndCheckFileIdentifier($folderPath) . '/';
		}
		return $canonicalizedIdentifier;
	}

	/**
	 * Returns the identifier of the folder the file resides in
	 *
	 * @param string $fileIdentifier
	 * @return mixed
	 */
	public function getParentFolderIdentifierOfIdentifier($fileIdentifier) {
		$fileIdentifier = $this->canonicalizeAndCheckFileIdentifier($fileIdentifier);
		return \TYPO3\CMS\Core\Utility\PathUtility::dirname($fileIdentifier) . '/';
	}


}
