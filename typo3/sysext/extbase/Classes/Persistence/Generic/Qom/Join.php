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
 * Performs a join between two node-tuple sources.
 */
class Join implements \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface
	 */
	protected $left;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface
	 */
	protected $right;

	/**
	 * @var integer
	 */
	protected $joinType;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinConditionInterface
	 */
	protected $joinCondition;

	/**
	 * Constructs the Join instance
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $left the left node-tuple source; non-null
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $right the right node-tuple source; non-null
	 * @param string $joinType one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
	 * @param JoinConditionInterface $joinCondition
	 */
	public function __construct(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $left, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $right, $joinType, \TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinConditionInterface $joinCondition) {
		$this->left = $left;
		$this->right = $right;
		$this->joinType = $joinType;
		$this->joinCondition = $joinCondition;
	}

	/**
	 * Gets the left node-tuple source.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface the left source; non-null
	 */
	public function getLeft() {
		return $this->left;
	}

	/**
	 * Gets the right node-tuple source.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface the right source; non-null
	 */
	public function getRight() {
		return $this->right;
	}

	/**
	 * Gets the join type.
	 *
	 * @return string one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
	 */
	public function getJoinType() {
		return $this->joinType;
	}

	/**
	 * Gets the join condition.
	 *
	 * @return JoinConditionInterface the join condition; non-null
	 */
	public function getJoinCondition() {
		return $this->joinCondition;
	}
}

?>