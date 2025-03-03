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

namespace TYPO3\CMS\Core\Domain\Event;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;

/**
 * Event which is fired when compiling the list of constraints such as "deleted" and "starttime",
 * "endtime" etc.
 *
 * This Event allows for additional enableColumns to be added or removed to the list of constraints.
 *
 * An example: The extension ingmar_accessctrl enables assigning more
 * than one usergroup to content and page records
 */
final class ModifyDefaultConstraintsForDatabaseQueryEvent
{
    public function __construct(
        private readonly string $table,
        private readonly string $tableAlias,
        private readonly ExpressionBuilder $expressionBuilder,
        /** @var array<string, CompositeExpression|string> */
        private array $constraints,
        /** @var array<string, bool> */
        private readonly array $enableFieldsToIgnore,
        private readonly Context $context
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getTableAlias(): string
    {
        return $this->tableAlias;
    }

    public function getExpressionBuilder(): ExpressionBuilder
    {
        return $this->expressionBuilder;
    }

    /**
     * @return array<string, CompositeExpression|string>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function setConstraints(array $constraints): void
    {
        $this->constraints = $constraints;
    }

    public function getEnableFieldsToIgnore(): array
    {
        return array_keys(array_filter($this->enableFieldsToIgnore));
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
