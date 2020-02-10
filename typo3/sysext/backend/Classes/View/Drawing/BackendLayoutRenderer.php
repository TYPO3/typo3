<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Backend\View\Drawing;

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

use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\Grid;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumn;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridRow;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext;
use TYPO3\CMS\Extbase\Mvc\Request;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\TemplateView;

/**
 * Backend Layout Renderer
 *
 * Draws a page layout - essentially, behaves as a wrapper for a view
 * which renders the Resources/Private/PageLayout/PageLayout template
 * with necessary assigned template variables.
 *
 * - Initializes the clipboard used in the page layout
 * - Inserts an encoded paste icon as JS which is made visible when clipboard elements are registered
 */
class BackendLayoutRenderer
{
    use LoggerAwareTrait;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var BackendLayout
     */
    protected $backendLayout;

    /**
     * @var Clipboard
     */
    protected $clipboard;

    /**
     * @var TemplateView
     */
    protected $view;

    public function __construct(BackendLayout $backendLayout)
    {
        $this->backendLayout = $backendLayout;
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->initializeClipboard();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $controllerContext = $objectManager->get(ControllerContext::class);
        $request = $objectManager->get(Request::class);
        $controllerContext->setRequest($request);
        $this->view = GeneralUtility::makeInstance(TemplateView::class);
        $this->view->getRenderingContext()->setControllerContext($controllerContext);
        $this->view->getRenderingContext()->getTemplatePaths()->fillDefaultsByPackageName('backend');
        $this->view->getRenderingContext()->setControllerName('PageLayout');
        $this->view->assign('backendLayout', $backendLayout);
    }

    /**
     * @param bool $renderUnused If true, renders the bottom column with unused records
     * @return string
     */
    public function drawContent(bool $renderUnused = true): string
    {
        $this->view->assign('hideRestrictedColumns', (bool)(BackendUtility::getPagesTSconfig($this->backendLayout->getDrawingConfiguration()->getPageId())['mod.']['web_layout.']['hideRestrictedCols'] ?? false));
        if (!$this->backendLayout->getDrawingConfiguration()->getLanguageMode()) {
            $this->view->assign('grid', $this->backendLayout->getGrid());
        }
        $this->view->assign('newContentTitle', $this->getLanguageService()->getLL('newContentElement'));
        $this->view->assign('newContentTitleShort', $this->getLanguageService()->getLL('content'));

        $rendered = $this->view->render('PageLayout');
        if ($renderUnused) {
            $unusedBackendLayout = clone $this->backendLayout;
            $unusedBackendLayout->getDrawingConfiguration()->setLanguageColumnsPointer($this->backendLayout->getDrawingConfiguration()->getLanguageColumnsPointer());
            $unusedRecords = $this->backendLayout->getContentFetcher()->getUnusedRecords();

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

                $unusedGrid = GeneralUtility::makeInstance(Grid::class, $unusedBackendLayout);
                $unusedRow = GeneralUtility::makeInstance(GridRow::class, $unusedBackendLayout);
                $unusedColumn = GeneralUtility::makeInstance(GridColumn::class, $unusedBackendLayout, ['colPos' => 99, 'name' => 'unused'], $unusedRecords);

                $unusedGrid->addRow($unusedRow);
                $unusedRow->addColumn($unusedColumn);

                $this->view->assign('unusedGrid', $unusedGrid);
                $rendered .= $this->view->render('UnusedRecords');
            }
        }
        return $rendered;
    }

    /**
     * Initializes the clipboard for generating paste links
     *
     * @see \TYPO3\CMS\Backend\Controller\ContextMenuController::clipboardAction()
     * @see \TYPO3\CMS\Filelist\Controller\FileListController::indexAction()
     */
    protected function initializeClipboard(): void
    {
        $this->clipboard = GeneralUtility::makeInstance(Clipboard::class);
        $this->clipboard->initializeClipboard();
        $this->clipboard->lockToNormal();
        $this->clipboard->cleanCurrent();
        $this->clipboard->endClipboard();

        $elFromTable = $this->clipboard->elFromTable('tt_content');
        if (!empty($elFromTable) && $this->backendLayout->getDrawingConfiguration()->isPageEditable()) {
            $pasteItem = (int)substr(key($elFromTable), 11);
            $pasteRecord = BackendUtility::getRecord('tt_content', (int)$pasteItem);
            $pasteTitle = (string)($pasteRecord['header'] ?: $pasteItem);
            $copyMode = $this->clipboard->clipData['normal']['mode'] ? '-' . $this->clipboard->clipData['normal']['mode'] : '';
            $addExtOnReadyCode = '
                     top.pasteIntoLinkTemplate = '
                . $this->drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-into', 'pasteIntoColumn')
                . ';
                    top.pasteAfterLinkTemplate = '
                . $this->drawPasteIcon($pasteItem, $pasteTitle, $copyMode, 't3js-paste-after', 'pasteAfterRecord')
                . ';';
        } else {
            $addExtOnReadyCode = '
                top.pasteIntoLinkTemplate = \'\';
                top.pasteAfterLinkTemplate = \'\';';
        }
        GeneralUtility::makeInstance(PageRenderer::class)->addJsInlineCode('pasteLinkTemplates', $addExtOnReadyCode);
    }

    /**
     * Draw a paste icon either for pasting into a column or for pasting after a record
     *
     * @param int $pasteItem ID of the item in the clipboard
     * @param string $pasteTitle Title for the JS modal
     * @param string $copyMode copy or cut
     * @param string $cssClass CSS class to determine if pasting is done into column or after record
     * @param string $title title attribute of the generated link
     *
     * @return string Generated HTML code with link and icon
     */
    private function drawPasteIcon(int $pasteItem, string $pasteTitle, string $copyMode, string $cssClass, string $title): string
    {
        $pasteIcon = json_encode(
            ' <a data-content="' . htmlspecialchars((string)$pasteItem) . '"'
            . ' data-title="' . htmlspecialchars($pasteTitle) . '"'
            . ' data-severity="warning"'
            . ' class="t3js-paste t3js-paste' . htmlspecialchars($copyMode) . ' ' . htmlspecialchars($cssClass) . ' btn btn-default btn-sm"'
            . ' title="' . htmlspecialchars($this->getLanguageService()->getLL($title)) . '">'
            . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
            . '</a>'
        );
        return $pasteIcon;
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
