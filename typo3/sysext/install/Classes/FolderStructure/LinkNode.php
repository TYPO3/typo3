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
 * A link
 */
class LinkNode extends AbstractNode implements NodeInterface {

	/**
	 * @var string Optional link target
	 */
	protected $target = '';

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
				'Link node must have parent',
				1380485700
			);
		}
		$this->parent = $parent;

		// Ensure name is a single segment, but not a path like foo/bar or an absolute path /foo
		if (strstr($structure['name'], '/') !== FALSE) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'File name must not contain forward slash',
				1380546061
			);
		}
		$this->name = $structure['name'];

		if (isset($structure['target']) && strlen($structure['target']) > 0) {
			$this->target = $structure['target'];
		}
	}

	/**
	 * Get own status
	 * Returns information status if running on Windows
	 * Returns OK status if is link and possible target is correct
	 * Else returns error (not fixable)
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getStatus() {
		if ($this->isWindowsOs()) {
			$status = new Status\InfoStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' should be a link, but this support is incomplete for Windows.');
			$status->setMessage(
				'This node is not handled for Windows OS and should be checked manually.'
			);
			return array($status);
		}

		if (!$this->exists()) {
			$status = new Status\ErrorStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' should be a link, but it does not exist');
			$status->setMessage('Links cannot be fixed by this system');
			return array($status);
		}

		if (!$this->isLink()) {
			$status = new Status\WarningStatus();
			$status->setTitle('Path ' . $this->getRelativePathBelowSiteRoot() . ' is not a link');
			$type = @filetype($this->getAbsolutePath());
			if ($type) {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a link,' .
					' but is of type ' . $type . '. This cannot be fixed automatically. Please investigate.'
				);
			} else {
				$status->setMessage(
					'The target ' . $this->getRelativePathBelowSiteRoot() . ' should be a file,' .
					' but is of unknown type, probably because an upper level directory does not exist. Please investigate.'
				);
			}
			return array($status);
		}

		if (!$this->isTargetCorrect()) {
			$status = new Status\ErrorStatus();
			$status->setTitle($this->getRelativePathBelowSiteRoot() . ' is a link, but link target is not as specified');
			$status->setMessage(
				'Link target should be ' . $this->getTarget() . ' but is ' . $this->getCurrentTarget()
			);
			return array($status);
		}

		$status = new Status\OkStatus();
		$message = 'Is a link';
		if ($this->getTarget() !== '') {
			$message .= ' and correctly points to target ' . $this->getTarget();
		}
		$status->setTitle($this->getRelativePathBelowSiteRoot());
		$status->setMessage($message);
		return array($status);
	}

	/**
	 * Fix structure
	 *
	 * If there is nothing to fix, returns an empty array
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function fix() {
		return array();
	}

	/**
	 * Get link target
	 *
	 * @return string Link target
	 */
	protected function getTarget() {
		return $this->target;
	}

	/**
	 * Find out if node is a link
	 *
	 * @throws Exception\InvalidArgumentException
	 * @return boolean TRUE if node is a link
	 */
	protected function isLink() {
		if (!$this->exists()) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Link does not exist',
				1380556246
			);
		}
		return @is_link($this->getAbsolutePath());
	}

	/**
	 * Checks if the real link target is identical to given target
	 *
	 * @throws Exception\InvalidArgumentException
	 * @return boolean TRUE if target is correct
	 */
	protected function isTargetCorrect() {
		if (!$this->exists()) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Link does not exist',
				1380556245
			);
		}
		if (!$this->isLink()) {
			throw new \TYPO3\CMS\Install\FolderStructure\Exception\InvalidArgumentException(
				'Node is not a link',
				1380556247
			);
		}

		$result = FALSE;
		$expectedTarget = $this->getTarget();
		if (empty($expectedTarget)) {
			$result = TRUE;
		} else {
			$actualTarget = $this->getCurrentTarget();
			if ($actualTarget === rtrim($expectedTarget, '/')) {
				$result = TRUE;
			}
		}
		return $result;
	}

	/**
	 * Return current target of link
	 *
	 * @return string target
	 */
	protected function getCurrentTarget() {
		return readlink($this->getAbsolutePath());
	}

}
