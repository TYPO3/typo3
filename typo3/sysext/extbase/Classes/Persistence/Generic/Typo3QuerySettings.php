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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Query settings, reflects the settings unique to TYPO3 CMS.
 */
class Typo3QuerySettings implements QuerySettingsInterface
{
    protected ConfigurationManagerInterface $configurationManager;
    protected Context $context;

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
     * @var bool
     */
    protected $languageOverlayMode = true;

    /**
     * Representing sys_language_uid only valid for current context
     *
     * @var int
     */
    protected $languageUid = 0;

    public function __construct(
        Context $context,
        ConfigurationManagerInterface $configurationManager
    ) {
        // QuerySettings should always keep its own Context, as they can differ
        // Currently this is only used for reading, but might be improved in the future
        $this->context = clone $context;
        $this->configurationManager = $configurationManager;
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && $this->configurationManager->isFeatureEnabled('ignoreAllEnableFieldsInBe')) {
            $this->setIgnoreEnableFields(true);
        }
        /** @var LanguageAspect $languageAspect */
        $languageAspect = $this->context->getAspect('language');
        $this->setLanguageUid($languageAspect->getContentId());
        $this->setLanguageOverlayMode(false);

        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            $overlayMode = $languageAspect->getLegacyOverlayType() === 'hideNonTranslated' ? 'hideNonTranslated' : (bool)$languageAspect->getLegacyOverlayType();
            $this->setLanguageOverlayMode($overlayMode);
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
     * Language Mode is NOT used anymore, so just avoid using it. Will be deprecated in the future.
     *
     * @param string $languageMode
     * @return QuerySettingsInterface instance of $this to allow method chaining
     * @deprecated since TYPO3 11.0, will be removed in version 12.0
     */
    public function setLanguageMode($languageMode = '')
    {
        trigger_error(
            __METHOD__ . ' does not have any functionality any more, stop calling it.',
            E_USER_DEPRECATED
        );
        return $this;
    }

    /**
     * Language Mode is NOT used anymore, so just avoid using it. Will be deprecated in the future.
     *
     * @return string NULL, "content_fallback", "strict" or "ignore"
     * @deprecated since TYPO3 11.0, will be removed in version 12.0
     */
    public function getLanguageMode()
    {
        trigger_error(
            __METHOD__ . ' does not have any functionality any more, stop calling it.',
            E_USER_DEPRECATED
        );
        return null;
    }

    /**
     * @param int $languageUid
     * @return QuerySettingsInterface instance of $this to allow method chaining
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
}
