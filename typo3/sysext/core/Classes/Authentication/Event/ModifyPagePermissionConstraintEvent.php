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

namespace TYPO3\CMS\Core\Authentication\Event;

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

/**
 * Listeners can change the constraint selecting the pages a backend user holds
 * the given page permissions for, e.g. to apply access control lists. The
 * constraint is used by queries such as the page tree and the record list.
 *
 * Build further constraints with the expression builder of the event and refer
 * to the pages table by its alias:
 *
 *   $expr = $event->getExpressionBuilder();
 *   $event->addConstraint($expr->neq($event->getTableAlias() . '.doktype', 254));
 *
 * Constraints added with addConstraint() restrict the pages further. To grant
 * pages in addition, combine the current constraint with setConstraint():
 *
 *   $event->setConstraint($expr->or($event->getConstraint(), $expr->eq(...)));
 *
 * Decisions on a single page are taken by ModifyPagePermissionsEvent. A listener
 * of this event must decide in the same way, so that listings and checks of
 * single pages agree. While a listener is registered, the webmount check selects
 * the pages up to the webmount with the constraint instead of evaluating their
 * permissions in PHP.
 *
 * The event is not dispatched for admins, who are not restricted.
 */
final class ModifyPagePermissionConstraintEvent
{
    /**
     * @param list<int> $groupIds
     */
    public function __construct(
        private CompositeExpression $constraint,
        private readonly ExpressionBuilder $expressionBuilder,
        private readonly int $permissions,
        private readonly string $tableAlias,
        private readonly int $userId,
        private readonly array $groupIds,
        private readonly int $workspaceId,
    ) {}

    public function getConstraint(): CompositeExpression
    {
        return $this->constraint;
    }

    public function setConstraint(CompositeExpression $constraint): void
    {
        $this->constraint = $constraint;
    }

    /**
     * Restricts the selected pages further: each constraint must hold in addition.
     */
    public function addConstraint(CompositeExpression|string ...$constraints): void
    {
        $this->constraint = $this->expressionBuilder->and($this->constraint, ...$constraints);
    }

    /**
     * The expression builder of the connection of the pages table, to build
     * constraints with quoted identifiers and values.
     */
    public function getExpressionBuilder(): ExpressionBuilder
    {
        return $this->expressionBuilder;
    }

    /**
     * The bitmask of the permissions the pages are selected for, see TYPO3\CMS\Core\Type\Bitmask\Permission.
     */
    public function getPermissions(): int
    {
        return $this->permissions;
    }

    /**
     * The alias of the pages table the constraint refers to.
     */
    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return list<int>
     */
    public function getGroupIds(): array
    {
        return $this->groupIds;
    }

    /**
     * The workspace of the user, 0 for live and before the workspace of the user
     * is initialized during the login.
     */
    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
