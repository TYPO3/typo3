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
 * A directory
 */
class DirectoryNode extends AbstractNode implements NodeInterface {

	/**
	 * @var NULL|string Default for directories is 2770
	 */
	protected $targetPermission = '2770';

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
				'Node must have parent',
				1366222203
			);
		}
		$this->parent = $parent;

		// Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
		if (strstr($structure['name'], '/') !== FALSE) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Directory name must not contain forward slash',
				1366226639
			);
		}
		$this->name = $structure['name'];

		if (isset($structure['targetPermission'])) {
			$this->targetPermission = $structure['targetPermission'];
		}

		if (array_key_exists('children', $structure)) {
			$this->createChildren($structure['children']);
		}
	}

	/**
	 * Get own status and status of child objects
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getStatus() {
		$result = array();
		if (!$this->exists()) {
			$status = new Status\WarningStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' does not exist');
			$result[] = $status;
		} else {
			$result[] = $this->getSelfStatus();
		}
		$result = array_merge($result, $this->getChildrenStatus());
		return $result;
	}

	/**
	 * Create a test file and delete again if directory exists
	 *
	 * @return boolean TRUE if test file creation was successful
	 */
	public function isWritable() {
		$result = TRUE;
		if (!$this->exists()) {
			$result = FALSE;
		} elseif (!$this->canFileBeCreated()) {
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * Fix structure
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function fix() {
		$result = $this->fixSelf();
		foreach ($this->children as $child) {
			/** @var $child NodeInterface */
			$result = array_merge($result, $child->fix());
		}
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
			$result[] = $this->createDirectory();
		}
		if (!$this->isDirectory()) {
			$status = new Status\ErrorStatus();
			$status->setTitle('Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a directory');
			$fileType = @filetype($this->getAbsolutePath());
			if ($fileType) {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,' .
					' but is of type ' . $fileType . '. I can not fix this. Please investigate.'
				);
			} else {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a directory,' .
					' but is of unknown type, probably because some upper level directory does not exist. Please investigate.'
				);
			}
			$result[] = $status;
		} elseif (!$this->isPermissionCorrect()) {
			$result[] = $this->fixPermission();
		}
		return $result;
	}

	/**
	 * Create directory if not exists
	 *
	 * @throws Exception
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function createDirectory() {
		if ($this->exists()) {
			throw new Exception(
				'Directory ' . $this->getAbsolutePath() . ' already exists',
				1366740091
			);
		}
		$result = @mkdir($this->getAbsolutePath());
		if ($result === TRUE) {
			$status = new Status\OkStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot() . ' successfully created.');
		} else {
			$status = new Status\ErrorStatus();
			$status->setTitle('Directory ' . $this->getRelativePathBelowSiteRoot() . ' not created!');
			$status->setMessage(
				'The target directory could not be created. There is probably some' .
				' group or owner permission problem on the parent directory.'
			);
		}
		return $status;
	}

	/**
	 * Get status of directory - used in root and directory node
	 *
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function getSelfStatus() {
		$result = NULL;
		if (!$this->isDirectory()) {
			$status = new Status\ErrorStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' is not a directory');
			$status->setMessage(
				'Path ' . $this->getAbsolutePath() . ' should be a directory,' .
				' but is of type ' . filetype($this->getAbsolutePath())
			);
			$result = $status;
		} elseif (!$this->isWritable()) {
			$status = new Status\WarningStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' is not writable');
			$status->setMessage(
				'Path ' . $this->getAbsolutePath() . ' exists, but no file below' .
				' can be created.'
			);
			$result = $status;
		} elseif (!$this->isPermissionCorrect()) {
			if ($this->getTargetPermissionRelaxed() === TRUE) {
				$status = new Status\NoticeStatus();
				$status->setTitle($this->getRelativePathBelowSiteRoot() . ' has wrong permission');
				$status->setMessage(
					'Target permission are ' . $this->targetPermission .
					' but current permission are ' . $this->getCurrentPermission()
				);
				$result = $status;
			} else {
				$status = new Status\WarningStatus();
				$status->setTitle($this->getRelativePathBelowSiteRoot() . ' has wrong permission');
				$status->setMessage(
					'Target permission are ' . $this->targetPermission .
					' but current permission are ' . $this->getCurrentPermission()
				);
				$result = $status;
			}
		} else {
			$status = new Status\OkStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot());
			$result = $status;
		}
		return $result;
	}

	/**
	 * Get status of children
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	protected function getChildrenStatus() {
		$result = array();
		foreach ($this->children as $child) {
			/** @var $child NodeInterface */
			$result = array_merge($result, $child->getStatus());
		}
		return $result;
	}

	/**
	 * Create a test file and delete again - helper for isWritable
	 *
	 * @return boolean TRUE if test file creation was successful
	 */
	protected function canFileBeCreated() {
		$testFileName = uniqid('installToolTest_');
		$result = @touch($this->getAbsolutePath() . '/' . $testFileName);
		if ($result === TRUE) {
			unlink($this->getAbsolutePath() . '/' . $testFileName);
		}
		return $result;
	}

	/**
	 * Checks if not is a directory
	 *
	 * @return boolean True if node is a directory
	 */
	protected function isDirectory() {
		$path = $this->getAbsolutePath();
		return (!@is_link($path) && @is_dir($path));
	}

	/**
	 * Create children nodes - done in directory and root node
	 *
	 * @param array $structure Array of childs
	 * @throws Exception\InvalidArgumentException
	 */
	protected function createChildren(array $structure) {
		foreach ($structure as $child) {
			if (!array_key_exists('type', $child)) {
				throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
					'Child must have type',
					1366222204
				);
			}
			if (!array_key_exists('name', $child)) {
				throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
					'Child must have name',
					1366222205
				);
			}
			$name = $child['name'];
			foreach ($this->children as $existingChild) {
				/** @var $existingChild NodeInterface */
				if ($existingChild->getName() === $name) {
					throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
						'Child name must be unique',
						1366222206
					);
				}
			}
			$this->children[] = new $child['type']($child, $this);
		}
	}
}
?>