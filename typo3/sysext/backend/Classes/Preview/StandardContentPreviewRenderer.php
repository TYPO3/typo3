<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Preview;

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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Backend\View\Drawing\DrawingConfiguration;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawFooterHookInterface;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Class StandardContentPreviewRenderer
 *
 * Legacy preview rendering refactored from PageLayoutView.
 * Provided as default preview rendering mechanism via
 * StandardPreviewRendererResolver which detects the renderer
 * based on TCA configuration.
 *
 * Can be replaced and/or subclassed by custom implementations
 * by changing this TCA configuration.
 *
 * See also PreviewRendererInterface documentation.
 */
class StandardContentPreviewRenderer implements PreviewRendererInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function renderPageModulePreviewHeader(GridColumnItem $item): string
    {
        $record = $item->getRecord();
        $itemLabels = $item->getBackendLayout()->getDrawingConfiguration()->getItemLabels();

        $outHeader = '';

        if ($record['header']) {
            $infoArr = [];
            $this->getProcessedValue($item, 'header_position,header_layout,header_link', $infoArr);
            $hiddenHeaderNote = '';
            // If header layout is set to 'hidden', display an accordant note:
            if ($record['header_layout'] == 100) {
                $hiddenHeaderNote = ' <em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.hidden')) . ']</em>';
            }
            $outHeader = $record['date']
                ? htmlspecialchars($itemLabels['date'] . ' ' . BackendUtility::date($record['date'])) . '<br />'
                : '';
            $outHeader .= '<strong>' . $this->linkEditContent($this->renderText($record['header']), $record)
                . $hiddenHeaderNote . '</strong><br />';
        }

        return $outHeader;
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $out = '';
        $record = $item->getRecord();

        $contentTypeLabels = $item->getBackendLayout()->getDrawingConfiguration()->getContentTypeLabels();
        $languageService = $this->getLanguageService();

        // Check if a Fluid-based preview template was defined for this CType
        // and render it via Fluid. Possible option:
        // mod.web_layout.tt_content.preview.media = EXT:site_mysite/Resources/Private/Templates/Preview/Media.html
        $infoArr = [];
        $this->getProcessedValue($item, 'header_position,header_layout,header_link', $infoArr);
        $tsConfig = BackendUtility::getPagesTSconfig($record['pid'])['mod.']['web_layout.']['tt_content.']['preview.'] ?? [];
        if (!empty($tsConfig[$record['CType']])) {
            $fluidPreview = $this->renderContentElementPreviewFromFluidTemplate($record);
            if ($fluidPreview !== null) {
                return $fluidPreview;
            }
        }

        // Draw preview of the item depending on its CType
        switch ($record['CType']) {
            case 'header':
                if ($record['subheader']) {
                    $out .= $this->linkEditContent($this->renderText($record['subheader']), $record) . '<br />';
                }
                break;
            case 'uploads':
                if ($record['media']) {
                    $out .= $this->linkEditContent($this->getThumbCodeUnlinked($record, 'tt_content', 'media'), $record) . '<br />';
                }
                break;
            case 'menu':
                $contentType = $contentTypeLabels[$record['CType']];
                $out .= $this->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $record) . '<br />';
                // Add Menu Type
                $menuTypeLabel = $languageService->sL(
                    BackendUtility::getLabelFromItemListMerged($record['pid'], 'tt_content', 'menu_type', $record['menu_type'])
                );
                $menuTypeLabel = $menuTypeLabel ?: 'invalid menu type';
                $out .= $this->linkEditContent($menuTypeLabel, $record);
                if ($record['menu_type'] !== '2' && ($record['pages'] || $record['selected_categories'])) {
                    // Show pages if menu type is not "Sitemap"
                    $out .= ':' . $this->linkEditContent($this->generateListForCTypeMenu($record), $record) . '<br />';
                }
                break;
            case 'shortcut':
                if (!empty($record['records'])) {
                    $shortcutContent = [];
                    $recordList = explode(',', $record['records']);
                    foreach ($recordList as $recordIdentifier) {
                        $split = BackendUtility::splitTable_Uid($recordIdentifier);
                        $tableName = empty($split[0]) ? 'tt_content' : $split[0];
                        $shortcutRecord = BackendUtility::getRecord($tableName, $split[1]);
                        if (is_array($shortcutRecord)) {
                            $icon = $this->getIconFactory()->getIconForRecord($tableName, $shortcutRecord, Icon::SIZE_SMALL)->render();
                            $icon = BackendUtility::wrapClickMenuOnIcon(
                                $icon,
                                $tableName,
                                $shortcutRecord['uid'],
                                1,
                                '',
                                '+copy,info,edit,view'
                            );
                            $shortcutContent[] = $icon
                                . htmlspecialchars(BackendUtility::getRecordTitle($tableName, $shortcutRecord));
                        }
                    }
                    $out .= implode('<br />', $shortcutContent) . '<br />';
                }
                break;
            case 'list':
                $hookOut = '';
                if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'])) {
                    $pageLayoutView = $this->createEmulatedPageLayoutViewFromDrawingConfiguration($item->getBackendLayout()->getDrawingConfiguration());
                    $_params = ['pObj' => &$pageLayoutView, 'row' => $record];
                    foreach (
                        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$record['list_type']] ??
                        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'] ??
                        [] as $_funcRef
                    ) {
                        $hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $pageLayoutView);
                    }
                }

                if ((string)$hookOut !== '') {
                    $out .= $hookOut;
                } elseif (!empty($record['list_type'])) {
                    $label = BackendUtility::getLabelFromItemListMerged($record['pid'], 'tt_content', 'list_type', $record['list_type']);
                    if (!empty($label)) {
                        $out .= $this->linkEditContent('<strong>' . htmlspecialchars($languageService->sL($label)) . '</strong>', $record) . '<br />';
                    } else {
                        $message = sprintf($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'), $record['list_type']);
                        $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                    }
                } elseif (!empty($record['select_key'])) {
                    $out .= htmlspecialchars($languageService->sL(BackendUtility::getItemLabel('tt_content', 'select_key')))
                        . ' ' . htmlspecialchars($record['select_key']) . '<br />';
                } else {
                    $out .= '<strong>' . $languageService->getLL('noPluginSelected') . '</strong>';
                }
                $out .= htmlspecialchars($languageService->sL(BackendUtility::getLabelFromItemlist('tt_content', 'pages', $record['pages']))) . '<br />';
                break;
            default:
                $contentType = $contentTypeLabels[$record['CType']];

                if (isset($contentType)) {
                    $out .= $this->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $record) . '<br />';
                    if ($record['bodytext']) {
                        $out .= $this->linkEditContent($this->renderText($record['bodytext']), $record) . '<br />';
                    }
                    if ($record['image']) {
                        $out .= $this->linkEditContent($this->getThumbCodeUnlinked($record, 'tt_content', 'image'), $record) . '<br />';
                    }
                } else {
                    $message = sprintf(
                        $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'),
                        $record['CType']
                    );
                    $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                }
        }

        return $out;
    }

    /**
     * Render a footer for the record
     *
     * @param GridColumnItem $item
     * @return string
     */
    public function renderPageModulePreviewFooter(GridColumnItem $item): string
    {
        $content = '';
        $info = [];
        $record = $item->getRecord();
        $this->getProcessedValue($item, 'starttime,endtime,fe_group,space_before_class,space_after_class', $info);

        if (!empty($GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']) && !empty($this->record[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']])) {
            $info[] = $this->record[$GLOBALS['TCA']['tt_content']['ctrl']['descriptionColumn']];
        }

        // Call drawFooter hooks
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter'])) {
            $pageLayoutView = $this->createEmulatedPageLayoutViewFromDrawingConfiguration($item->getBackendLayout()->getDrawingConfiguration());
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawFooter'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof PageLayoutViewDrawFooterHookInterface) {
                    throw new \UnexpectedValueException($className . ' must implement interface ' . PageLayoutViewDrawFooterHookInterface::class, 1582574541);
                }
                $hookObject->preProcess($pageLayoutView, $info, $record);
            }
        }

        if (!empty($info)) {
            $content = implode('<br>', $info);
        }

        if (!empty($content)) {
            $content = '<div class="t3-page-ce-footer">' . $content . '</div>';
        }

        return $content;
    }

    public function wrapPageModulePreview(string $previewHeader, string $previewContent, GridColumnItem $item): string
    {
        $drawItem = true;
        $record = $item->getRecord();
        $hookPreviewContent = '';

        // Hook: Render an own preview of a record
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'])) {
            $pageLayoutView = $this->createEmulatedPageLayoutViewFromDrawingConfiguration($item->getBackendLayout()->getDrawingConfiguration());
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem'] ?? [] as $className) {
                $hookObject = GeneralUtility::makeInstance($className);
                if (!$hookObject instanceof PageLayoutViewDrawItemHookInterface) {
                    throw new \UnexpectedValueException($className . ' must implement interface ' . PageLayoutViewDrawItemHookInterface::class, 1582574553);
                }
                $hookObject->preProcess($pageLayoutView, $drawItem, $previewHeader, $hookPreviewContent, $record);
            }
        }

        $content = $previewHeader;

        $content .= $hookPreviewContent;
        if ($drawItem) {
            $content .= $previewContent;
        }

        $out = '<span class="exampleContent">' . $content . '</span>';

        if ($item->isDisabled()) {
            return '<span class="text-muted">' . $out . '</span>';
        }
        return $out;
    }

    protected function getProcessedValue(GridColumnItem $item, string $fieldList, array &$info): void
    {
        $itemLabels = $item->getBackendLayout()->getDrawingConfiguration()->getItemLabels();
        $record = $item->getRecord();
        $fieldArr = explode(',', $fieldList);
        foreach ($fieldArr as $field) {
            if ($record[$field]) {
                $info[] = '<strong>' . htmlspecialchars($itemLabels[$field]) . '</strong> '
                    . htmlspecialchars(BackendUtility::getProcessedValue('tt_content', $field, $record[$field]));
            }
        }
    }

    protected function renderContentElementPreviewFromFluidTemplate(array $row): ?string
    {
        $tsConfig = BackendUtility::getPagesTSconfig($row['pid'])['mod.']['web_layout.']['tt_content.']['preview.'] ?? [];
        $fluidTemplateFile = '';

        if ($row['CType'] === 'list' && !empty($row['list_type'])
            && !empty($tsConfig['list.'][$row['list_type']])
        ) {
            $fluidTemplateFile = $tsConfig['list.'][$row['list_type']];
        } elseif (!empty($tsConfig[$row['CType']])) {
            $fluidTemplateFile = $tsConfig[$row['CType']];
        }

        if ($fluidTemplateFile) {
            $fluidTemplateFile = GeneralUtility::getFileAbsFileName($fluidTemplateFile);
            if ($fluidTemplateFile) {
                try {
                    $view = GeneralUtility::makeInstance(StandaloneView::class);
                    $view->setTemplatePathAndFilename($fluidTemplateFile);
                    $view->assignMultiple($row);
                    if (!empty($row['pi_flexform'])) {
                        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                        $view->assign('pi_flexform_transformed', $flexFormService->convertFlexFormContentToArray($row['pi_flexform']));
                    }
                    return $view->render();
                } catch (\Exception $e) {
                    $this->logger->warning(sprintf(
                        'The backend preview for content element %d can not be rendered using the Fluid template file "%s": %s',
                        $row['uid'],
                        $fluidTemplateFile,
                        $e->getMessage()
                    ));

                    if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] && $this->getBackendUser()->isAdmin()) {
                        $view = GeneralUtility::makeInstance(StandaloneView::class);
                        $view->assign('error', [
                            'message' => str_replace(Environment::getProjectPath(), '', $e->getMessage()),
                            'title' => 'Error while rendering FluidTemplate preview using ' . str_replace(Environment::getProjectPath(), '', $fluidTemplateFile),
                        ]);
                        $view->setTemplateSource('<f:be.infobox title="{error.title}" state="2">{error.message}</f:be.infobox>');
                        return $view->render();
                    }
                }
            }
        }
        return null;
    }

    /**
     * Create thumbnail code for record/field but not linked
     *
     * @param mixed[] $row Record array
     * @param string $table Table (record is from)
     * @param string $field Field name for which thumbnail are to be rendered.
     * @return string HTML for thumbnails, if any.
     */
    protected function getThumbCodeUnlinked($row, $table, $field): string
    {
        return BackendUtility::thumbCode($row, $table, $field, '', '', null, 0, '', '', false);
    }

    /**
     * Processing of larger amounts of text (usually from RTE/bodytext fields) with word wrapping etc.
     *
     * @param string $input Input string
     * @return string Output string
     */
    protected function renderText(string $input): string
    {
        $input = strip_tags($input);
        $input = GeneralUtility::fixed_lgd_cs($input, 1500);
        return nl2br(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8', false));
    }

    /**
     * Generates a list of selected pages or categories for the CType menu
     *
     * @param array $record row from pages
     * @return string
     */
    protected function generateListForCTypeMenu(array $record): string
    {
        $table = 'pages';
        $field = 'pages';
        // get categories instead of pages
        if (strpos($record['menu_type'], 'categorized_') !== false) {
            $table = 'sys_category';
            $field = 'selected_categories';
        }
        if (trim($record[$field]) === '') {
            return '';
        }
        $content = '';
        $uidList = explode(',', $record[$field]);
        foreach ($uidList as $uid) {
            $uid = (int)$uid;
            $pageRecord = BackendUtility::getRecord($table, $uid, 'title');
            $content .= '<br>' . $pageRecord['title'] . ' (' . $uid . ')';
        }
        return $content;
    }

    /**
     * Will create a link on the input string and possibly a big button after the string which links to editing in the RTE.
     * Used for content element content displayed so the user can click the content / "Edit in Rich Text Editor" button
     *
     * @param string $linkText String to link. Must be prepared for HTML output.
     * @param array $row The row.
     * @return string If the whole thing was editable $str is return with link around. Otherwise just $str.
     */
    protected function linkEditContent(string $linkText, $row): string
    {
        if ($this->getBackendUser()->recordEditAccessInternals('tt_content', $row)) {
            $urlParameters = [
                'edit' => [
                    'tt_content' => [
                        $row['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI') . '#element-tt_content-' . $row['uid']
            ];
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            return '<a href="' . htmlspecialchars($url) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('edit')) . '">' . $linkText . '</a>';
        }
        return $linkText;
    }

    protected function createEmulatedPageLayoutViewFromDrawingConfiguration(DrawingConfiguration $drawingConfiguration): PageLayoutView
    {
        $pageLayoutView = GeneralUtility::makeInstance(PageLayoutView::class);
        $pageLayoutView->id = $drawingConfiguration->getPageId();
        $pageLayoutView->pageRecord = $drawingConfiguration->getPageRecord();
        $pageLayoutView->option_newWizard = $drawingConfiguration->getShowNewContentWizard();
        $pageLayoutView->defLangBinding = $drawingConfiguration->getDefaultLanguageBinding();
        $pageLayoutView->tt_contentConfig['cols'] = implode(',', $drawingConfiguration->getActiveColumns());
        $pageLayoutView->tt_contentConfig['activeCols'] = implode(',', $drawingConfiguration->getActiveColumns());
        $pageLayoutView->tt_contentConfig['showHidden'] = $drawingConfiguration->getShowHidden();
        $pageLayoutView->tt_contentConfig['sys_language_uid'] = $drawingConfiguration->getLanguageColumnsPointer();
        if ($drawingConfiguration->getLanguageMode()) {
            $pageLayoutView->tt_contentConfig['languageMode'] = 1;
            $pageLayoutView->tt_contentConfig['languageCols'] = $drawingConfiguration->getLanguageColumns();
            $pageLayoutView->tt_contentConfig['languageColsPointer'] = $drawingConfiguration->getLanguageColumnsPointer();
        }
        return $pageLayoutView;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getIconFactory(): IconFactory
    {
        return GeneralUtility::makeInstance(IconFactory::class);
    }
}
