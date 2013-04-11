<?php
namespace TYPO3\CMS\Backend\ContextMenu\Extdirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 */
abstract class AbstractExtdirectContextMenu extends \TYPO3\CMS\Backend\ContextMenu\AbstractContextMenu {

	/**
	 * Returns the actions for the given node information
	 *
	 * Note: This method should be overriden to fit your specific needs.
	 *
	 * The informations should contain the basic informations of a
	 * \TYPO3\CMS\Backend\Tree\TreeNode for further processing. Also the classname
	 * (property type) of the node should be given, because we need this information
	 * to create the node.
	 *
	 * @param \stdClass $nodeData
	 * @return array
	 */
	public function getActionsForNodeArray($nodeData) {
		if ($this->dataProvider === NULL) {
			$dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\ContextMenu\\AbstractContextMenuDataProvider');
			$this->setDataProvider($dataProvider);
		}
		/** @var $node \TYPO3\CMS\Backend\Tree\TreeNode */
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\TreeNode', (array) $nodeData);
		$actions = $this->dataProvider->getActionsForNode($node);
		return $actions;
	}

	/**
	 * Unused for this implementation
	 *
	 * @see getActionsForNodeArray()
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $node
	 * @return array
	 */
	public function getActionsForNode(\TYPO3\CMS\Backend\Tree\TreeNode $node) {

	}

}


?>