<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Abstract Context Menu for ExtDirect
 *
 * This is a concrete implementation that should stay here to be shared
 * between the different ExtDirect implementation. Just create a subclass
 * for adding specific purposes.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
abstract class t3lib_contextmenu_extdirect_ContextMenu extends t3lib_contextmenu_AbstractContextMenu {
	/**
	 * Returns the actions for the given node informations
	 *
	 * Note: This method should be overriden to fit your specific needs.
	 *
	 * The informations should contain the basic informations of a
	 * t3lib_tree_Node for further processing. Also the classname (property type)
	 * of the node should be given, because we need this information
	 * to create the ndoe.
	 *
	 * @param stdClass $nodeInfo
	 * @return array
	 */
	public function getActionsForNodeArray($nodeData) {
		if ($this->dataProvider === NULL) {
			$dataProvider = t3lib_div::makeInstance('t3lib_contextmenu_AbstractDataProvider');
			$this->setDataProvider($dataProvider);
		}

		/** @var $node t3lib_tree_Node */
		$node = t3lib_div::makeInstance('t3lib_tree_Node', (array) $nodeData);
		$actions = $this->dataProvider->getActionsForNode($node);

		return $actions;
	}

	/**
	 * Unused for this implementation
	 *
	 * @see getActionsForNodeArray()
	 * @param t3lib_tree_Node $node
	 * @return array
	 */
	public function getActionsForNode(t3lib_tree_Node $node) {
	}
}

?>