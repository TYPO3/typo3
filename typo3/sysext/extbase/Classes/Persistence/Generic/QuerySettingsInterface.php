<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

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

/**
 * A query settings interface. This interface is NOT part of the TYPO3.Flow API.
 */
interface QuerySettingsInterface
{
    /**
     * Sets the flag if the storage page should be respected for the query.
     *
     * @param bool $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     */
    public function setRespectStoragePage($respectStoragePage);

    /**
     * Returns the state, if the storage page should be respected for the query.
     *
     * @return bool TRUE, if the storage page should be respected; otherwise FALSE.
     */
    public function getRespectStoragePage();

    /**
     * Sets the pid(s) of the storage page(s) that should be respected for the query.
     *
     * @param array $storagePageIds If TRUE the storage page ID will be determined and the statement will be extended accordingly.
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     */
    public function setStoragePageIds(array $storagePageIds);

    /**
     * Returns the pid(s) of the storage page(s) that should be respected for the query.
     *
     * @return array list of integers that each represent a storage page id
     */
    public function getStoragePageIds();

    /**
     * Sets the flag if record language should be respected when querying.
     * Other settings defines whether overlay should happen or not.
     *
     * @param bool $respectSysLanguage TRUE if only record language should be respected when querying
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     */
    public function setRespectSysLanguage($respectSysLanguage);

    /**
     * Returns the state, if record language should be checked when querying
     *
     * @return bool if TRUE record language is checked.
     */
    public function getRespectSysLanguage();

    /**
     * @param mixed $languageOverlayMode TRUE, FALSE or "hideNonTranslated"
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     */
    public function setLanguageOverlayMode($languageOverlayMode);

    /**
     * @return mixed TRUE, FALSE or "hideNonTranslated"
     */
    public function getLanguageOverlayMode();

    /**
     * Language Mode is NOT used when consistentTranslationOverlayHandling is enabled
     *
     * @param string $languageMode NULL, "content_fallback", "strict" or "ignore"
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     */
    public function setLanguageMode($languageMode);

    /**
     * Language Mode is NOT used when consistentTranslationOverlayHandling is enabled
     *
     * @return string NULL, "content_fallback", "strict" or "ignore"
     */
    public function getLanguageMode();

    /**
     * @param int $languageUid
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     */
    public function setLanguageUid($languageUid);

    /**
     * @return int
     */
    public function getLanguageUid();

    /**
     * Sets a flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
     * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
     * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
     *
     * @param bool $ignoreEnableFields
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     * @see setEnableFieldsToBeIgnored()
     */
    public function setIgnoreEnableFields($ignoreEnableFields);

    /**
     * The returned value indicates whether all or some enable fields should be ignored.
     *
     * If TRUE, all enable fields are ignored. If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored.
     * If FALSE, all enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
     *
     * @return bool
     * @see getEnableFieldsToBeIgnored()
     */
    public function getIgnoreEnableFields();

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement. Adding a column name here effectively switches off filtering
     * by this column. This setting is only taken into account if $this->ignoreEnableFields = TRUE.
     *
     * @param array $enableFieldsToBeIgnored
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     * @see setIgnoreEnableFields()
     */
    public function setEnableFieldsToBeIgnored($enableFieldsToBeIgnored);

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement.
     *
     * @return array
     * @see getIgnoreEnableFields()
     */
    public function getEnableFieldsToBeIgnored();

    /**
     * Sets the flag if the query should return objects that are deleted.
     *
     * @param bool $includeDeleted
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface instance of $this to allow method chaining
     */
    public function setIncludeDeleted($includeDeleted);

    /**
     * Returns if the query should return objects that are deleted.
     *
     * @return bool
     */
    public function getIncludeDeleted();
}
