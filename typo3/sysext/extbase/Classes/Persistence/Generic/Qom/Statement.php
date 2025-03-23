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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * A statement acting as a constraint.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class Statement implements ConstraintInterface
{
    /**
     * @param array $boundVariables An array of variables to bind to the statement, only to be used with prepared statements
     */
    public function __construct(
        protected string|\Doctrine\DBAL\Statement|QueryBuilder $statement,
        protected array $boundVariables = []
    ) {}

    public function getStatement(): string|\Doctrine\DBAL\Statement|QueryBuilder
    {
        return $this->statement;
    }

    public function getBoundVariables(): array
    {
        return $this->boundVariables;
    }

    public function collectBoundVariableNames(array &$boundVariables) {}
}
