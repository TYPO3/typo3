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

namespace TYPO3\CMS\Backend\View\Drawing;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\ContentFetcher;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\LanguageColumn;
use TYPO3\CMS\Backend\View\BackendLayout\RecordRememberer;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Backend Layout Renderer
 *
 * Draws a page layout - essentially, behaves as a wrapper for a view
 * which renders the Resources/Private/PageLayout/PageLayout template
 * with necessary assigned template variables.
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class BackendLayoutRenderer
{
    protected PageLayoutContext $context;
    protected ContentFetcher $contentFetcher;
    protected TemplateView $view;

    public function __construct(PageLayoutContext $context)
    {
        $this->context = $context;
        $this->contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $context);
        $this->view = GeneralUtility::makeInstance(TemplateView::class);
        $this->view->getRenderingContext()->setRequest(GeneralUtility::makeInstance(Request::class));
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('backend');
        $this->view->getRenderingContext()->setControllerName('PageLayout');
        $this->view->assign('context', $context);
    }

    public function getGridForPageLayoutContext(PageLayoutContext $context): Grid
    {
        $grid = GeneralUtility::makeInstance(Grid::class, $context);
        $recordRememberer = GeneralUtility::makeInstance(RecordRememberer::class);
        if ($context->getDrawingConfiguration()->getLanguageMode()) {
            $languageId = $context->getSiteLanguage()->getLanguageId();
        } else {
            $languageId = $context->getDrawingConfiguration()->getSelectedLanguageId();
        }
        $rows = $context->getBackendLayout()->getStructure()['__config']['backend_layout.']['rows.'] ?? [];
        ksort($rows);
        foreach ($rows as $row) {
            $rowObject = GeneralUtility::makeInstance(GridRow::class, $context);
            foreach ($row['columns.'] as $column) {
                $columnObject = GeneralUtility::makeInstance(GridColumn::class, $context, $column);
                $rowObject->addColumn($columnObject);
                if (isset($column['colPos'])) {
                    $records = $this->contentFetcher->getContentRecordsPerColumn((int)$column['colPos'], $languageId);
                    $recordRememberer->rememberRecords($records);
                    foreach ($records as $contentRecord) {
                        $columnItem = GeneralUtility::makeInstance(GridColumnItem::class, $context, $columnObject, $contentRecord);
                        $columnObject->addItem($columnItem);
                    }
                }
            }
            $grid->addRow($rowObject);
        }
        return $grid;
    }

    /**
     * @return LanguageColumn[]
     */
    protected function getLanguageColumnsForPageLayoutContext(PageLayoutContext $context): iterable
    {
        $languageColumns = [];
        foreach ($context->getLanguagesToShow() as $siteLanguage) {
            $localizedLanguageId = $siteLanguage->getLanguageId();
            if ($localizedLanguageId === -1) {
                continue;
            }
            if ($localizedLanguageId > 0) {
                $localizedContext = $context->cloneForLanguage($siteLanguage);
                if (!$localizedContext->getLocalizedPageRecord()) {
                    continue;
                }
            } else {
                $localizedContext = $context;
            }
            $translationInfo = $this->contentFetcher->getTranslationData(
                $this->contentFetcher->getFlatContentRecords($localizedLanguageId),
                $localizedContext->getSiteLanguage()->getLanguageId()
            );
            $languageColumnObject = GeneralUtility::makeInstance(
                LanguageColumn::class,
                $localizedContext,
                $this->getGridForPageLayoutContext($localizedContext),
                $translationInfo
            );
            $languageColumns[] = $languageColumnObject;
        }
        return $languageColumns;
    }

    protected function getLanguageColumnsWithDefLangBindingForPageLayoutContext(PageLayoutContext $context): iterable
    {
        $languageColumns = [];

        // default language
        $translationInfo = $this->contentFetcher->getTranslationData(
            $this->contentFetcher->getFlatContentRecords(0),
            0
        );

        $defaultLanguageColumnObject = GeneralUtility::makeInstance(
            LanguageColumn::class,
            $context,
            $this->getGridForPageLayoutContext($context),
            $translationInfo
        );
        foreach ($context->getLanguagesToShow() as $siteLanguage) {
            $localizedLanguageId = $siteLanguage->getLanguageId();
            if ($localizedLanguageId  <= 0) {
                continue;
            }

            $localizedContext = $context->cloneForLanguage($siteLanguage);
            if (!$localizedContext->getLocalizedPageRecord()) {
                continue;
            }

            $translationInfo = $this->contentFetcher->getTranslationData(
                $this->contentFetcher->getFlatContentRecords($localizedLanguageId),
                $localizedContext->getSiteLanguage()->getLanguageId()
            );

            $translatedRows = $this->contentFetcher->getFlatContentRecords($localizedLanguageId);

            $grid = $defaultLanguageColumnObject->getGrid();
            if ($grid === null) {
                continue;
            }

            foreach ($grid->getRows() as $rows) {
                foreach ($rows->getColumns() as $column) {
                    if (($translationInfo['mode'] ?? '') === 'connected') {
                        foreach ($column->getItems() as $item) {
                            // check if translation exists
                            foreach ($translatedRows as $translation) {
                                if ($translation['l18n_parent'] === $item->getRecord()['uid']) {
                                    $translatedItem = GeneralUtility::makeInstance(GridColumnItem::class, $localizedContext, $column, $translation);
                                    $item->addTranslation($localizedLanguageId, $translatedItem);
                                }
                            }
                        }
                    }
                }
            }

            $languageColumnObject = GeneralUtility::makeInstance(
                LanguageColumn::class,
                $localizedContext,
                $this->getGridForPageLayoutContext($localizedContext),
                $translationInfo
            );
            $languageColumns[$localizedLanguageId] = $languageColumnObject;
        }
        $languageColumns = [$defaultLanguageColumnObject] + $languageColumns;

        return $languageColumns;
    }

    /**
     * @param bool $renderUnused If true, renders the bottom column with unused records
     * @return string
     */
    public function drawContent(bool $renderUnused = true): string
    {
        $this->view->assign('hideRestrictedColumns', (bool)(BackendUtility::getPagesTSconfig($this->context->getPageId())['mod.']['web_layout.']['hideRestrictedCols'] ?? false));
        $this->view->assign('newContentTitle', $this->getLanguageService()->getLL('newContentElement'));
        $this->view->assign('newContentTitleShort', $this->getLanguageService()->getLL('content'));
        $this->view->assign('allowEditContent', $this->getBackendUser()->check('tables_modify', 'tt_content'));

        if ($this->context->getDrawingConfiguration()->getLanguageMode()) {
            if ($this->context->getDrawingConfiguration()->getDefaultLanguageBinding()) {
                $this->view->assign('languageColumns', $this->getLanguageColumnsWithDefLangBindingForPageLayoutContext($this->context));
            } else {
                $this->view->assign('languageColumns', $this->getLanguageColumnsForPageLayoutContext($this->context));
            }
        } else {
            $context = $this->context;
            // Check if we have to use a localized context for grid creation
            if ($this->context->getDrawingConfiguration()->getSelectedLanguageId() > 0) {
                // In case a localization is selected, clone the context with this language
                $localizedContext = $this->context->cloneForLanguage(
                    $this->context->getSiteLanguage($this->context->getDrawingConfiguration()->getSelectedLanguageId())
                );
                if ($localizedContext->getLocalizedPageRecord()) {
                    // In case the localized context contains the corresponding
                    // localized page record use this context for grid creation.
                    $context = $localizedContext;
                }
            }
            $this->view->assign('grid', $this->getGridForPageLayoutContext($context));
        }

        $rendered = $this->view->render('PageLayout');
        if ($renderUnused) {
            $unusedRecords = $this->contentFetcher->getUnusedRecords();

            if (!empty($unusedRecords)) {
                $unusedElementsMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $this->getLanguageService()->getLL('staleUnusedElementsWarning'),
                    $this->getLanguageService()->getLL('staleUnusedElementsWarningTitle'),
                    FlashMessage::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($unusedElementsMessage);

                $unusedGrid = GeneralUtility::makeInstance(Grid::class, $this->context);
                $unusedRow = GeneralUtility::makeInstance(GridRow::class, $this->context);
                $unusedColumn = GeneralUtility::makeInstance(GridColumn::class, $this->context, ['name' => 'unused']);

                $unusedGrid->addRow($unusedRow);
                $unusedRow->addColumn($unusedColumn);

                foreach ($unusedRecords as $unusedRecord) {
                    $item = GeneralUtility::makeInstance(GridColumnItem::class, $this->context, $unusedColumn, $unusedRecord);
                    $unusedColumn->addItem($item);
                }

                $this->view->assign('grid', $unusedGrid);
                $rendered .= $this->view->render('UnusedRecords');
            }
        }
        return $rendered;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
