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

namespace TYPO3\CMS\Core\Routing\Aspect;

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\ContextAwareInterface;
use TYPO3\CMS\Core\Context\ContextAwareTrait;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\SiteAwareInterface;
use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;
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
 *           routeValuePrefix: '/'
 */
class PersistedAliasMapper implements PersistedMappableAspectInterface, StaticMappableAspectInterface, ContextAwareInterface, SiteLanguageAwareInterface, SiteAwareInterface
{
    use AspectTrait;
    use SiteLanguageAccessorTrait;
    use SiteAccessorTrait;
    use ContextAwareTrait;

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
     * @var string[]
     */
    protected $persistenceFieldNames;

    /**
     * @var string|null
     */
    protected $languageFieldName;

    /**
     * @var string|null
     */
    protected $languageParentFieldName;

    /**
     * @var bool
     */
    protected $slugUniqueInSite;

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
        $this->languageFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['languageField'] ?? null;
        $this->languageParentFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['transOrigPointerField'] ?? null;
        $this->persistenceFieldNames = $this->buildPersistenceFieldNames();
        $this->slugUniqueInSite = $this->isSlugUniqueInSite($this->tableName, $this->routeFieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        $result = $this->findByIdentifier($value);
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
        $result = $this->findByRouteFieldValue($value);
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
            $this->languageFieldName,
            $this->languageParentFieldName,
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

    protected function findByIdentifier(string $value): ?array
    {
        $queryBuilder = $this->createQueryBuilder();
        $result = $queryBuilder
            ->select(...$this->persistenceFieldNames)
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($value, \PDO::PARAM_INT)
            ))
            ->executeQuery()
            ->fetchAssociative();
        return $result !== false ? $result : null;
    }

    protected function findByRouteFieldValue(string $value): ?array
    {
        $languageAware = $this->languageFieldName !== null && $this->languageParentFieldName !== null;

        $queryBuilder = $this->createQueryBuilder();
        $constraints = [
            $queryBuilder->expr()->eq(
                $this->routeFieldName,
                $queryBuilder->createNamedParameter($value, \PDO::PARAM_STR)
            ),
        ];

        $languageIds = null;
        if ($languageAware) {
            $languageIds = $this->resolveAllRelevantLanguageIds();
            $constraints[] = $queryBuilder->expr()->in(
                $this->languageFieldName,
                $queryBuilder->createNamedParameter($languageIds, Connection::PARAM_INT_ARRAY)
            );
        }

        $results = $queryBuilder
            ->select(...$this->persistenceFieldNames)
            ->where(...$constraints)
            ->executeQuery()
            ->fetchAllAssociative();
        // limit results to be contained in rootPageId of current Site
        // (which is defining the route configuration currently being processed)
        if ($this->slugUniqueInSite) {
            $results = array_values($this->filterContainedInSite($results));
        }
        // return first result record in case table is not language aware
        if (!$languageAware) {
            return $results[0] ?? null;
        }
        // post-process language fallbacks
        return $this->resolveLanguageFallback($results, $this->languageFieldName, $languageIds);
    }

    protected function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($this->tableName)
            ->from($this->tableName);
        $queryBuilder->setRestrictions(
            GeneralUtility::makeInstance(FrontendRestrictionContainer::class, $this->context)
        );
        // Frontend Groups are not available at this time (initialized via TSFE->determineId)
        // So this must be excluded to allow access restricted records
        $queryBuilder->getRestrictions()->removeByType(FrontendGroupRestriction::class);
        return $queryBuilder;
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
