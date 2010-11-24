<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Abstract Context Menu Data Provider
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_contextmenu_AbstractDataProvider {
	/**
	 * Context Menu Type (e.g. records.pages, records.tt_content)
	 *
	 * @var string
	 */
	protected $contextMenuType = '';

	/**
	 * Returns the context menu type
	 *
	 * @return string
	 */
	public function getContextMenuType() {
		return $this->contextMenuType;
	}

	/**
	 * Sets the context menu type
	 *
	 * @param string $contextMenuType
	 * @return void
	 */
	public function setContextMenuType($contextMenuType) {
		$this->contextMenuType = $contextMenuType;
	}

	/**
	 * Returns the root node
	 *
	 * @return t3lib_contextmenu_ActionCollection
	 */
	abstract public function getActionsForNode(t3lib_tree_Node $node);

	/**
	 * Returns the configuration of the specified context menu type
	 *
	 * @return array
	 */
	protected function getConfiguration() {
		$contextMenuAction = $GLOBALS['BE_USER']->getTSConfig(
			'options.contextMenu.' . $this->contextMenuType . '.items'
		);

		return $contextMenuAction;
	}
}
