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
 * The Query class used to run queries against the database
 *
 * @package Extbase
 * @subpackage Persistence
 * @version $Id: Query.php 658 2009-05-16 13:54:16Z jocrau $
 * @scope prototype
 */
class Tx_Extbase_Persistence_Query implements Tx_Extbase_Persistence_QueryInterface {
// SK: Is "limit" and "order" and "offset" evaluated?
	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var Tx_Extbase_Persistence_DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface
	 */
	protected $QOMFactory;

	/**
	 * @var Tx_Extbase_Persistence_ValueFactoryInterface
	 */
	protected $valueFactory;

	/**
	 * @var Tx_Extbase_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_QOM_SourceInterface
	 */
	protected $source;

	/**
	 * @var Tx_Extbase_Persistence_QOM_ConstraintInterface
	 */
	protected $constraint;

	/**
	 * An array of named variables and their values from the operators
	 * @var array
	 */
	protected $operands = array();

	/**
	 * @var int
	 */
	protected $orderings = array();

	/**
	 * @var int
	 */
	protected $columns = array();

	/**
	 * @var int
	 */
	protected $limit;

	/**
	 * @var int
	 */
	protected $offset;

	/**
	 * Constructs a query object working on the given class name
	 *
	 * @param string $className
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Injects the persistence manager, used to fetch the CR session
	 *
	 * @param Tx_Extbase_Persistence_ManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(Tx_Extbase_Persistence_ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
		$this->QOMFactory = $this->persistenceManager->getBackend()->getQOMFactory();
		$this->valueFactory = $this->persistenceManager->getBackend()->getValueFactory();
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(Tx_Extbase_Persistence_Mapper_DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Returns the class name the query handles
	 *
	 * @return string The class name
	 */
	public function getClassName() {
		$this->className;
	}

	/**
	 * Sets the source to fetch the result from
	 *
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $source
	 */
	public function setSource(Tx_Extbase_Persistence_QOM_SourceInterface $source) {
		$this->source = $source;
	}

	/**
	 * Returns the source to fetch the result from. If the source is not set, it will be generated from the given class name
	 *
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface The source
	 */
	protected function getSource() {
		if ($this->source === NULL) {
			$this->source = $this->QOMFactory->selector($this->dataMapper->convertClassNameToSelectorName($this->className));
		}
		return $this->source;
	}

	/**
	 * Executes the query against the database and returns the result
	 *
	 * @return Tx_Extbase_Persistence_QueryResultInterface The query result
	 */
	public function execute() {
		$query = $this->QOMFactory->createQuery(
			$this->getSource(),
			$this->constraint,
			$this->orderings,
			$this->columns // TODO implement selection of columns
		);
		foreach ($this->operands as $name => $value) {
			$query->bindValue($name, $this->valueFactory->createValue($value));
		}
		$result = $query->execute();

		return $this->dataMapper->map($this->className, $result->getRows());
	}

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 *  'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @param array $orderings The property names to order by
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;
		return $this;
	}

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setLimit($limit) {
		if (!is_int($limit) || $limit < 1) throw new InvalidArgumentException('The limit must be an integer >= 1', 1245071870);
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function setOffset($offset) {
		if (!is_int($offset) || $offset < 0) throw new InvalidArgumentException('The limit must be a positive integer', 1245071872);
		$this->offset = $offset;
		return $this;
	}

	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint
	 * @return Tx_Extbase_Persistence_QueryInterface
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		return $this;
	}

	/**
	 * Performs a logical conjunction of the two given constraints.
	 *
	 * @param object $constraint1 First constraint
	 * @param object $constraint2 Second constraint
	 * @return Tx_Extbase_Persistence_QOM_AndInterface
	 */
	public function logicalAnd($constraint1, $constraint2) {
		return $this->QOMFactory->_and(
		$constraint1,
		$constraint2
		);
	}

	/**
	 * Performs a logical disjunction of the two given constraints
	 *
	 * @param object $constraint1 First constraint
	 * @param object $constraint2 Second constraint
	 * @return Tx_Extbase_Persistence_QOM_OrInterface
	 */
	public function logicalOr($constraint1, $constraint2) {
		return $this->QOMFactory->_or(
		$constraint1,
		$constraint2
		);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return Tx_Extbase_Persistence_QOM_NotInterface
	 */
	public function logicalNot($constraint) {
		return $this->QOMFactory->not($constraint);
	}

	/**
	 * Matches against the (internal) uid.
	 *
	 * @param int $uid The uid to match against
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 */
	public function withUid($uid) {
		$sourceSelectorName = $this->getSource()->getSelectorName();
		$uniqueVariableName = $this->getUniqueVariableName('uid');
		$this->operands[$uniqueVariableName] = $uid;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('uid', $sourceSelectorName),
			Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
			$this->QOMFactory->bindVariable($uniqueVariableName)
			);
	}

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		$source = $this->getSource();
		$uniqueVariableName = uniqid($propertyName);
		if ($source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
			$sourceSelectorName = $this->getSource()->getSelectorName();
		}
		// TODO $sourceSelectorName might not be initialized

		if (is_object($operand) && !($operand instanceof DateTime)) {
			// FIXME This branch of if-then-else is not fully backported and non functional by now
			$operand = $this->persistenceManager->getBackend()->getUidByObject($operand);
			$left = $source;
			$columnMap = $this->dataMapper->getDataMap($this->className)->getColumnMap($propertyName);
			$childSelectorName = $columnMap->getChildTableName();
			$right = $this->QOMFactory->selector($childSelectorName);
			$joinCondition = $this->QOMFactory->childNodeJoinCondition($childSelectorName, $parentSelectorName);

			$this->source = $this->QOMFactory->join(
				$left,
				$right,
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_JOIN_TYPE_INNER,
				$joinCondition
				);

			$comparison = $this->QOMFactory->comparison(
				$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName),
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
				$this->QOMFactory->bindVariable($uniqueVariableName)
				);
				
			$this->operands[$uniqueVariableName] = $operand;
		} else {
			if ($caseSensitive) {
				$comparison = $this->QOMFactory->comparison(
					$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName),
					Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
					$this->QOMFactory->bindVariable($uniqueVariableName)
					);
			} else {
				$comparison = $this->QOMFactory->comparison(
					$this->QOMFactory->lowerCase(
						$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName)
					),
					Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
					$this->QOMFactory->bindVariable($uniqueVariableName)
					);
			}

			if ($caseSensitive) {
				$this->operands[$uniqueVariableName] = $operand;
			} else {
				$this->operands[$uniqueVariableName] = strtolower($operand);
			}
		}

		return $comparison;
	}

	/**
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 */
	public function like($propertyName, $operand) {
		$source = $this->getSource();
		$uniqueVariableName = uniqid($propertyName);
		if ($source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
			$sourceSelectorName = $this->getSource()->getSelectorName();
		}
		// TODO $sourceSelectorName might not be initialized

		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName),
			Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LIKE,
			$this->QOMFactory->bindVariable($uniqueVariableName)
			);
	}

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 */
	public function lessThan($propertyName, $operand) {
		$sourceSelectorName = $this->getSource()->getSelectorName();
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName),
			Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN,
			$this->QOMFactory->bindVariable($uniqueVariableName)
			);
	}

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		$sourceSelectorName = $this->getSource()->getSelectorName();
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName),
			Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($uniqueVariableName)
			);
	}

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 */
	public function greaterThan($propertyName, $operand) {
		$sourceSelectorName = $this->getSource()->getSelectorName();
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName),
			Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN,
			$this->QOMFactory->bindVariable($uniqueVariableName)
			);
	}

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		$sourceSelectorName = $this->getSource()->getSelectorName();
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $sourceSelectorName),
			Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($uniqueVariableName)
			);
	}

	protected function getUniqueVariableName($propertyName) {
		return uniqid($propertyName);
	}

}
?>
