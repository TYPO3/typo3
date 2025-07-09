<?php

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

namespace TYPO3\CMS\Frontend\Category\Collection;

use TYPO3\CMS\Core\Category\Collection\CategoryCollection as CoreCategoryCollection;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extend category collection for the frontend, to collect related records
 * while respecting language, enable fields, etc.
 *
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:frontend and not part of TYPO3's Core API.
 */
class CategoryCollection extends CoreCategoryCollection
{
    /**
     * Creates a new collection objects and reconstitutes the
     * given database record to the new object.
     *
     * Overrides the parent method to create a *frontend* category collection.
     *
     * @param array $collectionRecord Database record
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @return CategoryCollection
     */
    public static function create(array $collectionRecord, $fillItems = false)
    {
        $collection = GeneralUtility::makeInstance(
            __CLASS__,
            $collectionRecord['table_name'],
            $collectionRecord['field_name']
        );
        $collection->fromArray($collectionRecord);
        if ($fillItems) {
            $collection->loadContents();
        }
        return $collection;
    }

    /**
     * Loads the collection with the given id from persistence
     * For memory reasons, only data for the collection itself is loaded by default.
     * Entries can be loaded on first access or straightaway using the $fillItems flag.
     *
     * Overrides the parent method because of the call to "self::create()" which otherwise calls up
     * \TYPO3\CMS\Core\Category\Collection\CategoryCollection
     *
     * @param int $id Id of database record to be loaded
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @param string $tableName the table name
     * @param string $fieldName Name of the categories relation field
     * @return CategoryCollection
     */
    public static function load($id, $fillItems = false, $tableName = '', $fieldName = '')
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(static::$storageTableName);
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

        $collectionRecord = $queryBuilder
            ->select('*')
            ->from(static::$storageTableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($id, Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($collectionRecord === false) {
            return GeneralUtility::makeInstance(
                self::class,
                $tableName,
                $fieldName
            );
        }

        $collectionRecord['table_name'] = $tableName;
        $collectionRecord['field_name'] = $fieldName;

        return self::create($collectionRecord, $fillItems);
    }

    /**
     * Gets the collected records in this collection, by
     * looking up the MM relations of this record to the
     * table name defined in the local field 'table_name'.
     *
     * Overrides its parent method to implement usage of language,
     * enable fields, etc. Also performs overlays.
     *
     * @return array
     */
    protected function getCollectedRecords()
    {
        $relatedRecords = [];

        $queryBuilder = $this->getCollectedRecordsQueryBuilder();
        $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));
        $context = GeneralUtility::makeInstance(Context::class);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class, $context);
        $languageId = $context->getPropertyFromAspect('language', 'contentId', 0);

        // If language handling is defined for item table, add language condition
        if (isset($GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['languageField'])) {
            // Consider default or "all" language
            $languageField = sprintf(
                '%s.%s',
                $this->getItemTableName(),
                $GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['languageField']
            );

            $languageConstraint = $queryBuilder->expr()->in(
                $languageField,
                $queryBuilder->createNamedParameter([0, -1], Connection::PARAM_INT_ARRAY)
            );

            // If not in default language, also consider items in current language with no original
            if ($languageId > 0) {
                $transOrigPointerField = sprintf(
                    '%s.%s',
                    $this->getItemTableName(),
                    $GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['transOrigPointerField']
                );

                $languageConstraint = $queryBuilder->expr()->or(
                    $languageConstraint,
                    $queryBuilder->expr()->and(
                        $queryBuilder->expr()->eq(
                            $languageField,
                            $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            $transOrigPointerField,
                            $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                        )
                    )
                );
            }

            $queryBuilder->andWhere($languageConstraint);
        }

        // Get the related records from the database
        $result = $queryBuilder->executeQuery();

        while ($record = $result->fetchAssociative()) {
            // Overlay the record for workspaces
            $pageRepository->versionOL(
                $this->getItemTableName(),
                $record
            );

            // Overlay the record for translations
            if (is_array($record)) {
                $record = $pageRepository->getLanguageOverlay($this->getItemTableName(), $record);
            }

            // Record may have been unset during the overlay process
            if (is_array($record)) {
                $relatedRecords[] = $record;
            }
        }

        return $relatedRecords;
    }
}
