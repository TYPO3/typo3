<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Resource\Search\QueryRestrictions;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Limits search result to files with given folder hashes
 */
class FolderHashesRestriction implements QueryRestrictionInterface
{
    /**
     * @var array
     */
    private $folderHashes;

    public function __construct(array $folderHashes)
    {
        $this->folderHashes = $folderHashes;
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName !== 'sys_file') {
                continue;
            }
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
            $quotedHashes = array_map([$connection, 'quote'], $this->folderHashes);
            $constraints[] = $expressionBuilder->in($tableAlias . '.folder_hash', $quotedHashes);
        }

        return $expressionBuilder->orX(...$constraints);
    }
}
