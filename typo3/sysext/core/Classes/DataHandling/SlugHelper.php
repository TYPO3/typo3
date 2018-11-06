<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\DataHandling;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\Model\RecordState;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Generates, sanitizes and validates slugs for a TCA field
 */
class SlugHelper
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var int
     */
    protected $workspaceId;

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @var bool
     */
    protected $workspaceEnabled;

    /**
     * Defines whether the slug field should start with "/".
     * For pages (due to rootline functionality), this is a must have, otherwise the root level page
     * would have an empty value.
     *
     * @var bool
     */
    protected $prependSlashInSlug;

    /**
     * Slug constructor.
     *
     * @param string $tableName TCA table
     * @param string $fieldName TCA field
     * @param array $configuration TCA configuration of the field
     * @param int $workspaceId the workspace ID to be working on.
     */
    public function __construct(string $tableName, string $fieldName, array $configuration, int $workspaceId = 0)
    {
        $this->tableName = $tableName;
        $this->fieldName = $fieldName;
        $this->configuration = $configuration;
        $this->workspaceId = $workspaceId;

        if ($this->tableName === 'pages' && $this->fieldName === 'slug') {
            $this->prependSlashInSlug = true;
        } else {
            $this->prependSlashInSlug = $this->configuration['prependSlash'] ?? false;
        }

        $this->workspaceEnabled = BackendUtility::isTableWorkspaceEnabled($tableName);
    }

    /**
     * Cleans a slug value so it is used directly in the path segment of a URL.
     *
     * @param string $slug
     * @return string
     */
    public function sanitize(string $slug): string
    {
        // Convert to lowercase + remove tags
        $slug = mb_strtolower($slug, 'utf-8');
        $slug = strip_tags($slug);

        // Convert some special tokens (space, "_" and "-") to the space character
        $fallbackCharacter = (string)($this->configuration['fallbackCharacter'] ?? '-');
        $slug = preg_replace('/[ \t\x{00A0}\-+_]+/u', $fallbackCharacter, $slug);

        // Convert extended letters to ascii equivalents
        // The specCharsToASCII() converts "€" to "EUR"
        $slug = GeneralUtility::makeInstance(CharsetConverter::class)->specCharsToASCII('utf-8', $slug);

        // Get rid of all invalid characters, but allow slashes
        $slug = preg_replace('/[^\p{L}0-9\/' . preg_quote($fallbackCharacter) . ']/u', '', $slug);

        // Convert multiple fallback characters to a single one
        if ($fallbackCharacter !== '') {
            $slug = preg_replace('/' . preg_quote($fallbackCharacter) . '{2,}/', $fallbackCharacter, $slug);
        }

        // Ensure slug is lower cased after all replacement was done
        $slug = mb_strtolower($slug, 'utf-8');
        // keep slashes: re-convert them after rawurlencode did everything
        $slug = rawurlencode($slug);
        // @todo: add a test and see if we need this
        $slug = str_replace('%2F', '/', $slug);
        // Extract slug, thus it does not have wrapping fallback and slash characters
        $extractedSlug = $this->extract($slug);
        // Remove trailing and beginning slashes, except if the trailing slash was added, then we'll re-add it
        $appendTrailingSlash = $extractedSlug !== '' && substr($slug, -1) === '/';
        $slug = $extractedSlug . ($appendTrailingSlash ? '/' : '');
        if ($this->prependSlashInSlug && ($slug{0} ?? '') !== '/') {
            $slug = '/' . $slug;
        }
        return $slug;
    }

    /**
     * Extracts payload of slug and removes wrapping delimiters,
     * e.g. `/hello/world/` will become `hello/world`.
     *
     * @param string $slug
     * @return string
     */
    public function extract(string $slug): string
    {
        // Convert some special tokens (space, "_" and "-") to the space character
        $fallbackCharacter = $this->configuration['fallbackCharacter'] ?? '-';
        return trim($slug, $fallbackCharacter . '/');
    }

    /**
     * Used when no slug exists for a record
     *
     * @param array $recordData
     * @param int $pid
     * @return string
     */
    public function generate(array $recordData, int $pid): string
    {
        if ($pid === 0 || (!empty($recordData['is_siteroot']) && $this->tableName === 'pages')) {
            return '/';
        }
        $prefix = '';
        if ($this->configuration['generatorOptions']['prefixParentPageSlug'] ?? false) {
            $languageFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['languageField'] ?? null;
            $languageId = (int)($recordData[$languageFieldName] ?? 0);
            $parentPageRecord = $this->resolveParentPageRecord($pid, $languageId);
            if (is_array($parentPageRecord)) {
                // If the parent page has a slug, use that instead of "re-generating" the slug from the parents' page title
                if (!empty($parentPageRecord['slug'])) {
                    $rootLineItemSlug = $parentPageRecord['slug'];
                } else {
                    $rootLineItemSlug = $this->generate($parentPageRecord, (int)$parentPageRecord['pid']);
                }
                $rootLineItemSlug = trim($rootLineItemSlug, '/');
                if (!empty($rootLineItemSlug)) {
                    $prefix = $rootLineItemSlug;
                }
            }
        }

        $fieldSeparator = $this->configuration['generatorOptions']['fieldSeparator'] ?? '/';
        $slugParts = [];

        $replaceConfiguration = $this->configuration['generatorOptions']['replacements'] ?? [];
        foreach ($this->configuration['generatorOptions']['fields'] ?? [] as $fieldName) {
            if (!empty($recordData[$fieldName])) {
                $pieceOfSlug = $recordData[$fieldName];
                $pieceOfSlug = str_replace(array_keys($replaceConfiguration), array_values($replaceConfiguration), $pieceOfSlug);
                $slugParts[] = $pieceOfSlug;
            }
        }
        $slug = implode($fieldSeparator, $slugParts);
        $slug = $this->sanitize($slug);
        // No valid data found
        if ($slug === '' || $slug === '/') {
            $slug = 'default-' . GeneralUtility::shortMD5(json_encode($recordData));
        }
        if ($this->prependSlashInSlug && ($slug{0} ?? '') !== '/') {
            $slug = '/' . $slug;
        }
        if (!empty($prefix)) {
            $slug = $prefix . $slug;
        }

        return $this->sanitize($slug);
    }

    /**
     * Checks if there are other records with the same slug that are located on the same PID.
     *
     * @param string $slug
     * @param RecordState $state
     * @return bool
     */
    public function isUniqueInPid(string $slug, RecordState $state): bool
    {
        $pageId = (int)$state->resolveNodeIdentifier();
        $recordId = $state->getSubject()->getIdentifier();
        $languageId = $state->getContext()->getLanguageId();

        if ($pageId < 0) {
            $pageId = $this->resolveLivePageId($recordId);
        }

        $queryBuilder = $this->createPreparedQueryBuilder();
        $this->applySlugConstraint($queryBuilder, $slug);
        $this->applyPageIdConstraint($queryBuilder, $pageId);
        $this->applyRecordConstraint($queryBuilder, $recordId);
        $this->applyLanguageConstraint($queryBuilder, $languageId);
        $this->applyWorkspaceConstraint($queryBuilder);
        $statement = $queryBuilder->execute();

        $records = $this->resolveVersionOverlays(
            $statement->fetchAll()
        );
        return count($records) === 0;
    }

    /**
     * Check if there are other records with the same slug that are located on the same site.
     *
     * @param string $slug
     * @param RecordState $state
     * @return bool
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function isUniqueInSite(string $slug, RecordState $state): bool
    {
        $pageId = (int)$state->resolveNodeIdentifier();
        $recordId = $state->getSubject()->getIdentifier();
        $languageId = $state->getContext()->getLanguageId();

        if ($pageId < 0) {
            $pageId = $this->resolveLivePageId($recordId);
        }

        $queryBuilder = $this->createPreparedQueryBuilder();
        $this->applySlugConstraint($queryBuilder, $slug);
        $this->applyRecordConstraint($queryBuilder, $recordId);
        $this->applyLanguageConstraint($queryBuilder, $languageId);
        $this->applyWorkspaceConstraint($queryBuilder);
        $statement = $queryBuilder->execute();

        $records = $this->resolveVersionOverlays(
            $statement->fetchAll()
        );
        if (count($records) === 0) {
            return true;
        }

        // The installation contains at least ONE other record with the same slug
        // Now find out if it is the same root page ID
        $siteMatcher = GeneralUtility::makeInstance(SiteMatcher::class);
        $siteOfCurrentRecord = $siteMatcher->matchByPageId($pageId);
        foreach ($records as $record) {
            try {
                $recordState = RecordStateFactory::forName($this->tableName)->fromArray($record);
                $siteOfExistingRecord = $siteMatcher->matchByPageId(
                    (int)$recordState->resolveNodeAggregateIdentifier()
                );
            } catch (SiteNotFoundException $exception) {
                // In case not site is found, the record is not
                // organized in any site or pseudo-site
                continue;
            }
            if ($siteOfExistingRecord->getRootPageId() === $siteOfCurrentRecord->getRootPageId()) {
                return false;
            }
        }

        // Otherwise, everything is still fine
        return true;
    }

    /**
     * Generate a slug with a suffix "/mytitle-1" if that is in use already.
     *
     * @param string $slug proposed slug
     * @param RecordState $state
     * @return string
     * @throws \TYPO3\CMS\Core\Exception\SiteNotFoundException
     */
    public function buildSlugForUniqueInSite(string $slug, RecordState $state): string
    {
        $slug = $this->sanitize($slug);
        $rawValue = $this->extract($slug);
        $newValue = $slug;
        $counter = 0;
        while (!$this->isUniqueInSite(
                $newValue,
                $state
            ) && $counter++ < 100
        ) {
            $newValue = $this->sanitize($rawValue . '-' . $counter);
        }
        if ($counter === 100) {
            $newValue = $this->sanitize($rawValue . '-' . GeneralUtility::shortMD5($rawValue));
        }
        return $newValue;
    }

    /**
     * Generate a slug with a suffix "/mytitle-1" if the suggested slug is in use already.
     *
     * @param string $slug proposed slug
     * @param RecordState $state
     * @return string
     */
    public function buildSlugForUniqueInPid(string $slug, RecordState $state): string
    {
        $slug = $this->sanitize($slug);
        $rawValue = $this->extract($slug);
        $newValue = $slug;
        $counter = 0;
        while (!$this->isUniqueInPid(
                $newValue,
                $state
            ) && $counter++ < 100
        ) {
            $newValue = $this->sanitize($rawValue . '-' . $counter);
        }
        if ($counter === 100) {
            $newValue = $this->sanitize($rawValue . '-' . GeneralUtility::shortMD5($rawValue));
        }
        return $newValue;
    }

    /**
     * @return QueryBuilder
     */
    protected function createPreparedQueryBuilder(): QueryBuilder
    {
        $fieldNames = ['uid', 'pid', $this->fieldName];
        if ($this->workspaceEnabled) {
            $fieldNames[] = 't3ver_state';
        }
        $languageFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['languageField'] ?? null;
        if (is_string($languageFieldName)) {
            $fieldNames[] = $languageFieldName;
        }
        $languageParentFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['transOrigPointerField'] ?? null;
        if (is_string($languageParentFieldName)) {
            $fieldNames[] = $languageParentFieldName;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $queryBuilder
            ->select(...$fieldNames)
            ->from($this->tableName);
        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     */
    protected function applyWorkspaceConstraint(QueryBuilder $queryBuilder)
    {
        if (!$this->workspaceEnabled) {
            return;
        }

        $workspaceIds = [0];
        if ($this->workspaceId > 0) {
            $workspaceIds[] = $this->workspaceId;
        }
        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                't3ver_wsid',
                $queryBuilder->createNamedParameter($workspaceIds, Connection::PARAM_INT_ARRAY)
            ),
            $queryBuilder->expr()->neq(
                'pid',
                $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $languageId
     */
    protected function applyLanguageConstraint(QueryBuilder $queryBuilder, int $languageId)
    {
        $languageFieldName = $GLOBALS['TCA'][$this->tableName]['ctrl']['languageField'] ?? null;
        if (!is_string($languageFieldName)) {
            return;
        }

        // Only check records of the given language
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                $languageFieldName,
                $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $slug
     */
    protected function applySlugConstraint(QueryBuilder $queryBuilder, string $slug)
    {
        $queryBuilder->where(
            $queryBuilder->expr()->eq(
                $this->fieldName,
                $queryBuilder->createNamedParameter($slug)
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param int $pageId
     */
    protected function applyPageIdConstraint(QueryBuilder $queryBuilder, int $pageId)
    {
        if ($pageId < 0) {
            throw new \RuntimeException(
                sprintf(
                    'Page id must be positive "%d"',
                    $pageId
                ),
                1534962573
            );
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq(
                'pid',
                $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
            )
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string|int $recordId
     */
    protected function applyRecordConstraint(QueryBuilder $queryBuilder, $recordId)
    {
        // Exclude the current record if it is an existing record
        if (!MathUtility::canBeInterpretedAsInteger($recordId)) {
            return;
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($recordId, \PDO::PARAM_INT))
        );
        if ($this->workspaceId > 0 && $this->workspaceEnabled) {
            $liveId = BackendUtility::getLiveVersionIdOfRecord($this->tableName, $recordId) ?? $recordId;
            $queryBuilder->andWhere(
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($liveId, \PDO::PARAM_INT))
            );
        }
    }

    /**
     * @param int $recordId
     * @return int
     * @throws \RuntimeException
     */
    protected function resolveLivePageId($recordId): int
    {
        if (!MathUtility::canBeInterpretedAsInteger($recordId)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot resolve live page id for non-numeric identifier "%s"',
                    $recordId
                ),
                1534951024
            );
        }

        $liveVersion = BackendUtility::getLiveVersionOfRecord(
            $this->tableName,
            $recordId,
            'pid'
        );

        if (empty($liveVersion)) {
            throw new \RuntimeException(
                sprintf(
                    'Cannot resolve live page id for record "%s:%d"',
                    $this->tableName,
                    $recordId
                ),
                1534951025
            );
        }

        return (int)$liveVersion['pid'];
    }

    /**
     * @param array $records
     * @return array
     */
    protected function resolveVersionOverlays(array $records): array
    {
        if (!$this->workspaceEnabled) {
            return $records;
        }

        return array_filter(
            array_map(
                function (array $record) {
                    BackendUtility::workspaceOL(
                        $this->tableName,
                        $record,
                        $this->workspaceId,
                        true
                    );
                    if (VersionState::cast($record['t3ver_state'] ?? null)
                        ->equals(VersionState::DELETE_PLACEHOLDER)) {
                        return null;
                    }
                    return $record;
                },
                $records
            )
        );
    }

    /**
     * Fetch a parent page, but exclude spacers, recyclers and sys-folders and all doktypes > 200
     * @param int $pid
     * @param int $languageId
     * @return array|null
     */
    protected function resolveParentPageRecord(int $pid, int $languageId): ?array
    {
        $parentPageRecord = null;
        $rootLine = BackendUtility::BEgetRootLine($pid, '', true, ['nav_title']);
        do {
            $parentPageRecord = array_shift($rootLine);
            // do not use spacers (199), recyclers and folders and everything else
        } while (!empty($rootLine) && (int)$parentPageRecord['doktype'] >= 199);
        if ($languageId > 0) {
            $localizedParentPageRecord = BackendUtility::getRecordLocalization('pages', $parentPageRecord['uid'], $languageId);
            if (!empty($localizedParentPageRecord)) {
                $parentPageRecord = reset($localizedParentPageRecord);
            }
        }
        return $parentPageRecord;
    }
}
