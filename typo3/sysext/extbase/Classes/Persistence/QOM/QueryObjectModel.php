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
 * A query in the JCR query object model.
 *
 * The JCR query object model describes the queries that can be evaluated by a JCR
 * repository independent of any particular query language, such as SQL.
 *
 * A query consists of:
 *
 * a source. When the query is evaluated, the source evaluates its selectors and
 * the joins between them to produce a (possibly empty) set of node-tuples. This
 * is a set of 1-tuples if the query has one selector (and therefore no joins), a
 * set of 2-tuples if the query has two selectors (and therefore one join), a set
 * of 3-tuples if the query has three selectors (two joins), and so forth.
 * an optional constraint. When the query is evaluated, the constraint filters the
 * set of node-tuples.
 * a list of zero or more orderings. The orderings specify the order in which the
 * node-tuples appear in the query results. The relative order of two node-tuples
 * is determined by evaluating the specified orderings, in list order, until
 * encountering an ordering for which one node-tuple precedes the other. If no
 * orderings are specified, or if for none of the specified orderings does one
 * node-tuple precede the other, then the relative order of the node-tuples is
 * implementation determined (and may be arbitrary).
 * a list of zero or more columns to include in the tabular view of the query
 * results. If no columns are specified, the columns available in the tabular view
 * are implementation determined, but minimally include, for each selector, a column
 * for each single-valued non-residual property of the selector's node type.
 *
 * The query object model representation of a query is created by factory methods in the QueryObjectModelFactory.
 *
 * @package Extbase
 * @subpackage Persistence\QOM
 * @version $Id: QueryObjectModel.php 1729 2009-11-25 21:37:20Z stucki $
 * @scope prototype
 */
class Tx_Extbase_Persistence_QOM_QueryObjectModel implements Tx_Extbase_Persistence_QOM_QueryObjectModelInterface {

	/**
	 * @var Tx_Extbase_Persistence_DataMapper
	 */
	protected $dataMapper;

	/**
	 * @var Tx_Extbase_Persistence_QOM_SourceInterface
	 */
	protected $source;

	/**
	 * @var Tx_Extbase_Persistence_QOM_ConstraintInterface
	 */
	protected $constraint;

	/**
	 * @var array
	 */
	protected $orderings;

	/**
	 * @var array
	 */
	protected $columns;

	/**
	 * @var Tx_Extbase_Persistence_Storage_BackendInterface
	 */
	protected $storageBackend;

	/**
	 * var integer
	 */
	protected $limit;

	/**
	 * integer
	 */
	protected $offset;

	/**
	 * @var array
	 */
	protected $boundVariables = array();
	
	/**
	 * The query settings
	 * @var Tx_Extbase_Persistence_QuerySettingsInterface
	 */
	protected $querySettings;

	/**
	 * Constructs this QueryObjectModel instance
	 *
	 * @param Tx_Extbase_Persistence_QOM_SourceInterface $selectorOrSource
	 * @param Tx_Extbase_Persistence_QOM_ConstraintInterface $constraint (null if none)
	 * @param array $orderings
	 * @param array $columns
	 */
	public function __construct(Tx_Extbase_Persistence_QOM_SourceInterface $selectorOrSource, $constraint, array $orderings = array(), array $columns = array()) {
		$this->source = $selectorOrSource;
		$this->constraint = $constraint;
		$this->orderings = $orderings;
		$this->columns = $columns;

		if ($this->constraint !== NULL) {
			$this->constraint->collectBoundVariableNames($this->boundVariables);
		}
	}

	/**
	 * Injects the StorageBackend 
	 *
	 * @param Tx_Extbase_Persistence_Storage_BackendInterface $storageBackend
	 * @return void
	 */
	public function injectStorageBackend(Tx_Extbase_Persistence_Storage_BackendInterface $storageBackend) {
		$this->storageBackend = $storageBackend;
	}

	/**
	 * Injects the Data Mapper to map nodes to objects
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
	 * Sets the maximum size of the result set to limit.
	 *
	 * @param integer $limit
	 * @return void
	 */
	public function setLimit($limit) {
		if ($limit < 1 || !is_int($limit)) {
			throw new InvalidArgumentException('setLimit() accepts only integers greater than 0.', 1217244746);
		}
		$this->limit = $limit;
	}

	/**
	 * Returns the maximum size of the result set.
	 *
	 * @return integer
	 */
	public function getLimit() {
		return $this->limit;
	}

	/**
	 * Sets the start offset of the result set to offset.
	 *
	 * @param integer $offset
	 * @return void
	 */
	public function setOffset($offset) {
		if ($offset < 0 || !is_int($offset)) {
			throw new InvalidArgumentException('setOffset() accepts only integers greater than or equal to 0.', 1217245454);
		}
		$this->offset = $offset;
	}

	/**
	 * Returns the start offset of the result set.
	 *
	 * @return integer
	 */
	public function getOffset() {
		return $this->offset;
	}

	/**
	 * Returns the class name the query handles
	 *
	 * @return string The class name
	 */
	public function getSelectorName() {
		$this->source->getSelectorName();
	}

	/**
	 * Gets the node-tuple source for this query.
	 *
	 * @return Tx_Extbase_Persistence_QOM_SourceInterface the node-tuple source; non-null
	*/
	public function getSource() {
		return $this->source;
	}

	/**
	 * Gets the constraint for this query.
	 *
	 * @return Tx_Extbase_Persistence_QOM_ConstraintInterface the constraint, or null if none
	*/
	public function getConstraint() {
		return $this->constraint;
	}

	/**
	 * Gets the orderings for this query.
	 *
	 * @return array an array of zero or more Tx_Extbase_Persistence_QOM_OrderingInterface; non-null
	*/
	public function getOrderings() {
		return $this->orderings;
	}

	/**
	 * Gets the columns for this query.
	 *
	 * @return array an array of zero or more Tx_Extbase_Persistence_QOM_ColumnInterface; non-null
	*/
	public function getColumns() {
		return $this->columns;
	}

	/**
	 * Binds the given value to the variable named $varName.
	 *
	 * @param string $varName name of variable in query
	 * @param Tx_Extbase_Persistence_ValueInterface $value value to bind
	 * @return void
	 * @throws InvalidArgumentException if $varName is not a valid variable in this query.
	 * @throws RepositoryException if an error occurs.
	 */
	public function bindValue($varName, Tx_Extbase_Persistence_ValueInterface $value) {
		if (array_key_exists($varName, $this->boundVariables) === FALSE) {
			throw new InvalidArgumentException('Invalid variable name "' . $varName . '" given to bindValue.', 1217241834);
		}
		$this->boundVariables[$varName] = $value->getString();
	}

	/**
	 * Returns the values of all bound variables.
	 *
	 * @return array()
	 */
	public function getBoundVariableValues() {
		return $this->boundVariables;
	}

	/**
	 * Executes this query and returns a QueryResult object.
	 *
	 * @return Tx_Extbase_Persistence_QueryResultInterface A QueryResult object
	 */
	public function execute() {
		return t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryResult', $this->storageBackend->getRows($this));
	}

	/**
	 * Executes this query and returns the number of tuples matching the query.
	 *
	 * @return int The number of tuples matching the query
	 */
	public function count() {
		return $this->storageBackend->countRows($this);
	}

	/**
	 * Returns the statement defined for this query.
	 * If the language of this query is string-based (like JCR-SQL2), this method
	 * will return the statement that was used to create this query.
	 *
	 * If the language of this query is JCR-JQOM, this method will return the
	 * JCR-SQL2 equivalent of the JCR-JQOM object tree.
	 *
	 * This is the standard serialization of JCR-JQOM and is also the string stored
	 * in the jcr:statement property if the query is persisted. See storeAsNode($absPath).
	 *
	 * @return string the query statement.
	 */
	public function getStatement() {
		$this->storageBackend->getStatement($this);
	}

}
?>