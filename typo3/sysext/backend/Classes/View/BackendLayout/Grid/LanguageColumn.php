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

use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Imaging\Icon;
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
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class LanguageColumn extends AbstractGridObject
{
    /**
     * @var array
     */
    protected $localizationConfiguration = [];

    /**
     * @var Grid|null
     */
    protected $grid;

    /**
     * @var array
     */
    protected $translationInfo = [
        'hasStandaloneContent' => false,
        'hasTranslations' => false,
        'untranslatedRecordUids' => [],
    ];

    public function __construct(PageLayoutContext $context, Grid $grid, array $translationInfo)
    {
        parent::__construct($context);
        $this->localizationConfiguration = BackendUtility::getPagesTSconfig($context->getPageId())['mod.']['web_layout.']['localization.'] ?? [];
        $this->grid = $grid;
        $this->translationInfo = $translationInfo;
    }

    public function getGrid(): ?Grid
    {
        return $this->grid;
    }

    public function getPageIcon(): string
    {
        $localizedPageRecord = $this->context->getLocalizedPageRecord() ?? $this->context->getPageRecord();
        return BackendUtility::wrapClickMenuOnIcon(
            $this->iconFactory->getIconForRecord('pages', $localizedPageRecord, Icon::SIZE_SMALL)->render(),
            'pages',
            $localizedPageRecord['uid']
        );
    }

    public function getAllowTranslate(): bool
    {
        return ($this->localizationConfiguration['enableTranslate'] ?? true) && !($this->getTranslationData()['hasStandAloneContent'] ?? false);
    }

    public function getTranslationData(): array
    {
        return $this->translationInfo;
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
        return $this->getBackendUser()->check('tables_modify', 'pages')
            && $this->getBackendUser()->checkLanguageAccess($this->context->getSiteLanguage()->getLanguageId());
    }

    public function getPageEditTitle(): string
    {
        return $this->getLanguageService()->getLL('edit');
    }

    public function getPageEditUrl(): string
    {
        $pageRecordUid = $this->context->getLocalizedPageRecord()['uid'] ?? $this->context->getPageRecord()['uid'];
        $urlParameters = [
            'edit' => [
                'pages' => [
                    $pageRecordUid => 'edit',
                ],
            ],
            // Disallow manual adjustment of the language field for pages
            'overrideVals' => [
                'pages' => [
                    'sys_language_uid' => $this->context->getSiteLanguage()->getLanguageId(),
                ],
            ],
            'returnUrl' => $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getRequestUri(),
        ];
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
    }

    public function getAllowViewPage(): bool
    {
        return !VersionState::cast($this->context->getPageRecord()['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER);
    }

    public function getViewPageLinkTitle(): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage');
    }

    public function getPreviewUrlAttributes(): string
    {
        $pageId = $this->context->getPageId();
        $languageId = $this->context->getSiteLanguage()->getLanguageId();
        return (string)PreviewUriBuilder::create($pageId)
            ->withRootLine(BackendUtility::BEgetRootLine($pageId))
            ->withAdditionalQueryParameters('&L=' . $languageId)
            ->serializeDispatcherAttributes();
    }
}
