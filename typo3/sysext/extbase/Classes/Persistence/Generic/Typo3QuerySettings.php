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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Query settings. This class is NOT part of the TYPO3.Flow API.
 * It reflects the settings unique to TYPO3 CMS.
 *
 * @api
 */
class Typo3QuerySettings implements QuerySettingsInterface
{
    /**
     * Flag if the storage page should be respected for the query.
     *
     * @var bool
     */
    protected $respectStoragePage = true;

    /**
     * the pid(s) of the storage page(s) that should be respected for the query.
     *
     * @var array
     */
    protected $storagePageIds = [];

    /**
     * A flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
     * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
     * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
     *
     * @var bool
     */
    protected $ignoreEnableFields = false;

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement
     *
     * @var array
     */
    protected $enableFieldsToBeIgnored = [];

    /**
     * Flag whether deleted records should be included in the result set.
     *
     * @var bool
     */
    protected $includeDeleted = false;

    /**
     * Flag if the sys_language_uid should be respected (default is TRUE).
     *
     * @var bool
     */
    protected $respectSysLanguage = true;

    /**
     * Representing sys_language_overlay only valid for current context
     *
     * @var mixed
     */
    protected $languageOverlayMode = true;

    /**
     * Representing sys_language_mode only valid for current context
     *
     * @var string
     */
    protected $languageMode = null;

    /**
     * Represensting sys_language_uid only valid for current context
     *
     * @var int
     */
    protected $languageUid = 0;

    /**
     * Flag whether the query should use a prepared statement
     *
     * @var bool
     */
    protected $usePreparedStatement = false;

    /**
     * Flag whether the query should be cached using the caching framework
     *
     * @var bool
     */
    protected $useQueryCache = true;

    /**
     * As long as we use a feature flag ignoreAllEnableFieldsInBe to determine the default behavior, the
     * initializeObject is responsible for handling that.
     */
    public function initializeObject()
    {
        /** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManager */
        $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        /** @var $configurationManager \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface */
        $configurationManager = $objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::class);
        if (TYPO3_MODE === 'BE' && $configurationManager->isFeatureEnabled('ignoreAllEnableFieldsInBe')) {
            $this->setIgnoreEnableFields(true);
        }

        // TYPO3 CMS language defaults
        $this->setLanguageUid(0);
        $this->setLanguageMode(null);
        $this->setLanguageOverlayMode(false);

        // Set correct language uid for frontend handling
        if (isset($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
            $this->setLanguageUid((int)$GLOBALS['TSFE']->sys_language_content);
            $this->setLanguageOverlayMode($GLOBALS['TSFE']->sys_language_contentOL ?: false);
            $this->setLanguageMode($GLOBALS['TSFE']->sys_language_mode ?: null);
        } elseif ((int)GeneralUtility::_GP('L')) {
            // Set language from 'L' parameter
            $this->setLanguageUid((int)GeneralUtility::_GP('L'));
        }
    }

    /**
     * Sets the flag if the storage page should be respected for the query.
     *
     * @param bool $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
     * @return QuerySettingsInterface
     * @api
     */
    public function setRespectStoragePage($respectStoragePage)
    {
        $this->respectStoragePage = $respectStoragePage;
        return $this;
    }

    /**
     * Returns the state, if the storage page should be respected for the query.
     *
     * @return bool TRUE, if the storage page should be respected; otherwise FALSE.
     */
    public function getRespectStoragePage()
    {
        return $this->respectStoragePage;
    }

    /**
     * Sets the pid(s) of the storage page(s) that should be respected for the query.
     *
     * @param array $storagePageIds If given the storage page IDs will be determined and the statement will be extended accordingly.
     * @return QuerySettingsInterface
     * @api
     */
    public function setStoragePageIds(array $storagePageIds)
    {
        $this->storagePageIds = $storagePageIds;
        return $this;
    }

    /**
     * Returns the pid(s) of the storage page(s) that should be respected for the query.
     *
     * @return array list of integers that each represent a storage page id
     */
    public function getStoragePageIds()
    {
        return $this->storagePageIds;
    }

    /**
     * @param bool $respectSysLanguage TRUE if TYPO3 language settings are to be applied
     * @return QuerySettingsInterface
     * @api
     */
    public function setRespectSysLanguage($respectSysLanguage)
    {
        $this->respectSysLanguage = $respectSysLanguage;
        return $this;
    }

    /**
     * @return bool TRUE if TYPO3 language settings are to be applied
     */
    public function getRespectSysLanguage()
    {
        return $this->respectSysLanguage;
    }

    /**
     * @param mixed $languageOverlayMode TRUE, FALSE or "hideNonTranslated"
     * @return QuerySettingsInterface instance of $this to allow method chaining
     * @api
     */
    public function setLanguageOverlayMode($languageOverlayMode = false)
    {
        $this->languageOverlayMode = $languageOverlayMode;
        return $this;
    }

    /**
     * @return mixed TRUE, FALSE or "hideNonTranslated"
     */
    public function getLanguageOverlayMode()
    {
        return $this->languageOverlayMode;
    }

    /**
     * @param string $languageMode NULL, "content_fallback", "strict" or "ignore"
     * @return QuerySettingsInterface instance of $this to allow method chaining
     * @api
     */
    public function setLanguageMode($languageMode = '')
    {
        $this->languageMode = $languageMode;
        return $this;
    }

    /**
     * @return string NULL, "content_fallback", "strict" or "ignore"
     */
    public function getLanguageMode()
    {
        return $this->languageMode;
    }

    /**
     * @param int $languageUid
     * @return QuerySettingsInterface instance of $this to allow method chaining
     * @api
     */
    public function setLanguageUid($languageUid)
    {
        $this->languageUid = $languageUid;
        return $this;
    }

    /**
     * @return int
     */
    public function getLanguageUid()
    {
        return $this->languageUid;
    }

    /**
     * Sets a flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
     * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
     * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
     *
     * @param bool $ignoreEnableFields
     * @return QuerySettingsInterface
     * @see setEnableFieldsToBeIgnored()
     * @api
     */
    public function setIgnoreEnableFields($ignoreEnableFields)
    {
        $this->ignoreEnableFields = $ignoreEnableFields;
        return $this;
    }

    /**
     * The returned value indicates whether all or some enable fields should be ignored.
     *
     * If TRUE, all enable fields are ignored. If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored.
     * If FALSE, all enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
     *
     * @return bool
     * @see getEnableFieldsToBeIgnored()
     */
    public function getIgnoreEnableFields()
    {
        return $this->ignoreEnableFields;
    }

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement. Adding a column name here effectively switches off filtering
     * by this column. This setting is only taken into account if $this->ignoreEnableFields = TRUE.
     *
     * @param array $enableFieldsToBeIgnored
     * @return QuerySettingsInterface
     * @see setIgnoreEnableFields()
     * @api
     */
    public function setEnableFieldsToBeIgnored($enableFieldsToBeIgnored)
    {
        $this->enableFieldsToBeIgnored = $enableFieldsToBeIgnored;
        return $this;
    }

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement.
     *
     * @return array
     * @see getIgnoreEnableFields()
     */
    public function getEnableFieldsToBeIgnored()
    {
        return $this->enableFieldsToBeIgnored;
    }

    /**
     * Sets the flag if the query should return objects that are deleted.
     *
     * @param bool $includeDeleted
     * @return QuerySettingsInterface
     * @api
     */
    public function setIncludeDeleted($includeDeleted)
    {
        $this->includeDeleted = $includeDeleted;
        return $this;
    }

    /**
     * Returns if the query should return objects that are deleted.
     *
     * @return bool
     */
    public function getIncludeDeleted()
    {
        return $this->includeDeleted;
    }

    /**
     * @param bool $usePreparedStatement
     * @return QuerySettingsInterface
     */
    public function usePreparedStatement($usePreparedStatement)
    {
        $this->usePreparedStatement = $usePreparedStatement;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUsePreparedStatement()
    {
        return (bool)$this->usePreparedStatement;
    }

    /**
     * @param bool $useQueryCache
     * @return QuerySettingsInterface
     */
    public function useQueryCache($useQueryCache)
    {
        $this->useQueryCache = (bool)$useQueryCache;
        return $this;
    }

    /**
     * @return bool
     */
    public function getUseQueryCache()
    {
        return $this->useQueryCache;
    }
}
