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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Site\SiteLanguageAwareTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

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
 *           routeValuePrefix: '/'
 */
class PersistedAliasMapper implements PersistedMappableAspectInterface, StaticMappableAspectInterface
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
    protected $routeValuePrefix;

    /**
     * @var PersistenceDelegate
     */
    protected $persistenceDelegate;

    /**
     * @var string[]
     */
    protected $persistenceFieldNames;

    /**
     * @var string|null
     */
    protected $languageParentFieldName;

    /**
     * @param array $settings
     * @throws \InvalidArgumentException
     */
    public function __construct(array $settings)
    {
        $tableName = $settings['tableName'] ?? null;
        $routeFieldName = $settings['routeFieldName'] ?? null;
        $routeValuePrefix = $settings['routeValuePrefix'] ?? '';

        if (!is_string($tableName)) {
            throw new \InvalidArgumentException(
                'tableName must be string',
                1537277133
            );
        }
        if (!is_string($routeFieldName)) {
            throw new \InvalidArgumentException(
                'routeFieldName name must be string',
                1537277134
            );
        }
        if (!is_string($routeValuePrefix) || strlen($routeValuePrefix) > 1) {
            throw new \InvalidArgumentException(
                '$routeValuePrefix must be string with one character',
                1537277136
            );
        }

        $this->settings = $settings;
        $this->tableName = $tableName;
        $this->routeFieldName = $routeFieldName;
        $this->routeValuePrefix = $routeValuePrefix;
        $this->persistenceFieldNames = $this->buildPersistenceFieldNames();
        $this->languageParentFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['transOrigPointerField'] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        $result = $this->getPersistenceDelegate()->generate([
            'uid' => $value
        ]);
        $result = $this->resolveOverlay($result);
        if (!isset($result[$this->routeFieldName])) {
            return null;
        }
        return $this->purgeRouteValuePrefix(
            (string)$result[$this->routeFieldName]
        );
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
        if ($result[$this->languageParentFieldName] ?? null > 0) {
            return (string)$result[$this->languageParentFieldName];
        }
        if (isset($result['uid'])) {
            return (string)$result['uid'];
        }
        return null;
    }

    /**
     * @return string[]
     */
    protected function buildPersistenceFieldNames(): array
    {
        return array_filter([
            'uid',
            'pid',
            $this->routeFieldName,
            $GLOBALS['TCA'][$this->tableName]['ctrl']['languageField'] ?? null,
            $GLOBALS['TCA'][$this->tableName]['ctrl']['transOrigPointerField'] ?? null,
        ]);
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

    /**
     * @param array|null $record
     * @return array|null
     */
    protected function resolveOverlay(?array $record): ?array
    {
        $languageId = $this->siteLanguage->getLanguageId();
        if ($record === null || $languageId === 0) {
            return $record;
        }

        $pageRepository = $this->createPageRepository();
        if ($this->tableName === 'pages') {
            return $pageRepository->getPageOverlay($record, $languageId);
        }
        return $pageRepository
            ->getRecordOverlay($this->tableName, $record, $languageId) ?: null;
    }

    /**
     * @return PageRepository
     */
    protected function createPageRepository(): PageRepository
    {
        $context = clone GeneralUtility::makeInstance(Context::class);
        $context->setAspect(
            'language',
            LanguageAspectFactory::createFromSiteLanguage($this->siteLanguage)
        );
        return GeneralUtility::makeInstance(
            PageRepository::class,
            $context
        );
    }
}
