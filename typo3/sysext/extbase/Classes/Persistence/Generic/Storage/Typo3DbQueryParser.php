<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Persistence\Generic\Storage;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Types\Type;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Schema\Capability\RootLevelCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InconsistentQuerySettingsException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\InvalidRelationConfigurationException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\MissingColumnMapException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\RepositoryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Exception\UnsupportedOrderException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap\Relation;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\AndInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ComparisonInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\DynamicOperandInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\EquiJoinCondition;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\JoinInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\LowerCaseInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\NotInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\OrInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\PropertyValueInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SelectorInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\SourceInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Qom\UpperCaseInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Exception\BadConstraintException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * QueryParser, converting the qom to string representation
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
#[Autoconfigure(public: true, shared: false)]
class Typo3DbQueryParser
{
    /**
     * The TYPO3 page repository. Used for language and workspace overlay
     */
    protected ?PageRepository $pageRepository = null;

    /**
     * Instance of the Doctrine query builder
     */
    protected QueryBuilder $queryBuilder;

    /**
     * Maps domain model properties to their corresponding table aliases that are used in the query, e.g.:
     *
     * 'property1' => 'tableName',
     * 'property1.property2' => 'tableName1',
     */
    protected array $tablePropertyMap = [];

    /**
     * Maps tablenames to their aliases to be used in where clauses etc.
     * Mainly used for joins on the same table etc.
     *
     * @var array<string, string>
     */
    protected array $tableAliasMap = [];

    /**
     * Stores all tables used in for SQL joins
     */
    protected array $unionTableAliasCache = [];
    protected string $tableName = '';
    protected bool $suggestDistinctQuery = false;

    public function __construct(
        protected readonly DataMapper $dataMapper,
        protected readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Whether using a distinct query is suggested.
     * This information is defined during parsing of the current query
     * for RELATION_HAS_MANY & RELATION_HAS_AND_BELONGS_TO_MANY relations.
     */
    public function isDistinctQuerySuggested(): bool
    {
        return $this->suggestDistinctQuery;
    }

    /**
     * Returns a ready to be executed QueryBuilder object, based on the query
     */
    public function convertQueryToDoctrineQueryBuilder(QueryInterface $query): QueryBuilder
    {
        // Reset all properties
        $this->tablePropertyMap = [];
        $this->tableAliasMap = [];
        $this->unionTableAliasCache = [];
        $this->tableName = '';

        if ($query->getStatement() && $query->getStatement()->getStatement() instanceof QueryBuilder) {
            $this->queryBuilder = clone $query->getStatement()->getStatement();
            return $this->queryBuilder;
        }
        // Find the right table name
        $source = $query->getSource();
        $this->initializeQueryBuilder($source);

        $constraint = $query->getConstraint();
        if ($constraint instanceof ConstraintInterface) {
            $wherePredicates = $this->parseConstraint($constraint, $source);
            if (!empty($wherePredicates)) {
                $this->queryBuilder->andWhere($wherePredicates);
            }
        }

        $this->parseOrderings($query->getOrderings(), $source);
        $this->addTypo3Constraints($query);

        return $this->queryBuilder;
    }

    /**
     * Creates the queryBuilder object whether it is a regular select or a JOIN
     */
    protected function initializeQueryBuilder(SourceInterface $source): void
    {
        if ($source instanceof SelectorInterface) {
            $className = $source->getNodeTypeName();
            $tableName = $this->dataMapper->getDataMap($className)->tableName;
            $this->tableName = $tableName;
            $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($tableName);

            $this->queryBuilder
                ->getRestrictions()
                ->removeAll();

            $tableAlias = $this->getUniqueAlias($tableName);

            $this->queryBuilder
                ->select($tableAlias . '.*')
                ->from($tableName, $tableAlias);

            $this->addRecordTypeConstraint($className);
        } elseif ($source instanceof JoinInterface) {
            $leftSource = $source->getLeft();
            $leftTableName = $leftSource->getSelectorName();

            $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($leftTableName);
            $leftTableAlias = $this->getUniqueAlias($leftTableName);
            $this->queryBuilder
                ->select($leftTableAlias . '.*')
                ->from($leftTableName, $leftTableAlias);
            $this->parseJoin($source, $leftTableAlias);
        }
    }

    /**
     * Transforms a constraint into SQL and parameter arrays
     */
    protected function parseConstraint(ConstraintInterface $constraint, SourceInterface $source): CompositeExpression|string
    {
        if ($constraint instanceof AndInterface) {
            return $this->queryBuilder->expr()->and(
                $this->parseConstraint($constraint->getConstraint1(), $source),
                $this->parseConstraint($constraint->getConstraint2(), $source)
            );
        }
        if ($constraint instanceof OrInterface) {
            return $this->queryBuilder->expr()->or(
                $this->parseConstraint($constraint->getConstraint1(), $source),
                $this->parseConstraint($constraint->getConstraint2(), $source)
            );
        }
        if ($constraint instanceof NotInterface) {
            return ' NOT(' . $this->parseConstraint($constraint->getConstraint(), $source) . ')';
        }
        if ($constraint instanceof ComparisonInterface) {
            return $this->parseComparison($constraint, $source);
        }
        throw new \RuntimeException('not implemented', 1476199898);
    }

    /**
     * Transforms orderings into SQL.
     *
     * @param array $orderings An array of orderings (Qom\Ordering)
     * @throws UnsupportedOrderException
     */
    protected function parseOrderings(array $orderings, SourceInterface $source): void
    {
        foreach ($orderings as $propertyName => $order) {
            if ($order !== QueryInterface::ORDER_ASCENDING && $order !== QueryInterface::ORDER_DESCENDING) {
                throw new UnsupportedOrderException('Unsupported order encountered.', 1242816074);
            }
            $className = null;
            $tableName = '';
            if ($source instanceof SelectorInterface) {
                $className = $source->getNodeTypeName();
                $tableName = $this->dataMapper->convertClassNameToTableName($className);
                $fullPropertyPath = '';
                while (str_contains($propertyName, '.')) {
                    $this->addUnionStatement($className, $tableName, $propertyName, $fullPropertyPath);
                }
            } elseif ($source instanceof JoinInterface) {
                $tableName = $source->getLeft()->getSelectorName();
            }
            $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
            if ($tableName !== '') {
                $this->queryBuilder->addOrderBy($tableName . '.' . $columnName, $order);
            } else {
                $this->queryBuilder->addOrderBy($columnName, $order);
            }
        }
    }

    /**
     * add TYPO3 Constraints for all tables to the queryBuilder
     */
    protected function addTypo3Constraints(QueryInterface $query): void
    {
        $index = 0;
        foreach ($this->tableAliasMap as $tableAlias => $tableName) {
            if ($index === 0) {
                // We only add the pid and language check for the first table (aggregate root).
                // We know the first table is always the main table for the current query run.
                $additionalWhereClauses = $this->getAdditionalWhereClause($query->getQuerySettings(), $tableName, $tableAlias);
            } else {
                $additionalWhereClauses = [];
            }
            $index++;
            $statement = $this->getVisibilityConstraintStatement($query->getQuerySettings(), $tableName, $tableAlias);
            if ($statement !== '') {
                $additionalWhereClauses[] = $statement;
            }
            if (!empty($additionalWhereClauses)) {
                if (in_array($tableAlias, $this->unionTableAliasCache, true)) {
                    $this->queryBuilder->andWhere(
                        $this->queryBuilder->expr()->or(
                            $this->queryBuilder->expr()->and(...$additionalWhereClauses),
                            $this->queryBuilder->expr()->isNull($tableAlias . '.uid')
                        )
                    );
                } else {
                    $this->queryBuilder->andWhere(...$additionalWhereClauses);
                }
            }
        }
    }

    /**
     * Parse a Comparison into SQL and parameter arrays.
     *
     * @throws RepositoryException
     * @throws BadConstraintException
     */
    protected function parseComparison(ComparisonInterface $comparison, SourceInterface $source): string
    {
        if ($comparison->getOperator() === QueryInterface::OPERATOR_CONTAINS) {
            if ($comparison->getOperand2() === null) {
                throw new BadConstraintException('The value for the CONTAINS operator must not be null.', 1484828468);
            }
            $value = $this->dataMapper->getPlainValue($comparison->getOperand2());
            if (!$source instanceof SelectorInterface) {
                throw new \RuntimeException('Source is not of type "SelectorInterface"', 1395362539);
            }
            $className = $source->getNodeTypeName();
            $tableName = $this->dataMapper->convertClassNameToTableName($className);
            $operand1 = $comparison->getOperand1();
            $propertyName = $operand1->getPropertyName();
            $fullPropertyPath = '';
            while (str_contains($propertyName, '.')) {
                $this->addUnionStatement($className, $tableName, $propertyName, $fullPropertyPath);
            }
            $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
            $dataMap = $this->dataMapper->getDataMap($className);
            $columnMap = $dataMap->getColumnMap($propertyName);
            $typeOfRelation = $columnMap instanceof ColumnMap ? $columnMap->typeOfRelation : null;
            if ($typeOfRelation === Relation::HAS_AND_BELONGS_TO_MANY) {
                /** @var ColumnMap $columnMap */
                $relationTableName = (string)$columnMap->relationTableName;
                $queryBuilderForSubselect = $this->queryBuilder->getConnection()->createQueryBuilder();
                $queryBuilderForSubselect
                    ->select($columnMap->parentKeyFieldName)
                    ->from($relationTableName)
                    ->where(
                        $queryBuilderForSubselect->expr()->eq(
                            $columnMap->childKeyFieldName,
                            $this->queryBuilder->createNamedParameter($value)
                        )
                    );
                $additionalWhereForMatchFields = $this->getAdditionalMatchFieldsStatement($queryBuilderForSubselect->expr(), $columnMap, $relationTableName, $relationTableName);
                if ($additionalWhereForMatchFields) {
                    $queryBuilderForSubselect->andWhere($additionalWhereForMatchFields);
                }

                return $this->queryBuilder->expr()->comparison(
                    $this->queryBuilder->quoteIdentifier($tableName . '.uid'),
                    'IN',
                    '(' . $queryBuilderForSubselect->getSQL() . ')'
                );
            }
            if ($typeOfRelation === Relation::HAS_MANY) {
                if (isset($columnMap->parentKeyFieldName)) {
                    $childTableName = $columnMap->childTableName;
                    // Build the SQL statement of the subselect
                    $queryBuilderForSubselect = $this->queryBuilder->getConnection()->createQueryBuilder();
                    $queryBuilderForSubselect
                        ->select($columnMap->parentKeyFieldName)
                        ->from($childTableName)
                        ->where(
                            $queryBuilderForSubselect->expr()->eq(
                                'uid',
                                (int)$value
                            )
                        );
                    // Add it to the main query
                    return $this->queryBuilder->expr()->eq(
                        $tableName . '.uid',
                        '(' . $queryBuilderForSubselect->getSQL() . ')'
                    );
                }
                return $this->queryBuilder->expr()->inSet(
                    $tableName . '.' . $columnName,
                    $this->queryBuilder->quote((string)$value)
                );
            }
            throw new RepositoryException('Unsupported or non-existing property name "' . $propertyName . '" used in relation matching.', 1327065745);
        }
        return $this->parseDynamicOperand($comparison, $source);
    }

    /**
     * Parse a DynamicOperand into SQL and parameter arrays.
     *
     * @throws Exception
     * @throws BadConstraintException
     */
    protected function parseDynamicOperand(ComparisonInterface $comparison, SourceInterface $source): string
    {
        $value = $comparison->getOperand2();
        $fieldName = $this->parseOperand($comparison->getOperand1(), $source);
        $exprBuilder = $this->queryBuilder->expr();
        switch ($comparison->getOperator()) {
            case QueryInterface::OPERATOR_IN:
                $hasValue = false;
                $plainValues = [];
                foreach ($value as $singleValue) {
                    $plainValue = $this->dataMapper->getPlainValue($singleValue);
                    if ($plainValue !== null) {
                        $hasValue = true;
                        $plainValues[] = $this->createTypedNamedParameter($singleValue);
                    }
                }
                if (!$hasValue) {
                    throw new BadConstraintException(
                        'The IN operator needs a non-empty value list to compare against. ' .
                        'The given value list is empty.',
                        1484828466
                    );
                }
                $expr = $exprBuilder->comparison($fieldName, 'IN', '(' . implode(', ', $plainValues) . ')');
                break;
            case QueryInterface::OPERATOR_EQUAL_TO:
                if ($value === null) {
                    $expr = $fieldName . ' IS NULL';
                } else {
                    $placeHolder = $this->createTypedNamedParameter($value);
                    $expr = $exprBuilder->comparison($fieldName, $exprBuilder::EQ, $placeHolder);
                }
                break;
            case QueryInterface::OPERATOR_EQUAL_TO_NULL:
                $expr = $fieldName . ' IS NULL';
                break;
            case QueryInterface::OPERATOR_NOT_EQUAL_TO:
                if ($value === null) {
                    $expr = $fieldName . ' IS NOT NULL';
                } else {
                    $placeHolder = $this->createTypedNamedParameter($value);
                    $expr = $exprBuilder->comparison($fieldName, $exprBuilder::NEQ, $placeHolder);
                }
                break;
            case QueryInterface::OPERATOR_NOT_EQUAL_TO_NULL:
                $expr = $fieldName . ' IS NOT NULL';
                break;
            case QueryInterface::OPERATOR_LESS_THAN:
                $placeHolder = $this->createTypedNamedParameter($value);
                $expr = $exprBuilder->comparison($fieldName, $exprBuilder::LT, $placeHolder);
                break;
            case QueryInterface::OPERATOR_LESS_THAN_OR_EQUAL_TO:
                $placeHolder = $this->createTypedNamedParameter($value);
                $expr = $exprBuilder->comparison($fieldName, $exprBuilder::LTE, $placeHolder);
                break;
            case QueryInterface::OPERATOR_GREATER_THAN:
                $placeHolder = $this->createTypedNamedParameter($value);
                $expr = $exprBuilder->comparison($fieldName, $exprBuilder::GT, $placeHolder);
                break;
            case QueryInterface::OPERATOR_GREATER_THAN_OR_EQUAL_TO:
                $placeHolder = $this->createTypedNamedParameter($value);
                $expr = $exprBuilder->comparison($fieldName, $exprBuilder::GTE, $placeHolder);
                break;
            case QueryInterface::OPERATOR_LIKE:
                $placeHolder = $this->createTypedNamedParameter($value, Connection::PARAM_STR);
                if ($this->queryBuilder->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
                    $expr = $exprBuilder->comparison($fieldName, 'ILIKE', $placeHolder);
                } else {
                    $expr = $exprBuilder->comparison($fieldName, 'LIKE', $placeHolder);
                }
                break;
            default:
                throw new Exception(
                    'Unsupported operator encountered.',
                    1242816073
                );
        }
        return $expr;
    }

    /**
     * Maps plain value of operand to PDO types to help Doctrine and/or the database driver process the value
     * correctly when building the query.
     */
    protected function getParameterType(mixed $value): ParameterType
    {
        $parameterType = gettype($value);
        return match ($parameterType) {
            'integer' => Connection::PARAM_INT,
            'string' => Connection::PARAM_STR,
            default => throw new \InvalidArgumentException(
                'Unsupported parameter type encountered. Expected integer or string, ' . $parameterType . ' given.',
                1494878863
            ),
        };
    }

    /**
     * Create a named parameter for the QueryBuilder and guess the parameter type based on the
     * output of DataMapper::getPlainValue(). The type of the named parameter can be forced to
     * one of the \PDO::PARAM_* types by specifying the $forceType argument.
     *
     * @param mixed $value The input value that should be sent to the database
     * @param ParameterType|Type|ArrayParameterType|null $forceType The \TYPO3\CMS\Core\Database\Connection::PARAM_* type that should be forced
     * @return string The placeholder string to be used in the query
     * @see \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::getPlainValue()
     */
    protected function createTypedNamedParameter(mixed $value, ParameterType|Type|ArrayParameterType|null $forceType = null): string
    {
        if ($value instanceof DomainObjectInterface
            && $value->_hasProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID)
            && $value->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID) > 0
        ) {
            $plainValue = (int)$value->_getProperty(AbstractDomainObject::PROPERTY_LOCALIZED_UID);
        } else {
            $plainValue = $this->dataMapper->getPlainValue($value);
        }
        $parameterType = $forceType ?? $this->getParameterType($plainValue);
        return $this->queryBuilder->createNamedParameter($plainValue, $parameterType);
    }

    protected function parseOperand(DynamicOperandInterface $operand, SourceInterface $source): string
    {
        $tableName = null;
        if ($operand instanceof LowerCaseInterface) {
            $constraintSQL = 'LOWER(' . $this->parseOperand($operand->getOperand(), $source) . ')';
        } elseif ($operand instanceof UpperCaseInterface) {
            $constraintSQL = 'UPPER(' . $this->parseOperand($operand->getOperand(), $source) . ')';
        } elseif ($operand instanceof PropertyValueInterface) {
            $propertyName = $operand->getPropertyName();
            $className = '';
            if ($source instanceof SelectorInterface) {
                $className = $source->getNodeTypeName();
                $tableName = $this->dataMapper->convertClassNameToTableName($className);
                $fullPropertyPath = '';
                while (str_contains($propertyName, '.')) {
                    $this->addUnionStatement($className, $tableName, $propertyName, $fullPropertyPath);
                }
            } elseif ($source instanceof JoinInterface) {
                $tableName = $source->getJoinCondition()->getSelector1Name();
            }
            $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
            $constraintSQL = (!empty($tableName) ? $tableName . '.' : '') . $columnName;
            $constraintSQL = $this->queryBuilder->getConnection()->quoteIdentifier($constraintSQL);
        } else {
            throw new \InvalidArgumentException('Given operand has invalid type "' . get_class($operand) . '".', 1395710211);
        }
        return $constraintSQL;
    }

    /**
     * Add a constraint to ensure that the record type of the returned tuples is matching the data type of the repository.
     *
     * @param string|null $className The class name
     */
    protected function addRecordTypeConstraint(?string $className): void
    {
        if ($className !== null) {
            $dataMap = $this->dataMapper->getDataMap($className);
            if ($dataMap->recordTypeColumnName !== null) {
                $recordTypes = [];
                if ($dataMap->recordType !== null) {
                    $recordTypes[] = $dataMap->recordType;
                }
                foreach ($dataMap->subclasses as $subclassName) {
                    $subclassDataMap = $this->dataMapper->getDataMap($subclassName);
                    if ($subclassDataMap->recordType !== null) {
                        $recordTypes[] = $subclassDataMap->recordType;
                    }
                }
                if (!empty($recordTypes)) {
                    $recordTypeStatements = [];
                    foreach ($recordTypes as $recordType) {
                        $recordTypeStatements[] = $this->queryBuilder->expr()->eq(
                            $dataMap->tableName . '.' . $dataMap->recordTypeColumnName,
                            $this->queryBuilder->createNamedParameter($recordType)
                        );
                    }
                    $this->queryBuilder->andWhere(
                        $this->queryBuilder->expr()->or(...$recordTypeStatements)
                    );
                }
            }
        }
    }

    /**
     * Builds a condition for filtering records by the configured match field,
     * e.g. MM_match_fields, foreign_match_fields or foreign_table_field.
     *
     * @param ExpressionBuilder $exprBuilder
     * @param ColumnMap $columnMap The column man for which the condition should be build.
     * @param string $childTableAlias The alias of the child record table used in the query.
     * @param string $parentTable The real name of the parent table (used for building the foreign_table_field condition).
     * @return CompositeExpression|string The match field conditions or an empty string.
     */
    protected function getAdditionalMatchFieldsStatement($exprBuilder, $columnMap, $childTableAlias, $parentTable = null)
    {
        $additionalWhereForMatchFields = [];
        foreach ($columnMap->relationTableMatchFields as $fieldName => $value) {
            $additionalWhereForMatchFields[] = $exprBuilder->eq(
                $childTableAlias . '.' . $fieldName,
                $this->queryBuilder->createNamedParameter($value)
            );
        }
        if (isset($parentTable)) {
            if (!empty($columnMap->parentTableFieldName)) {
                $additionalWhereForMatchFields[] = $exprBuilder->eq(
                    $childTableAlias . '.' . $columnMap->parentTableFieldName,
                    $this->queryBuilder->createNamedParameter($parentTable)
                );
            }
        }
        if (!empty($additionalWhereForMatchFields)) {
            return $exprBuilder->and(...$additionalWhereForMatchFields);
        }
        return '';
    }

    /**
     * Adds additional WHERE statements according to the query settings.
     *
     * @param QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
     * @param string $tableName The table name to add the additional where clause for
     * @param string $tableAlias The table alias used in the query.
     */
    protected function getAdditionalWhereClause(QuerySettingsInterface $querySettings, string $tableName, string $tableAlias): array
    {
        $whereClause = [];
        if ($querySettings->getRespectSysLanguage()) {
            $systemLanguageStatement = $this->getLanguageStatement($tableName, $tableAlias, $querySettings);
            if (!empty($systemLanguageStatement)) {
                $whereClause[] = $systemLanguageStatement;
            }
        }

        if ($querySettings->getRespectStoragePage()) {
            $pageIdStatement = $this->getPageIdStatement($tableName, $tableAlias, $querySettings->getStoragePageIds());
            if (!empty($pageIdStatement)) {
                $whereClause[] = $pageIdStatement;
            }
        }
        if ($this->tcaSchemaFactory->has($tableName) && $this->tcaSchemaFactory->get($tableName)->isWorkspaceAware()) {
            // Always prevent workspace records from being returned (except for newly created records)
            $whereClause[] = $this->queryBuilder->expr()->eq($tableAlias . '.t3ver_oid', 0);
        }

        return $whereClause;
    }

    /**
     * Adds enableFields and deletedClause to the query if necessary
     */
    protected function getVisibilityConstraintStatement(QuerySettingsInterface $querySettings, string $tableName, string $tableAlias): string
    {
        if (!$this->tcaSchemaFactory->has($tableName)) {
            return '';
        }

        $ignoreEnableFields = $querySettings->getIgnoreEnableFields();
        $enableFieldsToBeIgnored = $querySettings->getEnableFieldsToBeIgnored();
        $includeDeleted = $querySettings->getIncludeDeleted();
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            $statement = $this->getFrontendConstraintStatement($tableName, $tableAlias, $ignoreEnableFields, $enableFieldsToBeIgnored, $includeDeleted);
        } else {
            // applicationType backend
            $statement = $this->getBackendConstraintStatement($tableName, $ignoreEnableFields, $includeDeleted);
            if (!empty($statement)) {
                $statement = $this->replaceTableNameWithAlias($statement, $tableName, $tableAlias);
                $statement = strtolower(substr($statement, 1, 3)) === 'and' ? substr($statement, 5) : $statement;
            }
        }
        return $statement;
    }

    /**
     * Returns constraint statement for frontend context
     *
     * @param bool $ignoreEnableFields A flag indicating whether the enable fields should be ignored
     * @param array $enableFieldsToBeIgnored If $ignoreEnableFields is true, this array specifies enable fields to be ignored. If it is NULL or an empty array (default) all enable fields are ignored.
     * @param bool $includeDeleted A flag indicating whether deleted records should be included
     * @throws InconsistentQuerySettingsException
     */
    protected function getFrontendConstraintStatement(string $tableName, string $tableAlias, bool $ignoreEnableFields, array $enableFieldsToBeIgnored, bool $includeDeleted): string
    {
        $statement = '';
        if ($ignoreEnableFields && !$includeDeleted) {
            if (!empty($enableFieldsToBeIgnored)) {
                $constraints = $this->getPageRepository()->getDefaultConstraints($tableName, $enableFieldsToBeIgnored, $tableAlias);
                if ($constraints !== []) {
                    $statement = implode(' AND ', $constraints);
                }
            } else {
                $schema = $this->tcaSchemaFactory->has($tableName) ? $this->tcaSchemaFactory->get($tableName) : null;
                if ($schema?->hasCapability(TcaSchemaCapability::SoftDelete)) {
                    $deleteField = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
                    $statement = $tableAlias . '.' . $deleteField . '=0';
                }
            }
        } elseif (!$ignoreEnableFields && !$includeDeleted) {
            $constraints = $this->getPageRepository()->getDefaultConstraints($tableName, [], $tableAlias);
            if ($constraints !== []) {
                $statement = implode(' AND ', $constraints);
            }
        } elseif (!$ignoreEnableFields) {
            throw new InconsistentQuerySettingsException('Query setting "ignoreEnableFields=FALSE" can not be used together with "includeDeleted=TRUE" in frontend context.', 1460975922);
        }
        return $statement;
    }

    /**
     * Returns constraint statement for backend context
     *
     * @param string $tableName
     * @param bool $ignoreEnableFields A flag indicating whether the enable fields should be ignored
     * @param bool $includeDeleted A flag indicating whether deleted records should be included
     * @return string
     */
    protected function getBackendConstraintStatement(string $tableName, bool $ignoreEnableFields, bool $includeDeleted): string
    {
        $statement = '';
        // In case of versioning-preview, enableFields are ignored (checked in Typo3DbBackend::doLanguageAndWorkspaceOverlay)
        $isUserInWorkspace = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('workspace', 'isOffline');
        if (!$ignoreEnableFields && !$isUserInWorkspace) {
            $statement .= BackendUtility::BEenableFields($tableName);
        }
        $schema = $this->tcaSchemaFactory->has($tableName) ? $this->tcaSchemaFactory->get($tableName) : null;
        if (!$includeDeleted && $schema?->hasCapability(TcaSchemaCapability::SoftDelete)) {
            $deleteField = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
            $statement .= ' AND ' . $tableName . '.' . $deleteField . '=0';
        }
        return $statement;
    }

    /**
     * Builds the language field statement
     *
     * @param string $tableName The database table name
     * @param string $tableAlias The table alias used in the query.
     * @param QuerySettingsInterface $querySettings The TYPO3 CMS specific query settings
     * @return CompositeExpression|string
     */
    protected function getLanguageStatement(string $tableName, string $tableAlias, QuerySettingsInterface $querySettings)
    {
        if (!$this->tcaSchemaFactory->has($tableName)) {
            return '';
        }
        $schema = $this->tcaSchemaFactory->get($tableName);
        if (!$schema->isLanguageAware()) {
            return '';
        }
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);

        // Select all entries for the current language
        // If any language is set -> get those entries which are not translated yet
        // They will be removed by \TYPO3\CMS\Core\Domain\Repository\PageRepository::getRecordOverlay if not matching overlay mode
        $languageField = $languageCapability->getLanguageField()->getName();
        $transOrigPointerField = $languageCapability->getTranslationOriginPointerField()->getName();

        $languageAspect = $querySettings->getLanguageAspect();
        if (!$languageAspect->getContentId()) {
            return $this->queryBuilder->expr()->in(
                $tableAlias . '.' . $languageField,
                [$languageAspect->getContentId(), -1]
            );
        }

        if (!$languageAspect->doOverlays()) {
            return $this->queryBuilder->expr()->in(
                $tableAlias . '.' . $languageField,
                [$languageAspect->getContentId(), -1]
            );
        }

        $defLangTableAlias = $tableAlias . '_dl';
        $defaultLanguageRecordsSubSelect = $this->queryBuilder->getConnection()->createQueryBuilder();
        $defaultLanguageRecordsSubSelect
            ->select($defLangTableAlias . '.uid')
            ->from($tableName, $defLangTableAlias)
            ->where(
                $defaultLanguageRecordsSubSelect->expr()->and(
                    $defaultLanguageRecordsSubSelect->expr()->eq($defLangTableAlias . '.' . $transOrigPointerField, 0),
                    $defaultLanguageRecordsSubSelect->expr()->eq($defLangTableAlias . '.' . $languageField, 0)
                )
            );

        $andConditions = [];
        // records in language 'all'
        $andConditions[] = $this->queryBuilder->expr()->eq($tableAlias . '.' . $languageField, -1);
        // translated records where a default language exists
        $andConditions[] = $this->queryBuilder->expr()->and(
            $this->queryBuilder->expr()->eq($tableAlias . '.' . $languageField, $languageAspect->getContentId()),
            $this->queryBuilder->expr()->in(
                $tableAlias . '.' . $transOrigPointerField,
                $defaultLanguageRecordsSubSelect->getSQL()
            )
        );
        // Records in translation with no default language
        if ($languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_ON_WITH_FLOATING) {
            $andConditions[] = $this->queryBuilder->expr()->and(
                $this->queryBuilder->expr()->eq($tableAlias . '.' . $languageField, $languageAspect->getContentId()),
                $this->queryBuilder->expr()->eq($tableAlias . '.' . $transOrigPointerField, 0),
                $this->queryBuilder->expr()->notIn(
                    $tableAlias . '.' . $transOrigPointerField,
                    $defaultLanguageRecordsSubSelect->getSQL()
                )
            );
        }
        if ($languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_MIXED) {
            // returns records from current language which have a default language
            // together with not translated default language records
            $translatedOnlyTableAlias = $tableAlias . '_to';
            $queryBuilderForSubselect = $this->queryBuilder->getConnection()->createQueryBuilder();
            $queryBuilderForSubselect
                ->select($translatedOnlyTableAlias . '.' . $transOrigPointerField)
                ->from($tableName, $translatedOnlyTableAlias)
                ->where(
                    $queryBuilderForSubselect->expr()->and(
                        $queryBuilderForSubselect->expr()->gt($translatedOnlyTableAlias . '.' . $transOrigPointerField, 0),
                        $queryBuilderForSubselect->expr()->eq($translatedOnlyTableAlias . '.' . $languageField, $languageAspect->getContentId())
                    )
                );
            // records in default language, which do not have a translation
            $andConditions[] = $this->queryBuilder->expr()->and(
                $this->queryBuilder->expr()->eq($tableAlias . '.' . $languageField, 0),
                $this->queryBuilder->expr()->notIn(
                    $tableAlias . '.uid',
                    $queryBuilderForSubselect->getSQL()
                )
            );
        }

        return $this->queryBuilder->expr()->or(...$andConditions);
    }

    /**
     * Builds the page ID checking statement
     *
     * @param string $tableName The database table name
     * @param string $tableAlias The table alias used in the query.
     * @param array $storagePageIds list of storage page ids
     * @throws InconsistentQuerySettingsException
     */
    protected function getPageIdStatement(string $tableName, string $tableAlias, array $storagePageIds): string
    {
        if (!$this->tcaSchemaFactory->has($tableName)) {
            return '';
        }

        /** @var RootLevelCapability $rootLevelCapability */
        $rootLevelCapability = $this->tcaSchemaFactory->get($tableName)->getCapability(TcaSchemaCapability::RestrictionRootLevel);
        switch ($rootLevelCapability->getRootLevelType()) {
            // Only in pid 0
            case RootLevelCapability::TYPE_ONLY_ON_ROOTLEVEL:
                $storagePageIds = [0];
                break;
                // Pid 0 and pagetree
            case RootLevelCapability::TYPE_BOTH:
                if ($storagePageIds === []) {
                    $storagePageIds = [0];
                } else {
                    $storagePageIds[] = 0;
                }
                break;
                // Only pagetree or not set
            case RootLevelCapability::TYPE_ONLY_ON_PAGES:
                if (empty($storagePageIds)) {
                    throw new InconsistentQuerySettingsException('Missing storage page ids.', 1365779762);
                }
                break;
                // Invalid configuration
            default:
                return '';
        }
        $storagePageIds = array_map(intval(...), $storagePageIds);
        if (count($storagePageIds) === 1) {
            return $this->queryBuilder->expr()->eq($tableAlias . '.pid', reset($storagePageIds));
        }
        return $this->queryBuilder->expr()->in($tableAlias . '.pid', $storagePageIds);
    }

    /**
     * Transforms a Join into SQL and parameter arrays
     */
    protected function parseJoin(JoinInterface $join, string $leftTableAlias): void
    {
        $leftSource = $join->getLeft();
        $leftClassName = $leftSource->getNodeTypeName();
        $this->addRecordTypeConstraint($leftClassName);
        $rightSource = $join->getRight();
        if ($rightSource instanceof JoinInterface) {
            $left = $rightSource->getLeft();
            $rightClassName = $left->getNodeTypeName();
            $rightTableName = $left->getSelectorName();
        } else {
            $rightClassName = $rightSource->getNodeTypeName();
            $rightTableName = $rightSource->getSelectorName();
            $this->queryBuilder->addSelect($rightTableName . '.*');
        }
        $this->addRecordTypeConstraint($rightClassName);
        $rightTableAlias = $this->getUniqueAlias($rightTableName);
        $joinCondition = $join->getJoinCondition();
        $joinConditionExpression = null;
        if ($joinCondition instanceof EquiJoinCondition) {
            $column1Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty1Name(), $leftClassName);
            $column2Name = $this->dataMapper->convertPropertyNameToColumnName($joinCondition->getProperty2Name(), $rightClassName);

            $joinConditionExpression = $this->queryBuilder->expr()->eq(
                $leftTableAlias . '.' . $column1Name,
                $this->queryBuilder->quoteIdentifier($rightTableAlias . '.' . $column2Name)
            );
        }
        $this->queryBuilder->leftJoin($leftTableAlias, $rightTableName, $rightTableAlias, $joinConditionExpression);
        if ($rightSource instanceof JoinInterface) {
            $this->parseJoin($rightSource, $rightTableAlias);
        }
    }

    /**
     * Generates a unique alias for the given table and the given property path.
     * The property path will be mapped to the generated alias in the tablePropertyMap.
     *
     * @param string $tableName The name of the table for which the alias should be generated.
     * @param string|null $fullPropertyPath The full property path that is related to the given table.
     * @return string The generated table alias.
     */
    protected function getUniqueAlias(string $tableName, ?string $fullPropertyPath = null): string
    {
        if (isset($fullPropertyPath) && isset($this->tablePropertyMap[$fullPropertyPath])) {
            return $this->tablePropertyMap[$fullPropertyPath];
        }
        $alias = $tableName;
        $i = 0;
        while (isset($this->tableAliasMap[$alias])) {
            $alias = $tableName . $i;
            $i++;
        }
        $this->tableAliasMap[$alias] = $tableName;
        if (isset($fullPropertyPath)) {
            $this->tablePropertyMap[$fullPropertyPath] = $alias;
        }
        return $alias;
    }

    /**
     * adds a union statement to the query, mostly for tables referenced in the where condition.
     * The property for which the union statement is generated will be appended.
     *
     * @param string $className The name of the parent class, will be set to the child class after processing.
     * @param string $tableName The name of the parent table, will be set to the table alias that is used in the union statement.
     * @param string $propertyPath The remaining property path, will be cut of by one part during the process.
     * @param string $fullPropertyPath The full path the current property, will be used to make table names unique.
     * @throws Exception
     * @throws InvalidRelationConfigurationException
     * @throws MissingColumnMapException
     */
    protected function addUnionStatement(&$className, &$tableName, &$propertyPath, &$fullPropertyPath)
    {
        $explodedPropertyPath = explode('.', $propertyPath, 2);
        $propertyName = $explodedPropertyPath[0];
        $columnName = $this->dataMapper->convertPropertyNameToColumnName($propertyName, $className);
        $realTableName = $this->dataMapper->convertClassNameToTableName($className);
        $tableName = $this->tablePropertyMap[$fullPropertyPath] ?? $realTableName;
        $columnMap = $this->dataMapper->getDataMap($className)->getColumnMap($propertyName);

        if ($columnMap === null) {
            throw new MissingColumnMapException('The ColumnMap for property "' . $propertyName . '" of class "' . $className . '" is missing.', 1355142232);
        }

        $parentKeyFieldName = $columnMap->parentKeyFieldName;
        $childTableName = $columnMap->childTableName;

        if ($childTableName === null) {
            throw new InvalidRelationConfigurationException('The relation information for property "' . $propertyName . '" of class "' . $className . '" is missing.', 1353170925);
        }

        $fullPropertyPath .= ($fullPropertyPath === '') ? $propertyName : '.' . $propertyName;
        $childTableAlias = $this->getUniqueAlias($childTableName, $fullPropertyPath);

        // If there is already a union with the current identifier we do not need to build it again and exit early.
        if (in_array($childTableAlias, $this->unionTableAliasCache, true)) {
            $propertyPath = $explodedPropertyPath[1];
            $tableName = $childTableAlias;
            $className = $this->dataMapper->getType($className, $propertyName);
            return;
        }

        if ($columnMap->typeOfRelation === Relation::HAS_ONE) {
            if (isset($parentKeyFieldName)) {
                // @todo: no test for this part yet
                $basicJoinCondition = $this->queryBuilder->expr()->eq(
                    $tableName . '.uid',
                    $this->queryBuilder->quoteIdentifier($childTableAlias . '.' . $parentKeyFieldName)
                );
            } else {
                $basicJoinCondition = $this->queryBuilder->expr()->eq(
                    $tableName . '.' . $columnName,
                    $this->queryBuilder->quoteIdentifier($childTableAlias . '.uid')
                );
            }
            $joinConditionExpression = $this->queryBuilder->expr()->and(
                $basicJoinCondition,
                $this->getAdditionalMatchFieldsStatement($this->queryBuilder->expr(), $columnMap, $childTableAlias, $realTableName)
            );
            $this->queryBuilder->leftJoin($tableName, $childTableName, $childTableAlias, (string)$joinConditionExpression);
            $this->unionTableAliasCache[] = $childTableAlias;
        } elseif ($columnMap->typeOfRelation === Relation::HAS_MANY) {
            if (isset($parentKeyFieldName)) {
                $basicJoinCondition = $this->queryBuilder->expr()->eq(
                    $tableName . '.uid',
                    $this->queryBuilder->quoteIdentifier($childTableAlias . '.' . $parentKeyFieldName)
                );
            } else {
                $basicJoinCondition = $this->queryBuilder->expr()->inSet(
                    $tableName . '.' . $columnName,
                    $this->queryBuilder->quoteIdentifier($childTableAlias . '.uid'),
                    true
                );
            }
            $joinConditionExpression = $this->queryBuilder->expr()->and(
                $basicJoinCondition,
                $this->getAdditionalMatchFieldsStatement($this->queryBuilder->expr(), $columnMap, $childTableAlias, $realTableName)
            );
            $this->queryBuilder->leftJoin($tableName, $childTableName, $childTableAlias, (string)$joinConditionExpression);
            $this->unionTableAliasCache[] = $childTableAlias;
            $this->suggestDistinctQuery = true;
        } elseif ($columnMap->typeOfRelation === Relation::HAS_AND_BELONGS_TO_MANY) {
            $relationTableName = (string)$columnMap->relationTableName;
            $relationTableAlias = $this->getUniqueAlias($relationTableName, $fullPropertyPath . '_mm');

            $joinConditionExpression = $this->queryBuilder->expr()->and(
                $this->queryBuilder->expr()->eq(
                    $tableName . '.uid',
                    $this->queryBuilder->quoteIdentifier(
                        $relationTableAlias . '.' . $columnMap->parentKeyFieldName
                    )
                ),
                $this->getAdditionalMatchFieldsStatement($this->queryBuilder->expr(), $columnMap, $relationTableAlias, $realTableName)
            );
            $this->queryBuilder->leftJoin($tableName, $relationTableName, $relationTableAlias, (string)$joinConditionExpression);
            $joinConditionExpression = $this->queryBuilder->expr()->eq(
                $relationTableAlias . '.' . $columnMap->childKeyFieldName,
                $this->queryBuilder->quoteIdentifier($childTableAlias . '.uid')
            );
            $this->queryBuilder->leftJoin($relationTableAlias, $childTableName, $childTableAlias, $joinConditionExpression);
            $this->unionTableAliasCache[] = $childTableAlias;
            $this->suggestDistinctQuery = true;
        } else {
            throw new Exception('Could not determine type of relation.', 1252502725);
        }
        $propertyPath = $explodedPropertyPath[1];
        $tableName = $childTableAlias;
        $className = $this->dataMapper->getType($className, $propertyName);
    }

    /**
     * If the table name does not match the table alias all occurrences of
     * "tableName." are replaced with "tableAlias." in the given SQL statement.
     *
     * @param string $statement The SQL statement in which the values are replaced.
     * @param string $tableName The table name that is replaced.
     * @param string $tableAlias The table alias that replaced the table name.
     * @return string The modified SQL statement.
     */
    protected function replaceTableNameWithAlias($statement, $tableName, $tableAlias)
    {
        if ($tableAlias !== $tableName) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
            $quotedTableName = $connection->quoteIdentifier($tableName);
            $quotedTableAlias = $connection->quoteIdentifier($tableAlias);
            $statement = str_replace(
                [$tableName . '.', $quotedTableName . '.'],
                [$tableAlias . '.', $quotedTableAlias . '.'],
                $statement
            );
        }

        return $statement;
    }

    protected function getPageRepository(): PageRepository
    {
        if (!$this->pageRepository instanceof PageRepository) {
            $this->pageRepository = GeneralUtility::makeInstance(PageRepository::class);
        }
        return $this->pageRepository;
    }
}
