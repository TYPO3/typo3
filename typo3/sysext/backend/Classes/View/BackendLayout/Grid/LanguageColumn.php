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

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Language Column
 *
 * Object representation of a site language selected in the "page" module
 * to show translations of content elements.
 *
 * Contains getter methods to return various values associated with a single
 * language, e.g. localized page title, associated SiteLanguage instance,
 * edit URLs and link titles and so on.
 *
 * Stores a duplicated Grid object associated with the SiteLanguage.
 *
 * Accessed from Fluid templates - generated from within BackendLayout when
 * "page" module is in "languages" mode.
 */
class LanguageColumn extends AbstractGridObject
{
    /**
     * @var SiteLanguage
     */
    protected $siteLanguage;

    /**
     * @var array
     */
    protected $localizedPageRecord = [];

    /**
     * @var array
     */
    protected $localizationConfiguration = [];

    /**
     * @var GridColumn|null
     */
    protected $grid;

    public function __construct(BackendLayout $backendLayout, SiteLanguage $language)
    {
        parent::__construct($backendLayout);
        $this->siteLanguage = $language;
        $this->localizationConfiguration = BackendUtility::getPagesTSconfig($backendLayout->getDrawingConfiguration()->getPageId())['mod.']['web_layout.']['localization.'] ?? [];
        if ($this->siteLanguage->getLanguageId() > 0) {
            $pageLocalizationRecord = BackendUtility::getRecordLocalization(
                'pages',
                $backendLayout->getDrawingConfiguration()->getPageId(),
                $language->getLanguageId()
            );
            if (is_array($pageLocalizationRecord)) {
                $pageLocalizationRecord = reset($pageLocalizationRecord);
            }
            BackendUtility::workspaceOL('pages', $pageLocalizationRecord);
            $this->localizedPageRecord = $pageLocalizationRecord;
        } else {
            $this->localizedPageRecord = $backendLayout->getDrawingConfiguration()->getPageRecord();
        }
    }

    public function getLocalizedPageRecord(): ?array
    {
        return $this->localizedPageRecord ?: null;
    }

    public function getSiteLanguage(): SiteLanguage
    {
        return $this->siteLanguage;
    }

    public function getGrid(): Grid
    {
        if (empty($this->grid)) {
            $this->grid = $this->backendLayout->getGrid();
        }
        return $this->grid;
    }

    public function getLanguageModeLabelClass(): string
    {
        $contentRecordsPerColumn = $this->backendLayout->getContentFetcher()->getFlatContentRecords();
        $translationData = $this->backendLayout->getContentFetcher()->getTranslationData($contentRecordsPerColumn, $this->siteLanguage->getLanguageId());
        return $translationData['mode'] === 'mixed' ? 'danger' : 'info';
    }

    public function getLanguageMode(): string
    {
        switch ($this->backendLayout->getLanguageModeIdentifier()) {
            case 'mixed':
                $languageMode = $this->getLanguageService()->getLL('languageModeMixed');
                break;
            case 'connected':
                $languageMode = $this->getLanguageService()->getLL('languageModeConnected');
                break;
            case 'free':
                $languageMode = $this->getLanguageService()->getLL('languageModeFree');
                break;
            default:
                $languageMode = '';
        }
        return $languageMode;
    }

    public function getPageIcon(): string
    {
        return BackendUtility::wrapClickMenuOnIcon(
            $this->iconFactory->getIconForRecord('pages', $this->localizedPageRecord, Icon::SIZE_SMALL)->render(),
            'pages',
            $this->localizedPageRecord['uid']
        );
    }

    public function getAllowTranslate(): bool
    {
        return ($this->localizationConfiguration['enableTranslate'] ?? true) && !($this->getTranslationData()['hasStandAloneContent'] ?? false);
    }

    public function getTranslationData(): array
    {
        $contentFetcher = $this->backendLayout->getContentFetcher();
        return $contentFetcher->getTranslationData($contentFetcher->getFlatContentRecords(), $this->siteLanguage->getLanguageId());
    }

    public function getAllowTranslateCopy(): bool
    {
        return ($this->localizationConfiguration['enableCopy'] ?? true) && !($this->getTranslationData()['hasTranslations'] ?? false);
    }

    public function getTranslatePageTitle(): string
    {
        return $this->getLanguageService()->getLL('newPageContent_translate');
    }

    public function getAllowEditPage(): bool
    {
        return $this->getBackendUser()->check('tables_modify', 'pages');
    }

    public function getPageEditTitle(): string
    {
        return $this->getLanguageService()->getLL('edit');
    }

    public function getPageEditUrl(): string
    {
        $urlParameters = [
            'edit' => [
                'pages' => [
                    $this->localizedPageRecord['uid'] => 'edit'
                ]
            ],
            // Disallow manual adjustment of the language field for pages
            'overrideVals' => [
                'pages' => [
                    'sys_language_uid' => $this->siteLanguage->getLanguageId()
                ]
            ],
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
    }

    public function getAllowViewPage(): bool
    {
        return !VersionState::cast($this->backendLayout->getDrawingConfiguration()->getPageRecord()['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER);
    }

    public function getViewPageLinkTitle(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage');
    }

    public function getViewPageOnClick(): string
    {
        $pageId = $this->backendLayout->getDrawingConfiguration()->getPageId();
        return BackendUtility::viewOnClick(
            $pageId,
            '',
            BackendUtility::BEgetRootLine($pageId),
            '',
            '',
            '&L=' . $this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer()
        );
    }
}
