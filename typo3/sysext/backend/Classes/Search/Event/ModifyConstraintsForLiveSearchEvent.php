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

namespace TYPO3\CMS\Backend\Search\Event;

use TYPO3\CMS\Backend\Search\LiveSearch\SearchDemand\SearchDemand;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;

/**
 * PSR-14 event to modify the query builder instance for the live search
 */
final class ModifyConstraintsForLiveSearchEvent
{
    /**
     * @param array<int, string|CompositeExpression> $constraints
     */
    public function __construct(
        private array $constraints,
        private readonly string $table,
        private readonly SearchDemand $searchDemand
    ) {}

    /**
     * @return array<int, string|CompositeExpression>
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }

    /**
     * Note that we only add a single/multiple constraints
     * and do not allow to remove or override existing ones. This is
     * a safeguard to not overwrite security-related query constraints.
     */
    public function addConstraints(string|CompositeExpression ...$constraints): void
    {
        foreach ($constraints as $constraint) {
            $this->addConstraint($constraint);
        }
    }

    public function addConstraint(string|CompositeExpression $constraint): void
    {
        $this->constraints[] = $constraint;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    public function getSearchDemand(): SearchDemand
    {
        return $this->searchDemand;
    }
}
