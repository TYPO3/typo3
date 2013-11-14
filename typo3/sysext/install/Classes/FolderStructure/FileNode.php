<?php
namespace TYPO3\CMS\Install\FolderStructure;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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

use TYPO3\CMS\Install\Status;

/**
 * A file
 */
class FileNode extends AbstractNode implements NodeInterface {

	/**
	 * @var NULL|integer Default for files is octal 0664 == decimal 436
	 */
	protected $targetPermission = '0664';

	/**
	 * @var string|NULL Target content of file. If NULL, target content is ignored
	 */
	protected $targetContent = NULL;

	/**
	 * Implement constructor
	 *
	 * @param array $structure Structure array
	 * @param NodeInterface $parent Parent object
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct(array $structure, NodeInterface $parent = NULL) {
		if (is_null($parent)) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'File node must have parent',
				1366927513
			);
		}
		$this->parent = $parent;

		// Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
		if (strstr($structure['name'], '/') !== FALSE) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'File name must not contain forward slash',
				1366222207
			);
		}
		$this->name = $structure['name'];

		if (isset($structure['targetPermission'])) {
			$this->setTargetPermission($structure['targetPermission']);
		}

		if (isset($structure['targetContent']) && isset($structure['targetContentFile'])) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Either targetContent or targetContentFile can be set, but not both',
				1380364361
			);
		}

		if (isset($structure['targetContent'])) {
			$this->targetContent = $structure['targetContent'];
		}
		if (isset($structure['targetContentFile'])) {
			if (!is_readable($structure['targetContentFile'])) {
				throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
					'targetContentFile ' . $structure['targetContentFile'] . ' does not exist or is not readable',
					1380364362
				);
			}
			$this->targetContent = file_get_contents($structure['targetContentFile']);
		}
	}

	/**
	 * Get own status
	 * Returns warning if file not exists
	 * Returns error if file exists but content is not as expected (can / shouldn't be fixed)
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getStatus() {
		$result = array();
		if (!$this->exists()) {
			$status = new Status\WarningStatus();
			$status->setTitle('File ' . $this->getRelativePathBelowSiteRoot() . ' does not exist');
			$status->setMessage('By using "Try to fix errors" we can try to create it');
			$result[] = $status;
		} else {
			$result = $this->getSelfStatus();
		}
		return $result;
	}

	/**
	 * Fix structure
	 *
	 * If there is nothing to fix, returns an empty array
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function fix() {
		$result = $this->fixSelf();
		return $result;
	}

	/**
	 * Fix this node: create if not there, fix permissions
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	protected function fixSelf() {
		$result = array();
		if (!$this->exists()) {
			$resultCreateFile = $this->createFile();
			$result[] = $resultCreateFile;
			if ($resultCreateFile instanceof \TYPO3\CMS\Install\Status\OkStatus
				&& !is_null($this->targetContent)
			) {
				$result[] = $this->setContent();
				if (!$this->isPermissionCorrect()) {
					$result[] = $this->fixPermission();
				}
			}
		} elseif (!$this->isFile()) {
			$status = new Status\ErrorStatus();
			$status->setTitle('Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a file');
			$fileType = @filetype($this->getAbsolutePath());
			if ($fileType) {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a file,' .
					' but is of type ' . $fileType . '. This cannot be fixed automatically. Please investigate.'
				);
			} else {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a file,' .
					' but is of unknown type, probably because an upper level directory does not exist. Please investigate.'
				);
			}
			$result[] = $status;
		} elseif (!$this->isPermissionCorrect()) {
			$result[] = $this->fixPermission();
		}
		return $result;
	}

	/**
	 * Create file if not exists
	 *
	 * @throws Exception
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function createFile() {
		if ($this->exists()) {
			throw new Exception(
				'File ' . $this->getRelativePathBelowSiteRoot() . ' already exists',
				1367048077
			);
		}
		$result = @touch($this->getAbsolutePath());
		if ($result === TRUE) {
			$status = new Status\OkStatus();
			$status->setTitle('File ' . $this->getRelativePathBelowSiteRoot() . ' successfully created.');
		} else {
			$status = new Status\ErrorStatus();
			$status->setTitle('File ' . $this->getRelativePathBelowSiteRoot() . ' not created!');
			$status->setMessage(
				'The target file could not be created. There is probably a' .
				' group or owner permission problem on the parent directory.'
			);
		}
		return $status;
	}

	/**
	 * Get status of file
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	protected function getSelfStatus() {
		$result = array();
		if (!$this->isFile()) {
			$status = new Status\ErrorStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' is not a file');
			$status->setMessage(
				'Path ' . $this->getAbsolutePath() . ' should be a file,' .
				' but is of type ' . filetype($this->getAbsolutePath())
			);
			$result[] = $status;
		} elseif (!$this->isWritable()) {
			$status = new Status\NoticeStatus();
			$status->setTitle('File ' . $this->getRelativePathBelowSiteRoot() . ' is not writable');
			$status->setMessage(
				'File ' . $this->getRelativePathBelowSiteRoot() . ' exists, but is not writeable.'
			);
			$result[] = $status;
		} elseif (!$this->isPermissionCorrect()) {
			$status = new Status\NoticeStatus();
			$status->setTitle('File ' . $this->getRelativePathBelowSiteRoot() . ' permissions mismatch');
			$status->setMessage(
				'Default configured permissions are ' . $this->getTargetPermission() .
				' but file permissions are ' . $this->getCurrentPermission()
			);
			$result[] = $status;
		}
		if ($this->isFile() && !$this->isContentCorrect()) {
			$status = new Status\NoticeStatus();
			$status->setTitle('File ' . $this->getRelativePathBelowSiteRoot() . ' content differs');
			$status->setMessage(
				'File content is not identical to default content. This file may have been changed manually.' .
				' The Install Tool will not overwrite the current version!'
			);
			$result[] = $status;
		} else {
			$status = new Status\OkStatus();
			$status->setTitle('File ' . $this->getRelativePathBelowSiteRoot());
			$status->setMessage(
				'Is a file with the default content and configured permissions of ' . $this->getTargetPermission()
			);
			$result[] = $status;
		}
		return $result;
	}

	/**
	 * Compare current file content with target file content
	 *
	 * @throws Exception If file does not exist
	 * @return boolean TRUE if current and target file content are identical
	 */
	protected function isContentCorrect() {
		$absolutePath = $this->getAbsolutePath();
		if (is_link($absolutePath) || !is_file($absolutePath)) {
			throw new Exception(
				'File ' . $absolutePath . ' must exist',
				1367056363
			);
		}
		$result = FALSE;
		if (is_null($this->targetContent)) {
			$result = TRUE;
		} else {
			$targetContentHash = md5($this->targetContent);
			$currentContentHash = md5(file_get_contents($absolutePath));
			if ($targetContentHash === $currentContentHash) {
				$result = TRUE;
			}
		}
		return $result;
	}

	/**
	 * Sets content of file to target content
	 *
	 * @throws Exception If file does not exist
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function setContent() {
		$absolutePath = $this->getAbsolutePath();
		if (is_link($absolutePath) || !is_file($absolutePath)) {
			throw new Exception(
				'File ' . $absolutePath . ' must exist',
				1367060201
			);
		}
		if (is_null($this->targetContent)) {
			throw new Exception(
				'Target content not defined for ' . $absolutePath,
				1367060202
			);
		}
		$result = @file_put_contents($absolutePath, $this->targetContent);
		if ($result !== FALSE) {
			$status = new Status\OkStatus();
			$status->setTitle('Set content to ' . $this->getRelativePathBelowSiteRoot());
		} else {
			$status = new Status\ErrorStatus();
			$status->setTitle('Setting content to ' . $this->getRelativePathBelowSiteRoot() . ' failed');
			$status->setMessage('Setting content of the file failed for unknown reasons.');
		}
		return $status;
	}

	/**
	 * Checks if not is a file
	 *
	 * @return boolean
	 */
	protected function isFile() {
		$path = $this->getAbsolutePath();
		return (!is_link($path) && is_file($path));
	}
}
