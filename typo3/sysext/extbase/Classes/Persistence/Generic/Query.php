<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

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
 * The Query class used to run queries against the database
 *
 * @api
 */
class Query implements \TYPO3\CMS\Extbase\Persistence\QueryInterface {

	/**
	 * An inner join.
	 */
	const JCR_JOIN_TYPE_INNER = '{http://www.jcp.org/jcr/1.0}joinTypeInner';

	/**
	 * A left-outer join.
	 */
	const JCR_JOIN_TYPE_LEFT_OUTER = '{http://www.jcp.org/jcr/1.0}joinTypeLeftOuter';

	/**
	 * A right-outer join.
	 */
	const JCR_JOIN_TYPE_RIGHT_OUTER = '{http://www.jcp.org/jcr/1.0}joinTypeRightOuter';

	/**
	 * Charset of strings in QOM
	 */
	const CHARSET = 'utf-8';

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory
	 */
	protected $qomFactory;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface
	 */
	protected $source;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface
	 */
	protected $constraint;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement
	 */
	protected $statement;

	/**
	 * @var int
	 */
	protected $orderings = array();

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
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface
	 */
	protected $querySettings;

	/**
	 * Constructs a query object working on the given class name
	 *
	 * @param string $type
	 */
	public function __construct($type) {
		$this->type = $type;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the persistence manager, used to fetch the CR session
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager
	 * @return void
	 */
	public function injectPersistenceManager(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

	/**
	 * Injects the DataMapper to map nodes to objects
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
	 * @return void
	 */
	public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper) {
		$this->dataMapper = $dataMapper;
	}

	/**
	 * Injects the Query Object Model Factory
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory
	 * @return void
	 */
	public function injectQomFactory(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\QueryObjectModelFactory $qomFactory) {
		$this->qomFactory = $qomFactory;
	}

	/**
	 * Sets the Query Settings. These Query settings must match the settings expected by
	 * the specific Storage Backend.
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The Query Settings
	 * @return void
	 * @api This method is not part of FLOW3 API
	 */
	public function setQuerySettings(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings) {
		$this->querySettings = $querySettings;
	}

	/**
	 * Returns the Query Settings.
	 *
	 * @throws Exception
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface $querySettings The Query Settings
	 * @api This method is not part of FLOW3 API
	 */
	public function getQuerySettings() {
		if (!$this->querySettings instanceof \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception('Tried to get the query settings without seting them before.', 1248689115);
		}
		return $this->querySettings;
	}

	/**
	 * Returns the type this query cares for.
	 *
	 * @return string
	 * @api
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * Sets the source to fetch the result from
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source
	 */
	public function setSource(\TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface $source) {
		$this->source = $source;
	}

	/**
	 * Returns the selectorn name or an empty string, if the source is not a selector
	 * TODO This has to be checked at another place
	 *
	 * @return string The selector name
	 */
	protected function getSelectorName() {
		if ($this->getSource() instanceof \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface) {
			return $this->source->getSelectorName();
		} else {
			return '';
		}
	}

	/**
	 * Gets the node-tuple source for this query.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface the node-tuple source; non-null
	 */
	public function getSource() {
		if ($this->source === NULL) {
			$this->source = $this->qomFactory->selector($this->getType(), $this->dataMapper->convertClassNameToTableName($this->getType()));
		}
		return $this->source;
	}

	/**
	 * Executes the query against the database and returns the result
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|array The query result object or an array if $this->getQuerySettings()->getReturnRawQueryResult() is TRUE
	 * @api
	 */
	public function execute() {
		if ($this->getQuerySettings()->getReturnRawQueryResult() === TRUE) {
			return $this->persistenceManager->getObjectDataByQuery($this);
		} else {
			return $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\QueryResultInterface', $this);
		}
	}

	/**
	 * Sets the property names to order the result by. Expected like this:
	 * array(
	 * 'foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
	 * 'bar' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
	 * )
	 * where 'foo' and 'bar' are property names.
	 *
	 * @param array $orderings The property names to order by
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function setOrderings(array $orderings) {
		$this->orderings = $orderings;
		return $this;
	}

	/**
	 * Returns the property names to order the result by. Like this:
	 * array(
	 * 'foo' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
	 * 'bar' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
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
	 * @throws \InvalidArgumentException
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function setLimit($limit) {
		if (!is_int($limit) || $limit < 1) {
			throw new \InvalidArgumentException('The limit must be an integer >= 1', 1245071870);
		}
		$this->limit = $limit;
		return $this;
	}

	/**
	 * Resets a previously set maximum size of the result set. Returns $this to allow
	 * for chaining (fluid interface)
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function unsetLimit() {
		unset($this->limit);
		return $this;
	}

	/**
	 * Returns the maximum size of the result set to limit.
	 *
	 * @return integer
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
	 * @throws \InvalidArgumentException
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 * @api
	 */
	public function setOffset($offset) {
		if (!is_int($offset) || $offset < 0) {
			throw new \InvalidArgumentException('The offset must be a positive integer', 1245071872);
		}
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
	 * @param \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface $constraint
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
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
	 * @param array $parameters An array of parameters. These will be bound to placeholders '?' in the $statement.
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
	 */
	public function statement($statement, array $parameters = array()) {
		$this->statement = $this->qomFactory->statement($statement, $parameters);
		return $this;
	}

	/**
	 * Returns the statement of this query.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Statement
	 */
	public function getStatement() {
		return $this->statement;
	}

	/**
	 * Gets the constraint for this query.
	 *
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Constraint the constraint, or null if none
	 * @api
	 */
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Performs a logical conjunction of the given constraints. The method takes one or more contraints and concatenates them with a boolean AND.
	 * It also scepts a single array of constraints to be concatenated.
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @throws Exception\InvalidNumberOfConstraintsException
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface
	 * @api
	 */
	public function logicalAnd($constraint1) {
		if (is_array($constraint1)) {
			$resultingConstraint = array_shift($constraint1);
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
			$resultingConstraint = array_shift($constraints);
		}
		if ($resultingConstraint === NULL) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056288);
		}
		foreach ($constraints as $constraint) {
			$resultingConstraint = $this->qomFactory->_and($resultingConstraint, $constraint);
		}
		return $resultingConstraint;
	}

	/**
	 * Performs a logical disjunction of the two given constraints
	 *
	 * @param mixed $constraint1 The first of multiple constraints or an array of constraints.
	 * @throws Exception\InvalidNumberOfConstraintsException
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface
	 * @api
	 */
	public function logicalOr($constraint1) {
		if (is_array($constraint1)) {
			$resultingConstraint = array_shift($constraint1);
			$constraints = $constraint1;
		} else {
			$constraints = func_get_args();
			$resultingConstraint = array_shift($constraints);
		}
		if ($resultingConstraint === NULL) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidNumberOfConstraintsException('There must be at least one constraint or a non-empty array of constraints given.', 1268056288);
		}
		foreach ($constraints as $constraint) {
			$resultingConstraint = $this->qomFactory->_or($resultingConstraint, $constraint);
		}
		return $resultingConstraint;
	}

	/**
	 * Performs a logical negation of the given constraint
	 *
	 * @param object $constraint Constraint to negate
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->qomFactory->not($constraint);
	}

	/**
	 * Returns an equals criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the equality test should be done case-sensitive
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function equals($propertyName, $operand, $caseSensitive = TRUE) {
		if (is_object($operand) || $caseSensitive) {
			$comparison = $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_EQUAL_TO, $operand);
		} else {
			$comparison = $this->qomFactory->comparison($this->qomFactory->lowerCase($this->qomFactory->propertyValue($propertyName, $this->getSelectorName())), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_EQUAL_TO, \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter')->conv_case(\TYPO3\CMS\Extbase\Persistence\Generic\Query::CHARSET, $operand, 'toLower'));
		}
		return $comparison;
	}

	/**
	 * Returns a like criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @param boolean $caseSensitive Whether the matching should be done case-sensitive
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function like($propertyName, $operand, $caseSensitive = TRUE) {
		return $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LIKE, $operand);
	}

	/**
	 * Returns a "contains" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains the given operand.
	 *
	 * @param string $propertyName The name of the (multivalued) property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function contains($propertyName, $operand) {
		return $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_CONTAINS, $operand);
	}

	/**
	 * Returns an "in" criterion used for matching objects against a query. It
	 * matches if the property's value is contained in the multivalued operand.
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with, multivalued
	 * @throws Exception\UnexpectedTypeException
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function in($propertyName, $operand) {
		if (!is_array($operand) && !$operand instanceof \ArrayAccess && !$operand instanceof \Traversable) {
			throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnexpectedTypeException('The "in" operator must be given a mutlivalued operand (array, ArrayAccess, Traversable).', 1264678095);
		}
		return $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_IN, $operand);
	}

	/**
	 * Returns a less than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function lessThan($propertyName, $operand) {
		return $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LESS_THAN, $operand);
	}

	/**
	 * Returns a less or equal than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function lessThanOrEqual($propertyName, $operand) {
		return $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO, $operand);
	}

	/**
	 * Returns a greater than criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function greaterThan($propertyName, $operand) {
		return $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_GREATER_THAN, $operand);
	}

	/**
	 * Returns a greater than or equal criterion used for matching objects against a query
	 *
	 * @param string $propertyName The name of the property to compare against
	 * @param mixed $operand The value to compare with
	 * @return \TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface
	 * @api
	 */
	public function greaterThanOrEqual($propertyName, $operand) {
		return $this->qomFactory->comparison($this->qomFactory->propertyValue($propertyName, $this->getSelectorName()), \TYPO3\CMS\Extbase\Persistence\QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO, $operand);
	}

	/**
	 * @return void
	 */
	public function __wakeup() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->persistenceManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\PersistenceManagerInterface');
		$this->dataMapper = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper');
		$this->qomFactory = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelFactory');
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		return array('type', 'source', 'constraint', 'statement', 'orderings', 'limit', 'offset', 'querySettings');
	}

	/**
	 * Returns the query result count.
	 *
	 * @return integer The query result count
	 * @api
	 */
	public function count() {
		return $this->execute()->count();
	}

	/**
	 * Returns an "isEmpty" criterion used for matching objects against a query.
	 * It matches if the multivalued property contains no values or is NULL.
	 *
	 * @param string $propertyName The name of the multivalued property to compare against
	 * @throws \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException
	 * @throws \TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException if used on a single-valued property
	 * @api
	 */
	public function isEmpty($propertyName) {
		throw new \TYPO3\CMS\Extbase\Persistence\Generic\Exception\NotImplementedException(__METHOD__);
	}
}

?>