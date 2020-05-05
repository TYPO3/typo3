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

use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\ContextAwareInterface;
use TYPO3\CMS\Core\Context\ContextAwareTrait;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendGroupRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Routing\Legacy\PersistedPatternMapperLegacyTrait;
use TYPO3\CMS\Core\Site\SiteAwareInterface;
use TYPO3\CMS\Core\Site\SiteLanguageAwareInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Very useful for building an a path segment from a combined value of the database.
 * Please note: title is not prepared for slugs and used raw.
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
 *           type: PersistedPatternMapper
 *           tableName: 'tx_events2_domain_model_event'
 *           routeFieldPattern: '^(?P<title>.+)-(?P<uid>\d+)$'
 *           routeFieldResult: '{title}-{uid}'
 *
 * @internal might change its options in the future, be aware that there might be modifications.
 */
class PersistedPatternMapper implements PersistedMappableAspectInterface, StaticMappableAspectInterface, ContextAwareInterface, SiteLanguageAwareInterface, SiteAwareInterface
{
    use AspectTrait;
    use SiteLanguageAccessorTrait;
    use SiteAccessorTrait;
    use ContextAwareTrait;
    use PersistedPatternMapperLegacyTrait;

    protected const PATTERN_RESULT = '#\{(?P<fieldName>[^}]+)\}#';

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
    protected $routeFieldPattern;

    /**
     * @var string
     */
    protected $routeFieldResult;

    /**
     * @var string[]
     */
    protected $routeFieldResultNames;

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
        $routeFieldPattern = $settings['routeFieldPattern'] ?? null;
        $routeFieldResult = $settings['routeFieldResult'] ?? null;

        if (!is_string($tableName)) {
            throw new \InvalidArgumentException('tableName must be string', 1537277173);
        }
        if (!is_string($routeFieldPattern)) {
            throw new \InvalidArgumentException('routeFieldPattern must be string', 1537277174);
        }
        if (!is_string($routeFieldResult)) {
            throw new \InvalidArgumentException('routeFieldResult must be string', 1537277175);
        }
        if (!preg_match_all(static::PATTERN_RESULT, $routeFieldResult, $routeFieldResultNames)) {
            throw new \InvalidArgumentException(
                'routeFieldResult must contain substitutable field names',
                1537962752
            );
        }

        $this->settings = $settings;
        $this->tableName = $tableName;
        $this->routeFieldPattern = $routeFieldPattern;
        $this->routeFieldResult = $routeFieldResult;
        $this->routeFieldResultNames = $routeFieldResultNames['fieldName'] ?? [];
        $this->languageFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['languageField'] ?? null;
        $this->languageParentFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['transOrigPointerField'] ?? null;
        $this->slugUniqueInSite = $this->hasSlugUniqueInSite($this->tableName, ...$this->routeFieldResultNames);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $value): ?string
    {
        $result = $this->findByIdentifier($value);
        $result = $this->resolveOverlay($result);
        return $this->createRouteResult($result);
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(string $value): ?string
    {
        if (!preg_match('#' . $this->routeFieldPattern . '#', $value, $matches)) {
            return null;
        }
        $values = $this->filterNamesKeys($matches);
        $result = $this->findByRouteFieldValues($values);
        if ($result[$this->languageParentFieldName] ?? null > 0) {
            return (string)$result[$this->languageParentFieldName];
        }
        if (isset($result['uid'])) {
            return (string)$result['uid'];
        }
        return null;
    }

    /**
     * @param array|null $result
     * @return string|null
     * @throws \InvalidArgumentException
     */
    protected function createRouteResult(?array $result): ?string
    {
        if ($result === null) {
            return $result;
        }
        $substitutes = [];
        foreach ($this->routeFieldResultNames as $fieldName) {
            if (!isset($result[$fieldName])) {
                return null;
            }
            $routeFieldName = '{' . $fieldName . '}';
            $substitutes[$routeFieldName] = $result[$fieldName];
        }
        return str_replace(
            array_keys($substitutes),
            array_values($substitutes),
            $this->routeFieldResult
        );
    }

    /**
     * @param array $array
     * @return array
     */
    protected function filterNamesKeys(array $array): array
    {
        return array_filter(
            $array,
            function ($key) {
                return !is_numeric($key);
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    protected function findByIdentifier(string $value): ?array
    {
        $queryBuilder = $this->createQueryBuilder();
        $result = $queryBuilder
            ->select('*')
            ->where($queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($value, \PDO::PARAM_INT)
            ))
            ->execute()
            ->fetch();
        return $result !== false ? $result : null;
    }

    protected function findByRouteFieldValues(array $values): ?array
    {
        $languageAware = $this->languageFieldName !== null && $this->languageParentFieldName !== null;

        $queryBuilder = $this->createQueryBuilder();
        $results = $queryBuilder
            ->select('*')
            ->where(...$this->createRouteFieldConstraints($queryBuilder, $values))
            ->execute()
            ->fetchAll();
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
        $languageIds = $this->resolveAllRelevantLanguageIds();
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
     * @param QueryBuilder $queryBuilder
     * @param array $values
     * @return array
     */
    protected function createRouteFieldConstraints(QueryBuilder $queryBuilder, array $values): array
    {
        $languageAware = $this->languageFieldName !== null && $this->languageParentFieldName !== null;
        $languageExpansion = $languageAware && isset($values['uid']);

        $constraints = [];
        foreach ($values as $fieldName => $fieldValue) {
            if ($languageExpansion && $fieldName === 'uid') {
                continue;
            }
            $constraints[] = $queryBuilder->expr()->eq(
                $fieldName,
                $queryBuilder->createNamedParameter(
                    $fieldValue,
                    \PDO::PARAM_STR
                )
            );
        }
        // either match uid or language parent field value (for any language)
        if ($languageExpansion) {
            $idParameter = $queryBuilder->createNamedParameter(
                $values['uid'],
                \PDO::PARAM_INT
            );
            $constraints[] = $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('uid', $idParameter),
                $queryBuilder->expr()->eq($this->languageParentFieldName, $idParameter)
            );
        // otherwise - basically uid is not in pattern - restrict to languages and apply fallbacks
        } elseif ($languageAware) {
            $languageIds = $this->resolveAllRelevantLanguageIds();
            $constraints[] = $queryBuilder->expr()->in(
                $this->languageFieldName,
                $queryBuilder->createNamedParameter($languageIds, Connection::PARAM_INT_ARRAY)
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
