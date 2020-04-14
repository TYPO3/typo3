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

use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

/**
 * Restriction to make queries respect backend user rights for pages.
 *
 * Adds a WHERE-clause for the pages-table where user permissions according to input argument, $permissions, is validated.
 * $permissions is the "mask" used to select - see Permission Bitset.
 * E.g. if $perms is 1 then you'll get all pages that a user can actually see!
 * 2^0 = show (1)
 * 2^1 = edit (2)
 * 2^2 = delete (4)
 * 2^3 = new (8)
 * If the user is 'admin' no validation is used.
 *
 * If the user is not set at all (->user is not an array), then "AND 1=0" is returned (will cause no selection results at all)
 *
 * The 95% use of this function is "->getPagePermsClause(1)" which will
 * return WHERE clauses for *selecting* pages in backend listings - in other words this will check read permissions.
 */
class PagePermissionRestriction implements QueryRestrictionInterface
{
    /**
     * @var int
     */
    protected $permissions;

    /**
     * @var UserAspect
     */
    protected $userAspect;

    public function __construct(UserAspect $userAspect, int $permissions)
    {
        $this->permissions = $permissions;
        $this->userAspect = $userAspect;
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

            $constraint = $this->buildUserConstraints($expressionBuilder, $tableAlias);
            if ($constraint) {
                $constraints[] = $expressionBuilder->andX($constraint);
            }
        }

        return $expressionBuilder->andX(...$constraints);
    }

    /**
     * @param ExpressionBuilder $expressionBuilder
     * @param string $tableAlias
     * @return string|CompositeExpression|null
     * @throws \TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException
     */
    protected function buildUserConstraints(ExpressionBuilder $expressionBuilder, string $tableAlias)
    {
        if (!$this->userAspect->isLoggedIn()) {
            return $expressionBuilder->comparison(1, ExpressionBuilder::EQ, 0);
        }
        if ($this->userAspect->isAdmin()) {
            return null;
        }
        // User permissions
        $constraint = $expressionBuilder->orX(
            $expressionBuilder->comparison(
                $expressionBuilder->bitAnd($tableAlias . '.perms_everybody', $this->permissions),
                ExpressionBuilder::EQ,
                $this->permissions
            ),
            $expressionBuilder->andX(
                $expressionBuilder->eq($tableAlias . '.perms_userid', $this->userAspect->get('id')),
                $expressionBuilder->comparison(
                    $expressionBuilder->bitAnd($tableAlias . '.perms_user', $this->permissions),
                    ExpressionBuilder::EQ,
                    $this->permissions
                )
            )
        );

        // User groups (if any are set)
        $groupIds = array_map('intval', $this->userAspect->getGroupIds());
        if (!empty($groupIds)) {
            $constraint->add(
                $expressionBuilder->andX(
                    $expressionBuilder->in(
                        $tableAlias . '.perms_groupid',
                        $groupIds
                    ),
                    $expressionBuilder->comparison(
                        $expressionBuilder->bitAnd($tableAlias . '.perms_group', $this->permissions),
                        ExpressionBuilder::EQ,
                        $this->permissions
                    )
                )
            );
        }
        return $constraint;
    }
}
