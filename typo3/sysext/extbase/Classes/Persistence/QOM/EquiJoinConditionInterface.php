<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
*
*  This class is a backport of the corresponding class of FLOW3.
*  All credits go to the v5 team.
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
 * Tests whether the childSelector node is a child of the parentSelector node. A
 * node-tuple satisfies the constraint only if:
 *  childSelectorNode.getParent().isSame(parentSelectorNode)
 * would return true, where childSelectorNode is the node for childSelector and
 * parentSelectorNode is the node for parentSelector.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: EquiJoinConditionInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_QOM_EquiJoinConditionInterface extends Tx_Extbase_Persistence_QOM_JoinConditionInterface {

	/**
	 * Gets the name of the child selector.
	 *
	 * @return string the selector name; non-null
	 */
	public function getChildSelectorName();

	/**
	 * Gets the name of the parent selector.
	 *
	 * @return string the selector name; non-null
	 */
	public function getParentSelectorName();

}

?>