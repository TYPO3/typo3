<?php

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

namespace TYPO3\CMS\Tstemplate\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Fluid\View\BackendTemplateView;

/**
 * TypoScript template analyzer
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TemplateAnalyzerModuleFunctionController
{
    protected TypoScriptTemplateModuleController $pObj;

    /**
     * The currently selected sys_template record
     * @var array|null
     */
    protected $templateRow;

    /**
     * @var ExtendedTemplateService
     */
    protected $templateService;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * Init, called from parent object
     *
     * @param TypoScriptTemplateModuleController $pObj
     * @param ServerRequestInterface $request
     */
    public function init($pObj, ServerRequestInterface $request)
    {
        $this->pObj = $pObj;
        $this->request = $request;
    }

    /**
     * Main, called from parent object
     *
     * @return string
     */
    public function main()
    {
        // The page id TypoScript tree should be show for
        $pageUid = (int)($this->request->getParsedBody()['id'] ?? $this->request->getQueryParams()['id'] ?? 0);
        // Set if user clicked on one template to show content of this specific one
        $selectedTemplate = ($this->request->getQueryParams()['template'] ?? '');
        // The template object browser shows info boxes with parser errors and links to template analyzer to highlight
        // affected line. Increased by one if set to avoid an off-by-one error.
        $highlightLine = (int)($this->request->getQueryParams()['highlightLine'] ?? 0);
        // 'const' or 'setup' or not set
        $highlightType = (string)($this->request->getQueryParams()['highlightType'] ?? '');

        $assigns = [
            'pageUid' => $pageUid,
        ];

        $templateUid = 0;
        $assigns['manyTemplatesMenu'] = $this->pObj->templateMenu($this->request);
        if ($assigns['manyTemplatesMenu']) {
            $templateUid = (int)$this->pObj->MOD_SETTINGS['templatesOnPage'];
        }

        $assigns['existTemplate'] = $this->initializeTemplates($pageUid, $templateUid);
        if ($assigns['existTemplate']) {
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['linkWrappedTemplateTitle'] = $this->pObj->linkWrapTemplateTitle($this->templateRow['title']);
        }

        $this->templateService->clearList_const_temp = array_flip($this->templateService->clearList_const);
        $this->templateService->clearList_setup_temp = array_flip($this->templateService->clearList_setup);
        $pointer = count($this->templateService->hierarchyInfo);
        $hierarchyInfo = $this->templateService->ext_process_hierarchyInfo([], $pointer);
        $assigns['hierarchy'] = implode('', array_reverse($this->getTemplateHierarchyArr(
            $hierarchyInfo,
            '',
            [],
            true
        )));

        $assigns['constants'] =  $this->renderTemplates($this->templateService->constants, $selectedTemplate, $highlightType === 'const', $highlightLine);
        $assigns['setups'] =  $this->renderTemplates($this->templateService->config, $selectedTemplate, $highlightType === 'setup', $highlightLine);

        if (ExtensionManagementUtility::isLoaded('t3editor')) {
            // @todo: Let EXT:t3editor add the deps via events in the render-loops above
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addCssFile('EXT:t3editor/Resources/Public/JavaScript/Contrib/codemirror/lib/codemirror.css');
            $pageRenderer->addCssFile('EXT:t3editor/Resources/Public/Css/t3editor.css');
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/T3editor/Element/CodeMirrorElement');
        }

        $view = GeneralUtility::makeInstance(BackendTemplateView::class);
        $view->setTemplateRootPaths(['EXT:tstemplate/Resources/Private/Templates']);
        $view->assignMultiple($assigns);
        return $view->render('TemplateAnalyzerModuleFunction');
    }

    protected function initializeTemplates(int $pageId, int $templateUid = 0): bool
    {
        // Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        // Gets the rootLine
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
        $rootLine = $rootlineUtility->get();

        // This generates the constants/config + hierarchy info for the template.
        $this->templateService->runThroughTemplates($rootLine, $templateUid);

        // Get the row of the first VISIBLE template of the page. where clause like the frontend.
        $this->templateRow = $this->pObj->getFirstTemplateRecordOnPage($pageId, $templateUid);
        return is_array($this->templateRow);
    }

    /**
     * Render constants or setup templates using t3editor or plain textarea
     *
     * @param array $templates
     * @param string $selectedTemplate
     * @param bool $highlight
     * @param int $highlightLine
     * @return array Modified assign array
     */
    protected function renderTemplates(array $templates, string $selectedTemplate, bool $highlight, int $highlightLine): array
    {
        $templatesMarkup = [];
        $totalLines = 0;
        foreach ($templates as $templateNumber => $templateContent) {
            $totalLines += 1 + count(explode(LF, $templateContent));
        }
        $thisLineOffset = $nextLineOffset = 0;
        foreach ($templates as $templateNumber => $templateContent) {
            $templateId = $this->templateService->hierarchyInfo[$templateNumber]['templateID'];
            $templateTitle = $this->templateService->hierarchyInfo[$templateNumber]['title'] ?? 'Template';
            // Prefix content with '[GLOBAL]' even for empty strings, the TemplateService does that, too.
            // Not replicating this leads to shifted line numbers when parser errors are reported in FE and object browser.
            // @todo: Locate where TemplateService hard prefixes this for empty strings and drop it.
            $templateContent = '[GLOBAL]' . LF . (string)$templateContent;
            $linesInTemplate = count(explode(LF, $templateContent));
            $nextLineOffset += $linesInTemplate;
            if ($selectedTemplate === 'all'
                || $templateId === $selectedTemplate
                || $highlight && $highlightLine && $highlightLine > $thisLineOffset && $highlightLine <= $nextLineOffset
            ) {
                if (ExtensionManagementUtility::isLoaded('t3editor')) {
                    // @todo: Fire event and let EXT:t3editor fill the markup
                    $templatesMarkup[] = $this->getCodeMirrorMarkup(
                        $templateTitle,
                        $thisLineOffset,
                        $linesInTemplate,
                        $totalLines,
                        $highlight ? $highlightLine : 0,
                        $templateContent
                    );
                } else {
                    $templatesMarkup[] = $this->getTextareaMarkup(
                        $templateTitle,
                        $linesInTemplate,
                        $templateContent
                    );
                }
            }
            $thisLineOffset = $nextLineOffset;
        }
        return $templatesMarkup;
    }

    protected function getCodeMirrorMarkup(
        string $label,
        int $lineOffset,
        int $lines,
        int $totalLines,
        int $highlightLine,
        string $content
    ): string {
        $codeMirrorConfig = [
            'label' => $label,
            'panel' => 'top',
            'mode' => 'TYPO3/CMS/T3editor/Mode/typoscript/typoscript',
            'autoheight' => 'true',
            'nolazyload' => 'true',
            'linedigits' => (string)strlen((string)$totalLines),
            'options' => GeneralUtility::jsonEncodeForHtmlAttribute([
                'readOnly' => true,
                'format' => 'typoscript',
                'rows' => 'auto',
                'firstLineNumber' => $lineOffset + 1,
            ], false),
        ];
        $textareaAttributes = [
            'rows' => (string)$lines,
            'readonly' => 'readonly',
        ];

        // If we want to highlight
        if ($highlightLine && $highlightLine >= $lineOffset && $highlightLine <= ($lineOffset + $lines)) {
            // Scroll to affected line and highlight line if requested
            $targetLineInTemplate = $highlightLine - $lineOffset;
            $codeMirrorConfig['scrollto'] = (string)$targetLineInTemplate;
            $codeMirrorConfig['marktext'] = GeneralUtility::jsonEncodeForHtmlAttribute([
                [
                    'from' => [
                        'line' => $targetLineInTemplate - 1,
                        'ch' => 0,
                    ],
                    'to' => [
                        'line' => $targetLineInTemplate - 1,
                        // Arbitrary high value to match full line
                        'ch' => 10000,
                    ],
                ],
            ], false);
        }

        $code = '<typo3-t3editor-codemirror ' . GeneralUtility::implodeAttributes($codeMirrorConfig, true) . '>';
        $code .= '<textarea ' . GeneralUtility::implodeAttributes($textareaAttributes, true) . '>' . htmlspecialchars($content) . '</textarea>';
        $code .= '</typo3-t3editor-codemirror>';

        return $code;
    }

    protected function getTextareaMarkup(string $title, int $linesInTemplate, string $content): string
    {
        return htmlspecialchars($title)
            . '<textarea class="form-control" rows="' . ($linesInTemplate + 1) . '" disabled>'
            . htmlspecialchars($content)
            . '</textarea>';
    }

    protected function getTemplateHierarchyArr(array $arr, string $depthData, array $keyArray, bool $first = false): array
    {
        $keyArr = [];
        foreach ($arr as $key => $value) {
            $key = preg_replace('/\\.$/', '', $key) ?? '';
            if (substr($key, -1) !== '.') {
                $keyArr[$key] = 1;
            }
        }
        $a = 0;
        $c = count($keyArr);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        foreach ($keyArr as $key => $value) {
            $HTML = '';
            $a++;
            $deeper = is_array($arr[$key . '.'] ?? false);
            $row = $arr[$key];
            $LN = $a == $c ? 'blank' : 'line';
            $BTM = $a == $c ? 'top' : '';
            $HTML .= $depthData;
            $alttext = '[' . $row['templateID'] . ']';
            $alttext .= $row['pid'] ? ' - ' . BackendUtility::getRecordPath($row['pid'], '1=1', 20) : '';
            $icon = strpos($row['templateID'], 'sys') === 0
                ? '<span title="' . htmlspecialchars($alttext) . '">' . $iconFactory->getIconForRecord('sys_template', $row, Icon::SIZE_SMALL)->render() . '</span>'
                : '<span title="' . htmlspecialchars($alttext) . '">' . $iconFactory->getIcon('mimetypes-x-content-template-static', Icon::SIZE_SMALL)->render() . '</span>';
            if (in_array($row['templateID'], $this->templateService->clearList_const) || in_array($row['templateID'], $this->templateService->clearList_setup)) {
                $urlParameters = [
                    'id' => $this->request->getParsedBody()['id'] ?? $this->request->getQueryParams()['id'] ?? null,
                    'template' => $row['templateID'],
                ];
                $aHref = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
                $A_B = '<a href="' . htmlspecialchars($aHref) . '">';
                $A_E = '</a>';
                if (($this->request->getParsedBody()['template'] ?? $this->request->getQueryParams()['template'] ?? null) == $row['templateID']) {
                    $A_B = '<strong>' . $A_B;
                    $A_E .= '</strong>';
                }
            } else {
                $A_B = '';
                $A_E = '';
            }
            $HTML .= ($first ? '' : '<span class="treeline-icon treeline-icon-join' . $BTM . '"></span>') . $icon . ' ' . $A_B
                . htmlspecialchars(GeneralUtility::fixed_lgd_cs($row['title'], $GLOBALS['BE_USER']->uc['titleLen']))
                . $A_E . '&nbsp;&nbsp;';
            $RL = $this->getRootlineNumber((int)$row['pid']);
            $statusCheckedIcon = $iconFactory->getIcon('status-status-checked', Icon::SIZE_SMALL)->render();
            $keyArray[] = '<tr>
							<td class="nowrap">' . $HTML . '</td>
							<td align="center">' . ($row['root'] ? $statusCheckedIcon : '') . '</td>
							<td align="center">' . ($row['clConf'] ? $statusCheckedIcon : '') . '</td>
							<td align="center">' . ($row['clConst'] ? $statusCheckedIcon : '') . '</td>
							<td align="center">' . ($row['pid'] ?: '') . '</td>
							<td align="center">' . ($RL >= 0 ? $RL : '') . '</td>
						</tr>';
            if ($deeper) {
                $keyArray = $this->getTemplateHierarchyArr($arr[$key . '.'], $depthData . ($first ? '' : '<span class="treeline-icon treeline-icon-' . $LN . '"></span>'), $keyArray);
            }
        }
        return $keyArray;
    }

    protected function getRootlineNumber(int $pid): int
    {
        if ($pid) {
            foreach ($this->templateService->getRootLine() as $key => $val) {
                if ((int)$val['uid'] === (int)$pid) {
                    return (int)$key;
                }
            }
        }
        return -1;
    }
}
