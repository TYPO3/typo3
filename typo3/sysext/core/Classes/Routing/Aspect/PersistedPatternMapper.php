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
class PersistedPatternMapper implements PersistedMappableAspectInterface, StaticMappableAspectInterface
{
    use SiteLanguageAwareTrait;

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
     * @var PersistenceDelegate
     */
    protected $persistenceDelegate;

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
        $result = $this->getPersistenceDelegate()->resolve($values);
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
            return $queryBuilder->select('*')->where(
                ...$this->createFieldConstraints($queryBuilder, $values, true)
            );
        };
        $generateModifier = function (QueryBuilder $queryBuilder, array $values) {
            return $queryBuilder->select('*')->where(
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
     * @param bool $resolveExpansion
     * @return array
     */
    protected function createFieldConstraints(
        QueryBuilder $queryBuilder,
        array $values,
        bool $resolveExpansion = false
    ): array {
        $languageExpansion = $this->languageParentFieldName
            && $resolveExpansion
            && isset($values['uid']);

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
        // If requested, either match uid or language parent field value
        if ($languageExpansion) {
            $idParameter = $queryBuilder->createNamedParameter(
                $values['uid'],
                \PDO::PARAM_INT
            );
            $constraints[] = $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('uid', $idParameter),
                $queryBuilder->expr()->eq($this->languageParentFieldName, $idParameter)
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
