<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Database\Query\Restriction;

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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

/**
 * Restriction to respect the soft-delete functionality of TYPO3.
 * Filters out records, that were marked as deleted.
 */
class DeletedRestriction implements QueryRestrictionInterface
{
    /**
     * Main method to build expressions for given tables
     * Evaluates the ctrl/delete flag of the table and adds the according restriction if set
     *
     * @param array $queriedTables Array of tables, where array key is table name and value potentially an alias
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableName => $tableAlias) {
            $deletedFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? null;
            if (!empty($deletedFieldName)) {
                $tablePrefix = $tableAlias ?: $tableName;
                $constraints[] = $expressionBuilder->eq(
                    $tablePrefix . '.' . $deletedFieldName,
                    0
                );
            }
        }
        return $expressionBuilder->andX(...$constraints);
    }
}
