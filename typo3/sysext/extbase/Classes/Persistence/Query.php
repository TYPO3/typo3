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
 * @version $Id$
 * @scope prototype
 * @api
 */
class Tx_Extbase_Persistence_Query implements Tx_Extbase_Persistence_QueryInterface, Tx_Extbase_Persistence_QuerySettingsInterface {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var Tx_Extbase_Persistence_DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var Tx_Extbase_Persistence_Manager
	 */
	protected $persistenceManager;

	/**
	 * @var Tx_Extbase_Persistence_QOM_QueryObjectModelFactoryInterface
	 */
	protected $qomFactory;

	/**
	 * @var Tx_Extbase_Persistence_ValueFactoryInterface
	 */
	protected $valueFactory;

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
	 * The query settings.
	 *
	 * @var Tx_Extbase_Persistence_QuerySettingsInterface
	 */
	protected $querySettings;

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
		$this->qomFactory = $this->persistenceManager->getBackend()->getQomFactory();
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
	 * Sets the Query Settings. These Query settings must match the settings expected by
	 * the specific Storage Backend.
	 *
	 * @param Tx_Extbase_Persistence_QuerySettingsInterface $querySettings The Query Settings
	 * @return void
	 */
	public function setQuerySettings(Tx_Extbase_Persistence_QuerySettingsInterface $querySettings) {
		$this->querySettings = $querySettings;
	}

	/**
	 * Returns the Query Settings.
	 * 
	 * @return Tx_Extbase_Persistence_QuerySettingsInterface $querySettings The Query Settings
	 */
	public function getQuerySettings() {
		if (!($this->querySettings instanceof Tx_Extbase_Persistence_QuerySettingsInterface)) throw new Tx_Extbase_Persistence_Exception('Tried to get the query settings without seting them before.', 1248689115);
		return $this->querySettings;
	}

	/**
	 * Returns the class name the query handles
	 *
	 * @return string The class name
	 */
	public function getClassName() {
		return $this->className;
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
	 * Returns the selectorn name or an empty string, if the source is not a selector
	 * // TODO This has to be checked at another place
	 * @return string The selector name
	 */
	protected function getSelectorName() {
		if ($this->getSource() instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
			return $this->source->getSelectorName();
		} else {
			return '';
		}
	}

	/**
	 * Gets the node-tuple source for this query.
	 *
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface the node-tuple source; non-null
	*/
	public function getSource() {
		if ($this->source === NULL) {
			$this->source = $this->qomFactory->selector($this->className, $this->dataMapper->convertClassNameToTableName($this->className));
		}
		return $this->source;
	}

	/**
	 * Executes the query against the database and returns the result
	 *
	 * @return array<object> The query result as an array of objects
	 * @api
	 */
	public function execute() {
		$rows = $this->persistenceManager->getObjectDataByQuery($this);
		if ($this->getQuerySettings()->getReturnRawQueryResult() === TRUE) {
			return $rows;
		} else {
			return $this->dataMapper->map($this->className, $rows);
		}
	}
	
	/**
	 * Executes the number of matching objects for the query
	 *
	 * @return integer The number of matching objects
	 * @api
	 */
	public function count() {
		return $this->persistenceManager->getObjectCountByQuery($this);
	}
	
	/**
	 * Prepares and returns a Query Object Model
	 *
	 * @return Tx_Extbase_Persistence_QOM_QueryObjectModelInterface The prepared query object
	 */
	protected function getPreparedQueryObjectModel() {
		if ($this->source === NULL) {
			$this->source = $this->qomFactory->selector($this->className, $this->dataMapper->convertClassNameToTableName($this->className));
		}
		if ($this->constraint instanceof Tx_Extbase_Persistence_QOM_StatementInterface) {
			$query = $this->qomFactory->createQuery(
				$this->source,
				$this->constraint,
				array(),
				array()
			);
		} else {
			$query = $this->qomFactory->createQuery(
				$this->source,
				$this->constraint,
				$this->orderings,
				$this->columns // TODO implement selection of columns
			);

			if ($this->limit !== NULL) {
				$query->setLimit($this->limit);
			}
			if ($this->offset !== NULL) {
				$query->setOffset($this->offset);
			}

		}

		foreach ($this->operands as $name => $value) {
			if (is_array($value)) {
				$newValue = array();
				foreach ($value as $valueItem) {
					$newValue[] = $this->valueFactory->createValue($valueItem);
				}
				$query->bindValue($name, $this->valueFactory->createValue($newValue));
			} else {
				$query->bindValue($name, $this->valueFactory->createValue($value));
			}
		}
		$query->setQuerySettings($this->getQuerySettings());
		return $query;
	}

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 *  'foo' => Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING,
	 *  'bar' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING
	 * )
	 * where 'foo' and 'bar' are property names.
	 *
	 * @param array $orderings The property names to order by
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function setOrderings(array $orderings) {
		$parsedOrderings = array();
		foreach ($orderings as $propertyName => $order) {
			if ($order === Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING) {
				$parsedOrderings[] = $this->qomFactory->descending($this->qomFactory->propertyValue($propertyName));
			} elseif ($order === Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING) {
				$parsedOrderings[] = $this->qomFactory->ascending($this->qomFactory->propertyValue($propertyName));
			} else {
				throw new Tx_Extbase_Persistence_Exception_UnsupportedOrder('The order you specified for your query is not supported.', 1253785630);
			}
		}
		$this->orderings = $parsedOrderings;
		return $this;
	}
	
	/**
	 * Returns the property names to order the result by. Like this:
	 * array(
	 *  'foo' => \F3\FLOW3\Persistence\QueryInterface::ORDER_ASCENDING,
	 *  'bar' => \F3\FLOW3\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 *
	 * @return array
	 * @api
	 */
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Sets the maximum size of the result set to limit. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param integer $limit
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function setLimit($limit) {
		if (!is_int($limit) || $limit < 1) throw new InvalidArgumentException('The limit must be an integer >= 1', 1245071870);
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Returns the maximum size of the result set to limit.
	 *
	 * @param integer
	 * @api
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Sets the start offset of the result set to offset. Returns $this to
	 * allow for chaining (fluid interface)
	 *
	 * @param integer $offset
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function setOffset($offset) {
		if (!is_int($offset) || $offset < 0) throw new InvalidArgumentException('The offset must be a positive integer', 1245071872);
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Returns the start offset of the result set.
	 *
	 * @return integer
	 * @api
	 */
	public function getOffset() {
		return $this->offset;
	}
	
	/**
	 * The constraint used to limit the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint
	 * @return Tx_Extbase_Persistence_QueryInterface
	 * @api
	 */
	public function matching($constraint) {
		$this->constraint = $constraint;
		return $this;
	}

	/**
	 * Sets the statement of this query programmatically. If you use this, you will lose the abstraction from a concrete storage
	 * backend (database).
	 *
	 * @param string $statement The statement
	 * @param array $paramerters An array of parameters. These will be bound to placeholders '?' in the $statement.
	 * @param object $language The language of the statement. Must be a supported languanguage defined as Tx_Extbase_Persistence_QOM_QueryObjectModelInterface::JCR_* or Tx_Extbase_Persistence_QOM_QueryObjectModelInterface::TYPO3_* or
	 * @return Tx_Extbase_Persistence_QOM_StatementInterface
	 */
	public function statement($statement, array $parameters = array(), $language = Tx_Extbase_Persistence_QOM_QueryObjectModelInterface::TYPO3_SQL_MYSQL) {
		$this->constraint = $this->qomFactory->statement($statement, $parameters, $language);
		return $this;
	}
	
	/**
	 * Gets the constraint for this query.
	 *
	 * @return Tx_Extbase_Persistence_QOM_Constraint the constraint, or null if none
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Performs a logical conjunction of the two given constraints.
	 *
	 * @param object $constraint1 First constraint
	 * @param object $constraint2 Second constraint
	 * @return Tx_Extbase_Persistence_QOM_AndInterface
	 * @api
	 */
	public function logicalAnd($constraint1, $constraint2) {
		return $this->qomFactory->_and(
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
	 * @api
	 */
	public function logicalOr($constraint1, $constraint2) {
		return $this->qomFactory->_or(
			$constraint1,
			$constraint2
			);
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return Tx_Extbase_Persistence_QOM_NotInterface
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->qomFactory->not($constraint);
	}

	/**
	 * Matches against the (internal) uid.
	 *
	 * @param int $uid The uid to match against
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 * @api
	 */
	public function withUid($operand) {
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue('uid', $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_EQUAL_TO,
			$operand
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
		if (is_object($operand) || $caseSensitive) {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
				Tx_Extbase_Persistence_QueryInterface::OPERATOR_EQUAL_TO,
				$operand
			);
		} else {
			$comparison = $this->qomFactory->comparison(
				$this->qomFactory->lowerCase(
					$this->qomFactory->propertyValue($propertyName, $this->getSelectorName())
				),
				Tx_Extbase_Persistence_QueryInterface::OPERATOR_EQUAL_TO,
				strtolower($operand)
			);
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
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_LIKE,
			$operand
			);
	}
	
	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * @param string $propertyName The name of the (multivalued) property to compare against
	 * @param mixed $operand The value to compare with
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 * @api
	 */
	public function contains($propertyName, $operand){
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_CONTAINS,
			$operand
		);
	}

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 * @api
	 */
	public function in($propertyName, $operand) {
		if (!is_array($operand) && (!$operand instanceof ArrayAccess) && (!$operand instanceof Traversable)) {
			throw new Tx_Extbase_Persistence_Exception_UnexpectedTypeException('The "in" operator must be given a mutlivalued operand (array, ArrayAccess, Traversable).', 1264678095);
		}
		
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_IN,
			$operand
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
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_LESS_THAN,
			$operand
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
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO,
			$operand
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
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_GREATER_THAN,
			$operand
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
		return $this->qomFactory->comparison(
			$this->qomFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$operand
			);
	}
		
}
?>