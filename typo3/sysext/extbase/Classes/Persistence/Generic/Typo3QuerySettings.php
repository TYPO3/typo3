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
     */
    protected bool $respectStoragePage = true;

    /**
     * the pid(s) of the storage page(s) that should be respected for the query.
     */
    protected array $storagePageIds = [];

    /**
     * A flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
     * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
     * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
     */
    protected bool $ignoreEnableFields = false;

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement
     */
    protected array $enableFieldsToBeIgnored = [];

    /**
     * Flag whether deleted records should be included in the result set.
     */
    protected bool $includeDeleted = false;

    /**
     * Flag if the sys_language_uid should be respected (default is TRUE).
     */
    protected bool $respectSysLanguage = true;

    protected LanguageAspect $languageAspect;

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
            && $this->configurationManager->isFeatureEnabled('ignoreAllEnableFieldsInBe')
        ) {
            // @deprecated since TYPO3 v12, will be removed in TYPO3 v13. Remove together with other extbase feature toggle related code.
            trigger_error(
                'Extbase feature toggle ignoreAllEnableFieldsInBe=1 is deprecated. Use explicit call to setIgnoreEnableFields(true) in' .
                ' repositories or backend controllers instead.',
                E_USER_DEPRECATED
            );
            $this->setIgnoreEnableFields(true);
        }
        $this->languageAspect = $this->context->getAspect('language');
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
     * @see setLanguageAspect()
     * @deprecated will be removed in TYPO3 13.0. Use ->setLanguageAspect()
     */
    public function setLanguageOverlayMode($languageOverlayMode = false)
    {
        switch ($languageOverlayMode) {
            case 'hideNonTranslated':
                $overlayType = LanguageAspect::OVERLAYS_ON;
                break;
            case '1':
            case true:
                $overlayType = LanguageAspect::OVERLAYS_MIXED;
                break;
            default:
                $overlayType = LanguageAspect::OVERLAYS_OFF;
                break;
        }
        $this->languageAspect = new LanguageAspect($this->languageAspect->getId(), $this->languageAspect->getContentId(), $overlayType);
        return $this;
    }

    /**
     * @return mixed TRUE, FALSE or "hideNonTranslated"
     * @see getLanguageAspect()
     * @deprecated will be removed in TYPO3 13.0. Use ->getLanguageAspect()
     */
    public function getLanguageOverlayMode()
    {
        switch ($this->getLanguageAspect()->getOverlayType()) {
            case LanguageAspect::OVERLAYS_ON_WITH_FLOATING:
            case LanguageAspect::OVERLAYS_ON:
                return 'hideNonTranslated';
            case LanguageAspect::OVERLAYS_MIXED:
                return true;
            default:
                return false;
        }
    }

    /**
     * @param int $languageUid
     * @return QuerySettingsInterface instance of $this to allow method chaining
     * @see setLanguageAspect()
     * @deprecated will be removed in TYPO3 13.0. Use ->setLanguageAspect()
     */
    public function setLanguageUid($languageUid)
    {
        $this->languageAspect = new LanguageAspect($languageUid, $languageUid, $this->languageAspect->getOverlayType());
        return $this;
    }

    /**
     * @return int
     * @see getLanguageAspect()
     * @deprecated will be removed in TYPO3 13.0. Use ->getLanguageAspect()
     */
    public function getLanguageUid()
    {
        return $this->languageAspect->getContentId();
    }

    public function getLanguageAspect(): LanguageAspect
    {
        return $this->languageAspect;
    }

    /**
     * @return $this to allow method chaining
     */
    public function setLanguageAspect(LanguageAspect $languageAspect)
    {
        $this->languageAspect = $languageAspect;
        return $this;
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
