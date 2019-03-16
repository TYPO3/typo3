<?php
declare(strict_types = 1);
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
 * Restriction to make queries for pages doktype-aware.
 */
class DocumentTypeExclusionRestriction implements QueryRestrictionInterface
{
    /**
     * @var int[]
     */
    protected $doktypes;

    /**
     * @param int[]|int $doktype
     */
    public function __construct($doktype)
    {
        if (is_array($doktype)) {
            $this->doktypes = $doktype;
        } else {
            $this->doktypes = [$doktype];
        }
    }

    /**
     * Main method to build expressions for given tables
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];

        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName !== 'pages') {
                continue;
            }

            $constraints[] = $expressionBuilder->notIn($tableAlias . '.doktype', $this->doktypes);
        }

        return $expressionBuilder->andX(...$constraints);
    }
}
