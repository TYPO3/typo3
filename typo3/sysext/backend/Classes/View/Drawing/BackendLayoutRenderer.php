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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\ContentFetcher;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\LanguageColumn;
use TYPO3\CMS\Backend\View\BackendLayout\RecordRememberer;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    public function __construct(
        protected readonly BackendViewFactory $backendViewFactory,
    ) {}

    public function getGridForPageLayoutContext(PageLayoutContext $context): Grid
    {
        $contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $context);
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
                    $records = $contentFetcher->getContentRecordsPerColumn((int)$column['colPos'], $languageId);
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
     * @param bool $renderUnused If true, renders the bottom column with unused records
     */
    public function drawContent(ServerRequestInterface $request, PageLayoutContext $pageLayoutContext, bool $renderUnused = true): string
    {
        $backendUser = $this->getBackendUser();
        $contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $pageLayoutContext);

        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'context' => $pageLayoutContext,
            'hideRestrictedColumns' => (bool)(BackendUtility::getPagesTSconfig($pageLayoutContext->getPageId())['mod.']['web_layout.']['hideRestrictedCols'] ?? false),
            'newContentTitle' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:newContentElement'),
            'newContentTitleShort' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:content'),
            'allowEditContent' => $backendUser->check('tables_modify', 'tt_content'),
            'maxTitleLength' => $backendUser->uc['titleLen'] ?? 20,
        ]);

        if ($pageLayoutContext->getDrawingConfiguration()->getLanguageMode()) {
            if ($pageLayoutContext->getDrawingConfiguration()->getDefaultLanguageBinding()) {
                $view->assign('languageColumns', $this->getLanguageColumnsWithDefLangBindingForPageLayoutContext($pageLayoutContext));
            } else {
                $view->assign('languageColumns', $this->getLanguageColumnsForPageLayoutContext($pageLayoutContext));
            }
        } else {
            $context = $pageLayoutContext;
            // Check if we have to use a localized context for grid creation
            if ($pageLayoutContext->getDrawingConfiguration()->getSelectedLanguageId() > 0) {
                // In case a localization is selected, clone the context with this language
                $localizedContext = $pageLayoutContext->cloneForLanguage(
                    $pageLayoutContext->getSiteLanguage($pageLayoutContext->getDrawingConfiguration()->getSelectedLanguageId())
                );
                if ($localizedContext->getLocalizedPageRecord()) {
                    // In case the localized context contains the corresponding
                    // localized page record use this context for grid creation.
                    $context = $localizedContext;
                }
            }
            $grid  = $this->getGridForPageLayoutContext($context);
            $view->assign('grid', $grid);
            $view->assign('gridColumns', array_fill(1, $grid->getContext()->getBackendLayout()->getColCount(), null));
        }

        $rendered = $view->render('PageLayout/PageLayout');
        if ($renderUnused) {
            $unusedRecords = $contentFetcher->getUnusedRecords();

            if (!empty($unusedRecords)) {
                $unusedElementsMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:staleUnusedElementsWarning'),
                    $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:staleUnusedElementsWarningTitle'),
                    ContextualFeedbackSeverity::WARNING
                );
                $service = GeneralUtility::makeInstance(FlashMessageService::class);
                $queue = $service->getMessageQueueByIdentifier();
                $queue->addMessage($unusedElementsMessage);

                $unusedGrid = GeneralUtility::makeInstance(Grid::class, $pageLayoutContext);
                $unusedRow = GeneralUtility::makeInstance(GridRow::class, $pageLayoutContext);
                $unusedColumn = GeneralUtility::makeInstance(GridColumn::class, $pageLayoutContext, ['name' => 'unused']);

                $unusedGrid->addRow($unusedRow);
                $unusedRow->addColumn($unusedColumn);

                foreach ($unusedRecords as $unusedRecord) {
                    $item = GeneralUtility::makeInstance(GridColumnItem::class, $pageLayoutContext, $unusedColumn, $unusedRecord);
                    $unusedColumn->addItem($item);
                }

                $view->assign('grid', $unusedGrid);
                $view->assign('gridColumns', null);
                $rendered .= $view->render('PageLayout/UnusedRecords');
            }
        }
        return $rendered;
    }

    /**
     * @return LanguageColumn[]
     */
    protected function getLanguageColumnsForPageLayoutContext(PageLayoutContext $context): iterable
    {
        $contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $context);
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
            $translationInfo = $contentFetcher->getTranslationData(
                $contentFetcher->getFlatContentRecords($localizedLanguageId),
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
        $contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class, $context);
        $languageColumns = [];

        // default language
        $translationInfo = $contentFetcher->getTranslationData(
            $contentFetcher->getFlatContentRecords(0),
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

            $translationInfo = $contentFetcher->getTranslationData(
                $contentFetcher->getFlatContentRecords($localizedLanguageId),
                $localizedContext->getSiteLanguage()->getLanguageId()
            );

            $translatedRows = $contentFetcher->getFlatContentRecords($localizedLanguageId);

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
        return [$defaultLanguageColumnObject] + $languageColumns;
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
