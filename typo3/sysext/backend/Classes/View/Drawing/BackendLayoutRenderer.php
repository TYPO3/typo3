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
use TYPO3\CMS\Backend\View\BackendLayout\ContentFetcher;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\LanguageColumn;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Backend\View\PageLayoutContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Schema\Exception\UndefinedSchemaException;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewInterface;

/**
 * Backend Layout Renderer
 *
 * Draws a page layout - essentially, behaves as a wrapper for a view
 * which renders the Resources/Private/PageLayout/PageLayout template
 * with necessary assigned template variables.
 *
 * @internal
 */
class BackendLayoutRenderer
{
    public function __construct(
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly RecordFactory $recordFactory,
    ) {}

    public function getGridForPageLayoutContext(PageLayoutContext $context): Grid
    {
        $recordIdentityMap = $context->getRecordIdentityMap();
        $contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class);
        $grid = GeneralUtility::makeInstance(Grid::class, $context);
        if ($context->getDrawingConfiguration()->isLanguageComparisonMode()) {
            $languageId = $context->getSiteLanguage()->getLanguageId();
        } else {
            $languageId = $context->getDrawingConfiguration()->getSelectedLanguageId();
        }
        $rows = $context->getBackendLayout()->getStructure()['__config']['backend_layout.']['rows.'] ?? [];
        ksort($rows);
        foreach ($rows as $row) {
            $rowObject = GeneralUtility::makeInstance(GridRow::class, $context);
            foreach ($row['columns.'] ?? [] as $column) {
                $columnObject = GeneralUtility::makeInstance(GridColumn::class, $context, $column);
                $rowObject->addColumn($columnObject);
                if (isset($column['colPos'])) {
                    $records = $contentFetcher->getContentRecordsPerColumn($context, (int)$column['colPos'], $languageId);
                    foreach ($records as $contentRecord) {
                        // @todo: ideally we hand in the record object into the GridColumnItem in the future - For now
                        //        we just call record factory to create the record and store it in the identity map.
                        try {
                            $this->recordFactory->createResolvedRecordFromDatabaseRow('tt_content', $contentRecord, null, $recordIdentityMap);
                        } catch (UndefinedSchemaException) {
                        }
                        $columnItem = GeneralUtility::makeInstance(GridColumnItem::class, $context, $columnObject, $contentRecord);
                        $columnObject->addItem($columnItem);
                    }
                }
            }
            $grid->addRow($rowObject);
        }
        return $grid;
    }

    protected function createView(ServerRequestInterface $request, PageLayoutContext $pageLayoutContext): ViewInterface
    {
        $backendUser = $this->getBackendUser();

        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'context' => $pageLayoutContext,
            'hideRestrictedColumns' => $pageLayoutContext->getDrawingConfiguration()->shouldHideRestrictedColumns(),
            'allowEditContent' => $backendUser->check('tables_modify', 'tt_content'),
            'maxTitleLength' => $backendUser->uc['titleLen'] ?? 20,
        ]);
        return $view;
    }

    /**
     * @param bool $renderUnused If true, renders the bottom column with unused records
     */
    public function drawContent(ServerRequestInterface $request, PageLayoutContext $pageLayoutContext, bool $renderUnused = true): string
    {
        $view = $this->createView($request, $pageLayoutContext);

        if ($pageLayoutContext->getDrawingConfiguration()->isLanguageComparisonMode()) {
            $view->assign('languageColumns', $this->getLanguageColumnsForPageLayoutContext($pageLayoutContext));
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
            } elseif ($pageLayoutContext->getDrawingConfiguration()->getSelectedLanguageId() === -1) {
                // In case we are not in language comparison mode and all-language is given,
                // we fall back to the default language to prevent an empty grid.
                $context->getDrawingConfiguration()->setSelectedLanguageId($context->getSiteLanguage()->getLanguageId());
            }
            $grid = $this->getGridForPageLayoutContext($context);
            $view->assign('grid', $grid);
            $view->assign('gridColumns', array_fill(1, $grid->getContext()->getBackendLayout()->getColCount(), null));
        }

        $rendered = $view->render('PageLayout/PageLayout');
        if ($renderUnused) {
            $rendered .= $this->renderUnused($request, $pageLayoutContext);
        }
        return $rendered;
    }

    protected function renderUnused(ServerRequestInterface $request, PageLayoutContext $pageLayoutContext): string
    {
        $contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class);
        $view = $this->createView($request, $pageLayoutContext);
        $unusedRecords = $contentFetcher->getUnusedRecords($pageLayoutContext);

        if (empty($unusedRecords)) {
            return '';
        }
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
        return $view->render('PageLayout/UnusedRecords');
    }

    protected function getLanguageColumnsForPageLayoutContext(PageLayoutContext $context): iterable
    {
        $contentFetcher = GeneralUtility::makeInstance(ContentFetcher::class);
        $languageColumns = [];

        // default language
        $translationInfo = $contentFetcher->getTranslationData(
            $context,
            $contentFetcher->getFlatContentRecords($context, 0),
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
                $context,
                $contentFetcher->getFlatContentRecords($context, $localizedLanguageId),
                $localizedContext->getSiteLanguage()->getLanguageId()
            );

            $translatedRows = $contentFetcher->getFlatContentRecords($context, $localizedLanguageId);

            foreach ($defaultLanguageColumnObject->getGrid()->getRows() as $rows) {
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
