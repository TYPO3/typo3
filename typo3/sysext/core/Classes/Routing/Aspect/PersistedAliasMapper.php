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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Classic usage when using a "URL segment" (e.g. slug) field within a database table.
 *
 * Example:
 *   routeEnhancers:
 *     EventsPlugin:
 *       type: Extbase
 *       extension: Events2
 *       plugin: Pi1
 *       routes:
 *         - { routePath: '/events/{event}', _controller: 'Event::detail', _arguments: {'event': 'event_name'}}
 *       defaultController: 'Events2::list'
 *       aspects:
 *         event:
 *           type: PersistedAliasMapper
 *           tableName: 'tx_events2_domain_model_event'
 *           routeFieldName: 'path_segment'
 *           valueFieldName: 'uid'
 *           routeValuePrefix: '/'
 */
class PersistedAliasMapper implements StaticMappableAspectInterface
{
    use SiteLanguageAwareTrait;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $routeFieldName;

    /**
     * @var string
     */
    protected $valueFieldName;

    /**
     * @var string
     */
    protected $routeValuePrefix;

    /**
     * @var PersistenceDelegate
     */
    protected $persistenceDelegate;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $tableName = $settings['tableName'] ?? null;
        $routeFieldName = $settings['routeFieldName'] ?? null;
        $valueFieldName = $settings['valueFieldName'] ?? null;
        $routeValuePrefix = $settings['routeValuePrefix'] ?? '';

        if (!is_string($tableName)) {
            throw new \InvalidArgumentException('tableName must be string', 1537277133);
        }
        if (!is_string($routeFieldName)) {
            throw new \InvalidArgumentException('routeFieldName name must be string', 1537277134);
        }
        if (!is_string($valueFieldName)) {
            throw new \InvalidArgumentException('valueFieldName name must be string', 1537277135);
        }
        if (!is_string($routeValuePrefix) || strlen($routeValuePrefix) > 1) {
            throw new \InvalidArgumentException('$routeValuePrefix name must be string with one character', 1537277136);
        }

        $this->settings = $settings;
        $this->tableName = $tableName;
        $this->routeFieldName = $routeFieldName;
        $this->valueFieldName = $valueFieldName;
        $this->routeValuePrefix = $routeValuePrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        $result = $this->getPersistenceDelegate()->generate([
            $this->valueFieldName => $value
        ]);
        $value = null;
        if (isset($result[$this->routeFieldName])) {
            $value = (string)$result[$this->routeFieldName];
        }
        $result = $this->purgeRouteValuePrefix($value);
        return $result ? (string)$result : null;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        $value = $this->routeValuePrefix . $this->purgeRouteValuePrefix($value);
        $result = $this->getPersistenceDelegate()->resolve([
            $this->routeFieldName => $value
        ]);
        $result = $result[$this->valueFieldName] ?? null;
        return $result ? (string)$result : null;
    }

    /**
     * @param string|null $value
     * @return string
     */
    protected function purgeRouteValuePrefix(?string $value): ?string
    {
        if (empty($this->routeValuePrefix) || $value === null) {
            return $value;
        }
        return ltrim($value, $this->routeValuePrefix);
    }

    /**
     * @return PersistenceDelegate
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
            return $queryBuilder->select($this->valueFieldName)->where(
                ...$this->createFieldConstraints($queryBuilder, $values)
            );
        };
        $generateModifier = function (QueryBuilder $queryBuilder, array $values) {
            return $queryBuilder->select($this->routeFieldName)->where(
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
     */
    protected function createFieldConstraints(QueryBuilder $queryBuilder, array $values): array
    {
        $constraints = [];
        foreach ($values as $fieldName => $fieldValue) {
            $constraints[] = $queryBuilder->expr()->eq(
                $fieldName,
                $queryBuilder->createNamedParameter($fieldValue, \PDO::PARAM_STR)
            );
        }
        return $constraints;
    }
}
