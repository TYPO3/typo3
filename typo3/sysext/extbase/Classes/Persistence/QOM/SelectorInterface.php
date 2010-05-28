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
 * Selects a subset of the nodes in the repository based on node type.
*
* A selector selects every node in the repository, subject to access control
* constraints, that satisfies at least one of the following conditions:
*
* the node's primary node type is nodeType, or
* the node's primary node type is a subtype of nodeType, or
* the node has a mixin node type that is nodeType, or
* the node has a mixin node type that is a subtype of nodeType.
*
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: SelectorInterface.php 1729 2009-11-25 21:37:20Z stucki $
 */
interface Tx_Extbase_Persistence_QOM_SelectorInterface extends Tx_Extbase_Persistence_QOM_SourceInterface {

	/**
	 * Gets the name of the required node type.
	 *
	 * @return string the node type name; non-null
	 */
	public function getNodeTypeName();

	/**
	 * Gets the selector name.
	 * A selector's name can be used elsewhere in the query to identify the selector.
	 *
	 * @return the selector name; non-null
	 */
	public function getSelectorName();

}

?>