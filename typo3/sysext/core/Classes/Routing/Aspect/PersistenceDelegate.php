<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Aspect;

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

use TYPO3\CMS\Core\Database\Query\QueryBuilder;

/**
 * Delegate implementation in order to retrieve and generate values
 * using a database connection.
 *
 * @deprecated since TYPO3 v10.3, will be removed in TYPO3 v11.0
 */
class PersistenceDelegate implements DelegateInterface
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var \Closure
     */
    protected $resolveModifier;

    /**
     * @var \Closure
     */
    protected $generateModifier;

    /**
     * @param QueryBuilder $queryBuilder
     * @param \Closure $resolveModifier
     * @param \Closure $generateModifier
     */
    public function __construct(QueryBuilder $queryBuilder, \Closure $resolveModifier, \Closure $generateModifier)
    {
        $this->queryBuilder = $queryBuilder;
        $this->resolveModifier = $resolveModifier;
        $this->generateModifier = $generateModifier;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $values): bool
    {
        $this->applyValueModifier($this->resolveModifier, $values);
        return $this->queryBuilder
            ->count('*')
            ->execute()
            ->fetchColumn(0) > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(array $values): ?array
    {
        $this->applyValueModifier($this->resolveModifier, $values);
        $result = $this->queryBuilder
            ->execute()
            ->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $values): ?array
    {
        $this->applyValueModifier($this->generateModifier, $values);
        $result = $this->queryBuilder
            ->execute()
            ->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * @param \Closure $modifier
     * @param array $values
     */
    protected function applyValueModifier(\Closure $modifier, array $values)
    {
        $modifier($this->queryBuilder, $values);
    }
}
