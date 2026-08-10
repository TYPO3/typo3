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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Restriction to filter records, that should not be shown until the start time has been reached
 */
class StartTimeRestriction implements QueryRestrictionInterface
{
    protected ?int $accessTimeStamp;

    public function __construct(?int $accessTimeStamp = null)
    {
        $this->accessTimeStamp = $accessTimeStamp ?: null;
    }

    /**
     * Main method to build expressions for given tables
     * Evaluates the ctrl/enablecolumns/starttime flag of the table and adds the according restriction if set
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $tcaSchemaFactory = $expressionBuilder->getContainer()->get(TcaSchemaFactory::class);
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if (!$tcaSchemaFactory->has($tableName)) {
                continue;
            }
            $schema = $tcaSchemaFactory->get($tableName);
            if ($schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
                $constraints[] = $expressionBuilder->lte(
                    $tableAlias . '.' . $schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName(),
                    $this->getAccessTimeStamp()
                );
            }
        }
        return $expressionBuilder->and(...$constraints);
    }

    /**
     * Resolved late, so that merely creating a restriction container does not access the Context,
     * and so that a date aspect set after instantiation - for instance by the preview simulator -
     * is still taken into account.
     */
    protected function getAccessTimeStamp(): int
    {
        return $this->accessTimeStamp ?? GeneralUtility::makeInstance(Context::class)
            ->getAspect('date')
            ->getTimestampWithMinutePrecision();
    }
}
