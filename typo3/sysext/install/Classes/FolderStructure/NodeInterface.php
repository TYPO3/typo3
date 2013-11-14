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

/**
 * Interface for structure nodes root, link, file, ...
 */
interface NodeInterface {

	/**
	 * Constructor gets structure and parent object defaulting to NULL
	 *
	 * @param array $structure Structure
	 * @param NodeInterface $parent Parent
	 */
	public function __construct(array $structure, NodeInterface $parent = NULL);

	/**
	 * Get node name
	 *
	 * @return string Node name
	 */
	public function getName();

	/**
	 * Get absolute path of node
	 *
	 * @return string Absolute path
	 */
	public function getAbsolutePath();

	/**
	 * Get the status of the object tree, recursive for directory and root node
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function getStatus();

	/**
	 * Check if node is writable - can be created and permission can be fixed
	 *
	 * @return boolean TRUE if node is writable
	 */
	public function isWritable();

	/**
	 * Fix structure
	 *
	 * If there is nothing to fix, returns an empty array
	 *
	 * @return array<\TYPO3\CMS\Install\Status\StatusInterface>
	 */
	public function fix();
}
