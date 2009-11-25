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
 * @version $Id: Query.php 1729 2009-11-25 21:37:20Z stucki $
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
	 * @api
	 */
	public function getQuerySettings() {
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
	 * Executes the query against the database and returns the result
	 *
	 * @return array<object> The query result as an array of objects
	 * @api
	 */
	public function execute() {
		$result = $this->getPreparedQueryObjectModel()->execute();
		if ($this->getQuerySettings()->getReturnRawQueryResult() === TRUE) {
			return $result;
		} else {
			return $this->dataMapper->map($this->className, $result->getRows());
		}
	}
	
	/**
	 * Executes the query against the database and returns the number of matching objects
	 *
	 * @return integer The number of matching objects
	 * @api
	 */
	public function count() {
		return $this->getPreparedQueryObjectModel()->count();
	}
	
	/**
	 * Prepares and returns a Query Object Model
	 *
	 * @return Tx_Extbase_Persistence_QOM_QueryObjectModelInterface The prepared query object
	 */
	protected function getPreparedQueryObjectModel() {
		if ($this->source === NULL) {
			$this->source = $this->QOMFactory->selector($this->className, $this->dataMapper->convertClassNameToTableName($this->className));
		}
		if ($this->constraint instanceof Tx_Extbase_Persistence_QOM_StatementInterface) {
			$query = $this->QOMFactory->createQuery(
				$this->source,
				$this->constraint,
				array(),
				array()
			);
		} else {
			$query = $this->QOMFactory->createQuery(
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
				$parsedOrderings[] = $this->QOMFactory->descending($this->QOMFactory->propertyValue($propertyName));
			} elseif ($order === Tx_Extbase_Persistence_QueryInterface::ORDER_ASCENDING) {
				$parsedOrderings[] = $this->QOMFactory->ascending($this->QOMFactory->propertyValue($propertyName));
			} else {
				throw new Tx_Extbase_Persistence_Exception_UnsupportedOrder('The order you specified for your query is not supported.', 1253785630);
			}
		}
		$this->orderings = $parsedOrderings;
		return $this;
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
		$boundVariables = array();
		foreach ($parameters as $parameter) {
			$uniqueVariableName = uniqid();
			$this->operands[$uniqueVariableName] = $parameter;
			$boundVariables[$uniqueVariableName] = $this->QOMFactory->bindVariable($uniqueVariableName);
		}
		$this->constraint = $this->QOMFactory->statement($statement, $boundVariables, $language);
		return $this;
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
	 * @api
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
	 * @api
	 */
	public function logicalNot($constraint) {
		return $this->QOMFactory->not($constraint);
	}

	/**
	 * Matches against the (internal) uid.
	 *
	 * @param int $uid The uid to match against
	 * @return Tx_Extbase_Persistence_QOM_ComparisonInterface
	 * @api
	 */
	public function withUid($uid) {
		$uniqueVariableName = $this->getUniqueVariableName('uid');
		$this->operands[$uniqueVariableName] = $uid;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue('uid', $this->getSelectorName()),
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
		$uniqueVariableName = uniqid($propertyName);
		if (is_object($operand) && !($operand instanceof DateTime)) {
			$operand = $this->persistenceManager->getBackend()->getIdentifierByObject($operand);
		}
		if ($caseSensitive) {
			$comparison = $this->QOMFactory->comparison(
				$this->QOMFactory->propertyValue($propertyName, $this->getSelectorName()),
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
				$this->QOMFactory->bindVariable($uniqueVariableName)
				);
		} else {
			$comparison = $this->QOMFactory->comparison(
				$this->QOMFactory->lowerCase(
					$this->QOMFactory->propertyValue($propertyName, $this->getSelectorName())
				),
				Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
				$this->QOMFactory->bindVariable($uniqueVariableName)
				);
		}

		// TODO Implement case sensitivity for arrays (callback)
		if ($caseSensitive || !is_string($operand)) {
			$this->operands[$uniqueVariableName] = $operand;
		} else {
			$this->operands[$uniqueVariableName] = strtolower($operand);
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
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $this->getSelectorName()),
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
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $this->getSelectorName()),
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
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $this->getSelectorName()),
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
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $this->getSelectorName()),
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
		$uniqueVariableName = uniqid($propertyName);
		$this->operands[$uniqueVariableName] = $operand;
		return $this->QOMFactory->comparison(
			$this->QOMFactory->propertyValue($propertyName, $this->getSelectorName()),
			Tx_Extbase_Persistence_QOM_QueryObjectModelConstantsInterface::JCR_OPERATOR_GREATER_THAN_OR_EQUAL_TO,
			$this->QOMFactory->bindVariable($uniqueVariableName)
			);
	}

	/**
	 * Returns a unique variable name for a given property name. This is necessary for storing
	 * the variable values in an associative array without overwriting existing variables.
	 *
	 * @param string $propertyName The name of the property
	 * @return string The postfixed property name
	 */
	protected function getUniqueVariableName($propertyName) {
		return uniqid($propertyName);
	}

	/**
	 * Returns the selectorn name or an empty string, if the source is not a selector
	 * // TODO This has to be checked at another place
	 * @return string The selector name
	 */
	protected function getSelectorName() {
		if ($this->source === NULL) {
			$this->source = $this->QOMFactory->selector($this->className, $this->dataMapper->convertClassNameToTableName($this->className));
		}
		if ($this->source instanceof Tx_Extbase_Persistence_QOM_SelectorInterface) {
			return $this->source->getSelectorName();
		} else {
			return '';
		}
	}

}
?>
