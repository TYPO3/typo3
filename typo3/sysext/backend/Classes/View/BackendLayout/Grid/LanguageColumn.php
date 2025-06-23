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
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    public function __construct(
        protected PageLayoutContext $context,
        protected readonly Grid $grid,
        protected readonly array $translationInfo
    ) {
        parent::__construct($context);
    }

    public function getGrid(): Grid
    {
        return $this->grid;
    }

    public function getPageIcon(): string
    {
        $localizedPageRecord = $this->context->getLocalizedPageRecord() ?? $this->context->getPageRecord();
        return BackendUtility::wrapClickMenuOnIcon(
            $this->iconFactory->getIconForRecord('pages', $localizedPageRecord, IconSize::SMALL)->render(),
            'pages',
            $localizedPageRecord['uid']
        );
    }

    public function getAllowTranslate(): bool
    {
        return $this->context->getDrawingConfiguration()->translateModeForTranslationsAllowed() && !($this->getTranslationData()['hasStandAloneContent'] ?? false);
    }

    public function getTranslationData(): array
    {
        return $this->translationInfo;
    }

    public function getAllowTranslateCopy(): bool
    {
        return $this->context->getDrawingConfiguration()->copyModeForTranslationsAllowed() && !($this->getTranslationData()['hasTranslations'] ?? false);
    }

    public function getAllowEditPage(): bool
    {
        return $this->getBackendUser()->doesUserHaveAccess($this->context->getPageRecord(), Permission::PAGE_EDIT)
            && $this->getBackendUser()->check('tables_modify', 'pages')
            && $this->getBackendUser()->checkLanguageAccess($this->context->getSiteLanguage());
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
            'returnUrl' => $this->context->getCurrentRequest()->getAttribute('normalizedParams')->getRequestUri(),
        ];
        // Disallow manual adjustment of the language field for pages
        if (($languageField = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('pages')->getCapability(TcaSchemaCapability::Language)->getLanguageField()->getName()) !== '') {
            $urlParameters['overrideVals']['pages'][$languageField] = $this->context->getSiteLanguage()->getLanguageId();
        }
        return (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', $urlParameters);
    }

    public function getAllowViewPage(): bool
    {
        return PreviewUriBuilder::create($this->context->getLocalizedPageRecord() ?? $this->context->getPageRecord())->isPreviewable();
    }

    public function getPreviewUrlAttributes(): string
    {
        $pageId = $this->context->getPageId();
        $languageId = $this->context->getSiteLanguage()->getLanguageId();
        return (string)PreviewUriBuilder::create($this->context->getLocalizedPageRecord() ?? $this->context->getPageRecord())
            ->withRootLine(BackendUtility::BEgetRootLine($pageId))
            ->withLanguage($languageId)
            ->serializeDispatcherAttributes();
    }
}
