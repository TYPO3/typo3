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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Restriction to filter records, which are limited to the given user groups
 */
class FrontendGroupRestriction implements QueryRestrictionInterface
{
    /**
     * @var array
     */
    protected $frontendGroupIds;

    /**
     * @param array $frontendGroupIds Normalized array with user groups of currently logged in user (typically found in the Frontend Context)
     */
    public function __construct(array $frontendGroupIds = null)
    {
        if ($frontendGroupIds !== null) {
            $this->frontendGroupIds = $frontendGroupIds;
        } else {
            /** @var UserAspect $frontendUserAspect */
            $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
            $this->frontendGroupIds = $frontendUserAspect->getGroupIds();
        }
    }

    /**
     * Main method to build expressions for given tables
     * Evaluates the ctrl/enablecolumns/fe_group flag of the table and adds the according restriction if set
     *
     * @param array $queriedTables Array of tables, where array key is table alias and value is a table name
     * @param ExpressionBuilder $expressionBuilder Expression builder instance to add restrictions with
     * @return CompositeExpression The result of query builder expression(s)
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            $groupFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['enablecolumns']['fe_group'] ?? null;
            if (!empty($groupFieldName)) {
                $fieldName = $tableAlias . '.' . $groupFieldName;
                // Allow records where no group access has been configured (field values NULL, 0 or empty string)
                $constraints = [
                    $expressionBuilder->isNull($fieldName),
                    $expressionBuilder->eq($fieldName, $expressionBuilder->literal('')),
                    $expressionBuilder->eq($fieldName, $expressionBuilder->literal('0')),
                ];
                foreach ($this->frontendGroupIds as $frontendGroupId) {
                    $constraints[] = $expressionBuilder->inSet(
                        $fieldName,
                        $expressionBuilder->literal((string)$frontendGroupId)
                    );
                }
            }
        }
        return $expressionBuilder->orX(...$constraints);
    }
}
