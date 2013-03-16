<?php
namespace TYPO3\CMS\Backend\Tree;

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
 * Interface that defines the comparison of nodes
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
interface ComparableNodeInterface
{
	/**
	 * Compare Node against another one
	 *
	 * Returns:
	 * 1 if the current node is greater than the $other,
	 * -1 if $other is greater than the current node and
	 * 0 if the nodes are equal
	 *
	 * <strong>Example</strong>
	 * <pre>
	 * if ($this->sortValue > $other->sortValue) {
	 * return 1;
	 * } elseif ($this->sortValue < $other->sortValue) {
	 * return -1;
	 * } else {
	 * return 0;
	 * }
	 * </pre>
	 *
	 * @param \TYPO3\CMS\Backend\Tree\TreeNode $other
	 * @return integer see description
	 */
	public function compareTo($other);

}

?>