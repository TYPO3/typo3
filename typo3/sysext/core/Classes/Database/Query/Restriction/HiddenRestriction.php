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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Restriction to filter records that have been marked as hidden
 */
class HiddenRestriction implements QueryRestrictionInterface
{
    protected TcaSchemaFactory $tcaSchemaFactory;

    public function __construct()
    {
        $this->tcaSchemaFactory = GeneralUtility::makeInstance(TcaSchemaFactory::class);
    }

    /**
     * Main method to build expressions for given tables
     * Evaluates the ctrl/enablecolumns/disabled flag of the table and adds the according restriction if set
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if (!$this->tcaSchemaFactory->has($tableName)) {
                continue;
            }
            $schema = $this->tcaSchemaFactory->get($tableName);
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
                $constraints[] = $expressionBuilder->eq(
                    $tableAlias . '.' . $schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName(),
                    0
                );
            }
        }
        return $expressionBuilder->and(...$constraints);
    }
}
