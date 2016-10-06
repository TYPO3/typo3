<?php
namespace TYPO3\CMS\Frontend\Category\Collection;

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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Extend category collection for the frontend, to collect related records
 * while respecting language, enable fields, etc.
 */
class CategoryCollection extends \TYPO3\CMS\Core\Category\Collection\CategoryCollection
{
    /**
     * Creates a new collection objects and reconstitutes the
     * given database record to the new object.
     *
     * Overrides the parent method to create a *frontend* category collection.
     *
     * @param array $collectionRecord Database record
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @return \TYPO3\CMS\Frontend\Category\Collection\CategoryCollection
     */
    public static function create(array $collectionRecord, $fillItems = false)
    {
        /** @var $collection \TYPO3\CMS\Frontend\Category\Collection\CategoryCollection */
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
     * @return \TYPO3\CMS\Core\Collection\CollectionInterface
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
                    $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->execute()
            ->fetch();

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
        $tsfe = self::getTypoScriptFrontendController();

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
            if ($tsfe->sys_language_content > 0) {
                $transOrigPointerField = sprintf(
                    '%s.%s',
                    $this->getItemTableName(),
                    $GLOBALS['TCA'][$this->getItemTableName()]['ctrl']['transOrigPointerField']
                );

                $languageConstraint = $queryBuilder->expr()->orX(
                    $languageConstraint,
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            $languageField,
                            $queryBuilder->createNamedParameter($tsfe->sys_language_content, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            $transOrigPointerField,
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        )
                    )
                );
            }

            $queryBuilder->andWhere($languageConstraint);
        }

        // Get the related records from the database
        $result = $queryBuilder->execute();

        while ($record = $result->fetch()) {
            // Overlay the record for workspaces
            $tsfe->sys_page->versionOL(
                $this->getItemTableName(),
                $record
            );

            // Overlay the record for translations
            if (is_array($record) && $tsfe->sys_language_contentOL) {
                if ($this->getItemTableName() === 'pages') {
                    $record = $tsfe->sys_page->getPageOverlay($record);
                } else {
                    $record = $tsfe->sys_page->getRecordOverlay(
                        $this->getItemTableName(),
                        $record,
                        $tsfe->sys_language_content,
                        $tsfe->sys_language_contentOL
                    );
                }
            }

            // Record may have been unset during the overlay process
            if (is_array($record)) {
                $relatedRecords[] = $record;
            }
        }

        return $relatedRecords;
    }

    /**
     * Gets the TSFE object.
     *
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
