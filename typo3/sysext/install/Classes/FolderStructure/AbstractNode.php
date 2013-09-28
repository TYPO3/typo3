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
 * Abstract node implements common methods
 */
abstract class AbstractNode {

	/**
	 * @var string Name
	 */
	protected $name = '';

	/**
	 * @var NULL|string Target permissions for unix, eg. 2770
	 */
	protected $targetPermission = NULL;

	/**
	 * @var boolean If TRUE, permission check and fix do not throw error level status if wrong
	 */
	protected $targetPermissionRelaxed = FALSE;

	/**
	 * @var NULL|NodeInterface Parent object of this structure node
	 */
	protected $parent = NULL;

	/**
	 * @var array Directories and root may have children, files and link always empty array
	 */
	protected $children = array();

	/**
	 * Get name
	 *
	 * @return string Name
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Get target permission
	 *
	 * @return string Permission, eg. 2770
	 */
	protected function getTargetPermission() {
		return $this->targetPermission;
	}

	/**
	 * Get target permission relaxed flag
	 *
	 * @return boolean TRUE if relaxed permission check should be done
	 */
	protected function getTargetPermissionRelaxed() {
		return $this->targetPermissionRelaxed;
	}

	/**
	 * Get children
	 *
	 * @return array
	 */
	protected function getChildren() {
		return $this->children;
	}

	/**
	 * Get parent
	 *
	 * @return NULL|NodeInterface
	 */
	protected function getParent() {
		return $this->parent;
	}

	/**
	 * Get absolute path of node
	 *
	 * @return string
	 */
	public function getAbsolutePath() {
		return $this->getParent()->getAbsolutePath() . '/' . $this->name;
	}

	/**
	 * Current node is writable if parent is writable
	 *
	 * @return boolean TRUE if parent is writable
	 */
	public function isWritable() {
		return $this->getParent()->isWritable();
	}

	/**
	 * Checks if node exists.
	 * Returns TRUE if it is there, even if it is only a link.
	 * Does not check the type!
	 *
	 * @return boolean
	 */
	protected function exists() {
		if (@is_link($this->getAbsolutePath())) {
			return TRUE;
		} else {
			return @file_exists($this->getAbsolutePath());
		}
	}

	/**
	 * Fix permission if they are not equal to target permission
	 *
	 * @throws Exception
	 * @return \TYPO3\CMS\Install\Status\StatusInterface
	 */
	protected function fixPermission() {
		if ($this->isPermissionCorrect()) {
			throw new Exception(
				'Permission on ' . $this->getAbsolutePath() . ' are already ok',
				1366744035
			);
		}
		$result = @chmod($this->getAbsolutePath(), octdec($this->getTargetPermission()));
		if ($result === TRUE) {
			$status = new Status\OkStatus();
			$status->setTitle('Fixed permission on ' . $this->getRelativePathBelowSiteRoot() . '.');
		} else {
			if ($this->getTargetPermissionRelaxed() === TRUE) {
				$status = new Status\NoticeStatus();
				$status->setTitle('Permission change on ' . $this->getRelativePathBelowSiteRoot() . ' not successful');
				$status->setMessage(
					'Permissions could not be changed to ' . $this->targetPermission . '. This is not a problem as' .
						' long as files and folders within this node can be written.'
				);
			} else {
				$status = new Status\ErrorStatus();
				$status->setTitle('Permission change on ' . $this->getRelativePathBelowSiteRoot() . ' not successful!');
				$status->setMessage(
					'Permissions could not be changed to ' . $this->targetPermission . '. There is probably some' .
						' group or owner permission problem on the parent directory.'
				);
			}
		}
		return $status;
	}

	/**
	 * Checks if current permission are identical to target permission
	 *
	 * @return boolean
	 */
	protected function isPermissionCorrect() {
		if ($this->isWindowsOs()) {
			return TRUE;
		}
		if ($this->getCurrentPermission() === $this->getTargetPermission()) {
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Get current permission of node
	 *
	 * @return string, eg. 2770 for dirs, 0660 for files
	 */
	protected function getCurrentPermission() {
		$absolutePath = $this->getAbsolutePath();
		$permissions = decoct(fileperms($this->getAbsolutePath()));
		if (is_dir($absolutePath)) {
			$result = substr($permissions, 1);
		} else {
			$result = substr($permissions, 2);
		}
		return $result;
	}

	/**
	 * Returns TRUE if OS is windows
	 *
	 * @return boolean TRUE on windows
	 */
	protected function isWindowsOs() {
		if (TYPO3_OS === 'WIN') {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Cut off PATH_site from given path
	 *
	 * @param string $path Given path
	 * @return string Relative path, but beginning with /
	 * @throws Exception\InvalidArgumentException
	 */
	protected function getRelativePathBelowSiteRoot($path = NULL) {
		if (is_null($path)) {
			$path = $this->getAbsolutePath();
		}
		$pathSiteWithoutTrailingSlash = substr(PATH_site, 0, -1);
		if (strpos($path, $pathSiteWithoutTrailingSlash, 0) !== 0) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'PATH_site is not first part of given path',
				1366398198
			);
		}
		$relativePath = substr($path, strlen($pathSiteWithoutTrailingSlash), strlen($path));
		// Add a forward slash again, so we don't end up with an empty string
		if (strlen($relativePath) === 0) {
			$relativePath = '/';
		}
		return $relativePath;
	}
}
?>