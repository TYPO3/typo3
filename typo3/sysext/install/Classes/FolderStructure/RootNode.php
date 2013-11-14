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
 * Root node of structure
 */
class RootNode extends DirectoryNode implements RootNodeInterface {

	/**
	 * Implement constructor
	 *
	 * @param array $structure Given structure
	 * @param NodeInterface $parent Must be NULL for RootNode
	 * @throws Exception\RootNodeException
	 * @throws Exception\InvalidArgumentException
	 */
	public function __construct(array $structure, NodeInterface $parent = NULL) {
		if (!is_null($parent)) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\RootNodeException(
				'Root node must not have parent',
				1366140117
			);
		}

		if (!isset($structure['name'])
			|| ($this->isWindowsOs() && substr($structure['name'], 1, 2) !== ':/')
			|| (!$this->isWindowsOs() && $structure['name'][0] !== '/')
		) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Root node expects absolute path as name',
				1366141329
			);
		}
		$this->name = $structure['name'];

		if (isset($structure['targetPermission'])) {
			$this->setTargetPermission($structure['targetPermission']);
		}

		if (array_key_exists('children', $structure)) {
			$this->createChildren($structure['children']);
		}
	}

	/**
	 * Get own status and status of child objects - Root node gives error status if not exists
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getStatus() {
		$result = array();
		if (!$this->exists()) {
			$status = new Status\ErrorStatus();
			$status->setTitle($this->getAbsolutePath() . ' does not exist');
			$result[] = $status;
		} else {
			$result = $this->getSelfStatus();
		}
		$result = array_merge($result, $this->getChildrenStatus());
		return $result;
	}

	/**
	 * Root node does not call parent, but returns own name only
	 *
	 * @return string Absolute path
	 */
	public function getAbsolutePath() {
		return $this->name;
	}
}
