<?php
namespace TYPO3\CMS\Backend\ContextMenu\Pagetree\Extdirect;

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
 * Context Menu of the Page Tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ContextMenuConfiguration extends \TYPO3\CMS\Backend\ContextMenu\Extdirect\AbstractExtdirectContextMenu {

	/**
	 * Sets the data provider
	 *
	 * @return void
	 */
	protected function initDataProvider() {
		/** @var $dataProvider \TYPO3\CMS\Backend\ContextMenu\Pagetree\ContextMenuDataProvider */
		$dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\ContextMenu\\Pagetree\\ContextMenuDataProvider');
		$this->setDataProvider($dataProvider);
	}

	/**
	 * Returns the actions for the given node information's
	 *
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function getActionsForNodeArray($nodeData) {
		/** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode', (array) $nodeData);
		$node->setRecord(\TYPO3\CMS\Backend\Tree\Pagetree\Commands::getNodeRecord($node->getId()));
		$this->initDataProvider();
		$this->dataProvider->setContextMenuType('table.' . $node->getType());
		$actionCollection = $this->dataProvider->getActionsForNode($node);
		if ($actionCollection instanceof \TYPO3\CMS\Backend\ContextMenu\ContextMenuActionCollection) {
			$actions = $actionCollection->toArray();
		}
		return $actions;
	}

}


?>