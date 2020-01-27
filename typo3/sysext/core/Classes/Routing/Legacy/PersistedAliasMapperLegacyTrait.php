<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Legacy;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Routing\Aspect\PersistenceDelegate;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait PersistedAliasMapperLegacyTrait
{
    /**
     * @var PersistenceDelegate
     */
    protected $persistenceDelegate;

    /**
     * @return PersistenceDelegate
     * @deprecated since TYPO3 v10.3, will be removed in TYPO3 v11.0
     */
    protected function getPersistenceDelegate(): PersistenceDelegate
    {
        if ($this->persistenceDelegate !== null) {
            return $this->persistenceDelegate;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName)
            ->from($this->tableName);
        // @todo Restrictions (Hidden? Workspace?)

        $resolveModifier = function (QueryBuilder $queryBuilder, array $values) {
            return $queryBuilder->select(...$this->persistenceFieldNames)->where(
                ...$this->createFieldConstraints($queryBuilder, $values)
            );
        };
        $generateModifier = function (QueryBuilder $queryBuilder, array $values) {
            return $queryBuilder->select(...$this->persistenceFieldNames)->where(
                ...$this->createFieldConstraints($queryBuilder, $values)
            );
        };

        return $this->persistenceDelegate = new PersistenceDelegate(
            $queryBuilder,
            $resolveModifier,
            $generateModifier
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $values
     * @return array
     * @deprecated since TYPO3 v10.3, will be removed in TYPO3 v11.0
     */
    protected function createFieldConstraints(QueryBuilder $queryBuilder, array $values): array
    {
        $constraints = [];
        foreach ($values as $fieldName => $fieldValue) {
            $constraints[] = $queryBuilder->expr()->eq(
                $fieldName,
                $queryBuilder->createNamedParameter(
                    $fieldValue,
                    \PDO::PARAM_STR
                )
            );
        }
        return $constraints;
    }
}
