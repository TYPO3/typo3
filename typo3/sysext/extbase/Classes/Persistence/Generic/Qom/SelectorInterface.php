<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * Selects a subset of the nodes in the repository based on node type.
 *
 * A selector selects every node in the repository, subject to access control
 * constraints, that satisfies at least one of the following conditions:
 *
 * the node's primary node type is nodeType, or
 * the node's primary node type is a subtype of nodeType, or
 * the node has a mixin node type that is nodeType, or
 * the node has a mixin node type that is a subtype of nodeType.
 */
interface SelectorInterface extends \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface {

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
	 * @return string the selector name; non-null
	 */
	public function getSelectorName();
}

?>