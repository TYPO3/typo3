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

namespace TYPO3\CMS\Core\Database\Query\Restriction;

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

/**
 * Restriction container that applies added restrictions only to the given table aliases.
 * Enforced restrictions are treated equally to all other restrictions.
 */
class LimitToTablesRestrictionContainer implements QueryRestrictionContainerInterface
{
    /**
     * @var QueryRestrictionInterface[]
     */
    private $restrictions = [];

    /**
     * @var QueryRestrictionContainerInterface[]
     */
    private $restrictionContainer = [];

    /**
     * @var array
     */
    private $applicableTableAliases;

    public function removeAll(): QueryRestrictionContainerInterface
    {
        $this->applicableTableAliases = $this->restrictions = $this->restrictionContainer = [];
        return $this;
    }

    public function removeByType(string $restrictionType): QueryRestrictionContainerInterface
    {
        unset($this->applicableTableAliases[$restrictionType], $this->restrictions[$restrictionType]);
        foreach ($this->restrictionContainer as $restrictionContainer) {
            $restrictionContainer->removeByType($restrictionType);
        }
        return $this;
    }

    public function add(QueryRestrictionInterface $restriction): QueryRestrictionContainerInterface
    {
        $this->restrictions[get_class($restriction)] = $restriction;
        if ($restriction instanceof QueryRestrictionContainerInterface) {
            $this->restrictionContainer[get_class($restriction)] = $restriction;
        }
        return $this;
    }

    /**
     * Adds the restriction, but also remembers which table aliases it should be applied to
     *
     * @param QueryRestrictionInterface $restriction
     * @param array $tableAliases flat array of table aliases, not table names
     * @return QueryRestrictionContainerInterface
     */
    public function addForTables(QueryRestrictionInterface $restriction, array $tableAliases): QueryRestrictionContainerInterface
    {
        $this->applicableTableAliases[get_class($restriction)] = $tableAliases;
        return $this->add($restriction);
    }

    /**
     * Main method to build expressions for given tables, but respecting configured filters.
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($this->restrictions as $name => $restriction) {
            $constraints[] = $restriction->buildExpression(
                $this->filterApplicableTableAliases($queriedTables, $name),
                $expressionBuilder
            );
        }
        return $expressionBuilder->andX(...$constraints);
    }

    private function filterApplicableTableAliases(array $queriedTables, string $name): array
    {
        if (!isset($this->applicableTableAliases[$name])) {
            return $queriedTables;
        }

        $filteredTables = [];
        foreach ($this->applicableTableAliases[$name] as $tableAlias) {
            if (isset($queriedTables[$tableAlias])) {
                $filteredTables[$tableAlias] = $queriedTables[$tableAlias];
            }
        }

        return $filteredTables;
    }
}
