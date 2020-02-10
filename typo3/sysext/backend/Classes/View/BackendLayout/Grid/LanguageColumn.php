<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\View\BackendLayout\Grid;

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
    protected $defaultLanguageElements = [];

    /**
     * @var array
     */
    protected $flatContentOfLanguage = [];

    /**
     * @var GridColumn|null
     */
    protected $grid;

    public function __construct(BackendLayout $backendLayout, SiteLanguage $language, array $defaultLanguageElements)
    {
        parent::__construct($backendLayout);
        $this->siteLanguage = $language;
        $this->defaultLanguageElements = $defaultLanguageElements;
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

        $contentFetcher = $backendLayout->getContentFetcher();
        $contentRecords = $contentFetcher->getContentRecordsPerColumn(null, $language->getLanguageId());
        if (!empty($contentRecords)) {
            $this->flatContentOfLanguage = array_merge(...$contentRecords);
        } else {
            $this->flatContentOfLanguage = [];
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
        if ($this->siteLanguage->getLanguageId() === 0) {
            return false;
        }

        $localizationTsConfig = BackendUtility::getPagesTSconfig($this->backendLayout->getDrawingConfiguration()->getPageId())['mod.']['web_layout.']['localization.'] ?? [];
        $allowTranslate = (bool)($localizationTsConfig['enableTranslate'] ?? true);
        if (!$allowTranslate) {
            return false;
        }

        $translationData = $this->backendLayout->getContentFetcher()->getTranslationData($this->flatContentOfLanguage, $this->siteLanguage->getLanguageId());
        if (!empty($translationData)) {
            if (isset($translationData['hasStandAloneContent'])) {
                return false;
            }
        }

        $defaultLanguageUids = array_flip(array_column($this->defaultLanguageElements, 'uid'));
        $translatedLanguageUids = array_column($this->flatContentOfLanguage, 'l10n_source');
        if (empty($translatedLanguageUids)) {
            return true;
        }

        foreach ($translatedLanguageUids as $translatedUid) {
            unset($defaultLanguageUids[$translatedUid]);
        }

        return !empty($defaultLanguageUids);
    }

    public function getTranslationData(): array
    {
        $contentFetcher = $this->backendLayout->getContentFetcher();
        return $contentFetcher->getTranslationData($this->defaultLanguageElements, $this->siteLanguage->getLanguageId());
    }

    public function getAllowTranslateCopy(): bool
    {
        $localizationTsConfig = BackendUtility::getPagesTSconfig($this->backendLayout->getDrawingConfiguration()->getPageId())['mod.']['web_layout.']['localization.'] ?? [];
        $allowCopy = (bool)($localizationTsConfig['enableCopy'] ?? true);
        if (!empty($translationData)) {
            if (isset($translationData['hasStandAloneContent'])) {
                return false;
            }
            if (isset($translationData['hasTranslations'])) {
                $allowCopy = $allowCopy && !$translationData['hasTranslations'];
            }
        }
        return $allowCopy;
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
