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

namespace TYPO3\CMS\Extbase\Persistence\Generic;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/**
 * Query settings, reflects the settings unique to TYPO3 CMS.
 */
#[Autoconfigure(public: true, shared: false)]
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
        $this->languageAspect = $this->context->getAspect('language');
    }

    /**
     * Sets the flag if the storage page should be respected for the query.
     *
     * @param bool $respectStoragePage If TRUE the storage page ID will be determined and the statement will be extended accordingly.
     */
    public function setRespectStoragePage(bool $respectStoragePage): QuerySettingsInterface
    {
        $this->respectStoragePage = $respectStoragePage;
        return $this;
    }

    /**
     * Returns the state, if the storage page should be respected for the query.
     *
     * @return bool TRUE, if the storage page should be respected; otherwise FALSE.
     */
    public function getRespectStoragePage(): bool
    {
        return $this->respectStoragePage;
    }

    /**
     * Sets the pid(s) of the storage page(s) that should be respected for the query.
     *
     * @param array $storagePageIds If given the storage page IDs will be determined and the statement will be extended accordingly.
     */
    public function setStoragePageIds(array $storagePageIds): self
    {
        $this->storagePageIds = $storagePageIds;
        return $this;
    }

    /**
     * Returns the pid(s) of the storage page(s) that should be respected for the query.
     *
     * @return array list of integers that each represent a storage page id
     */
    public function getStoragePageIds(): array
    {
        return $this->storagePageIds;
    }

    /**
     * @param bool $respectSysLanguage TRUE if TYPO3 language settings are to be applied
     */
    public function setRespectSysLanguage(bool $respectSysLanguage): self
    {
        $this->respectSysLanguage = $respectSysLanguage;
        return $this;
    }

    /**
     * @return bool TRUE if TYPO3 language settings are to be applied
     */
    public function getRespectSysLanguage(): bool
    {
        return $this->respectSysLanguage;
    }

    public function getLanguageAspect(): LanguageAspect
    {
        return $this->languageAspect;
    }

    public function setLanguageAspect(LanguageAspect $languageAspect): self
    {
        $this->languageAspect = $languageAspect;
        return $this;
    }

    /**
     * Sets a flag indicating whether all or some enable fields should be ignored. If TRUE, all enable fields are ignored.
     * If--in addition to this--enableFieldsToBeIgnored is set, only fields specified there are ignored. If FALSE, all
     * enable fields are taken into account, regardless of the enableFieldsToBeIgnored setting.
     *
     * @see setEnableFieldsToBeIgnored()
     */
    public function setIgnoreEnableFields(bool $ignoreEnableFields): self
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
     * @see getEnableFieldsToBeIgnored()
     */
    public function getIgnoreEnableFields(): bool
    {
        return $this->ignoreEnableFields;
    }

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement. Adding a column name here effectively switches off filtering
     * by this column. This setting is only taken into account if $this->ignoreEnableFields = TRUE.
     *
     * @see setIgnoreEnableFields()
     */
    public function setEnableFieldsToBeIgnored(array $enableFieldsToBeIgnored): self
    {
        $this->enableFieldsToBeIgnored = $enableFieldsToBeIgnored;
        return $this;
    }

    /**
     * An array of column names in the enable columns array (array keys in $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']),
     * to be ignored while building the query statement.
     *
     * @see getIgnoreEnableFields()
     */
    public function getEnableFieldsToBeIgnored(): array
    {
        return $this->enableFieldsToBeIgnored;
    }

    /**
     * Sets the flag if the query should return objects that are deleted.
     */
    public function setIncludeDeleted(bool $includeDeleted): self
    {
        $this->includeDeleted = $includeDeleted;
        return $this;
    }

    /**
     * Returns if the query should return objects that are deleted.
     */
    public function getIncludeDeleted(): bool
    {
        return $this->includeDeleted;
    }
}
