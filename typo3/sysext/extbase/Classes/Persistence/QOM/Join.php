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
 * Performs a join between two node-tuple sources.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: Join.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_Join implements Tx_Extbase_Persistence_QOM_JoinInterface {

	/**
	 * @var Tx_Extbase_Persistence_QOM_SourceInterface
	 */
	protected $left;

	/**
	 * @var Tx_Extbase_Persistence_QOM_SourceInterface
	 */
	protected $right;

	/**
	 * @var integer
	 */
	protected $joinType;

	/**
	 * @var Tx_Extbase_Persistence_QOM_JoinConditionInterface
	 */
	protected $joinCondition;

	/**
	 * Constructs the Join instance
	 *
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $left the left node-tuple source; non-null
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $right the right node-tuple source; non-null
	 * @param string $joinType one of QueryObjectModelConstants.JCR_JOIN_TYPE_*
	 * @param Tx_Extbase_Persistence_QOM_JoinConditionInterface $join Condition the join condition; non-null
	 */
	public function __construct(Tx_Extbase_Persistence_QOM_SourceInterface $left, Tx_Extbase_Persistence_QOM_SourceInterface $right, $joinType, Tx_Extbase_Persistence_QOM_JoinConditionInterface $joinCondition) {
		$this->left = $left;
		$this->right = $right;
		$this->joinType = $joinType;
		$this->joinCondition = $joinCondition;
	}

	/**
	 * Gets the left node-tuple source.
	 *
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface the left source; non-null
	 */
	public function getLeft() {
		return $this->left;
	}

	/**
	 * Gets the right node-tuple source.
	 *
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface the right source; non-null
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
	 * @return JoinCondition the join condition; non-null
	 */
	public function getJoinCondition() {
		return $this->joinCondition;
	}

}

?>