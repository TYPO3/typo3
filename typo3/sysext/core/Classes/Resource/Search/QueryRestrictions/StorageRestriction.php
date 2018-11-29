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

use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;

/**
 * Limits search result to a give storage
 */
class StorageRestriction implements QueryRestrictionInterface
{
    /**
     * @var ResourceStorage
     */
    private $storage;

    public function __construct(ResourceStorage $storage)
    {
        $this->storage = $storage;
    }

    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        foreach ($queriedTables as $tableAlias => $tableName) {
            if ($tableName !== 'sys_file') {
                continue;
            }
            $constraints[] = $expressionBuilder->eq(
                $tableAlias . '.storage',
                (int)$this->storage->getUid()
            );
        }

        return $expressionBuilder->orX(...$constraints);
    }
}
