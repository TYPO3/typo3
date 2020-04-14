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

namespace TYPO3\CMS\Core\Resource\Search\QueryRestrictions;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Assumes identifiers carrying hierarchical information and
 * filters files with identifiers starting with given identifier.
 */
class FolderIdentifierRestriction implements QueryRestrictionInterface
{
    /**
     * @var string
     */
    private $folderIdentifier;

    public function __construct(string $folderIdentifier)
    {
        $this->folderIdentifier = $folderIdentifier;
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName !== 'sys_file') {
                continue;
            }
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
            $folderIdentifier = $connection->createQueryBuilder()->escapeLikeWildcards($this->folderIdentifier);
            $constraints[] = $expressionBuilder->like(
                $tableAlias . '.identifier',
                $connection->quote($folderIdentifier . '%')
            );
        }

        return $expressionBuilder->orX(...$constraints);
    }
}
