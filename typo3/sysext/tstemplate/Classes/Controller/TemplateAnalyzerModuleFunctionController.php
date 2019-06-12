<?php
namespace TYPO3\CMS\Tstemplate\Controller;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * TypoScript template analyzer
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TemplateAnalyzerModuleFunctionController
{

    /**
     * @var TypoScriptTemplateModuleController
     */
    protected $pObj;

    /**
     * @var string
     */
    protected $localLanguageFilePath;

    /**
     * The currently selected sys_template record
     * @var array
     */
    protected $templateRow;

    /**
     * @var ExtendedTemplateService
     */
    protected $templateService;

    /**
     * @var int GET/POST var 'id'
     */
    protected $id;

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

        // Setting MOD_MENU items as we need them for logging:
        $this->pObj->MOD_MENU = array_merge($this->pObj->MOD_MENU, $this->modMenu());
        $this->localLanguageFilePath = 'EXT:tstemplate/Resources/Private/Language/locallang_analyzer.xlf';
        $this->pObj->modMenu_setDefaultList .= ',ts_analyzer_checkLinenum,ts_analyzer_checkSyntax';
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
    }

    /**
     * Mod menu
     *
     * @return array
     */
    protected function modMenu()
    {
        return [
            'ts_analyzer_checkSetup' => '1',
            'ts_analyzer_checkConst' => '1',
            'ts_analyzer_checkLinenum' => '1',
            'ts_analyzer_checkComments' => '1',
            'ts_analyzer_checkCrop' => '1',
            'ts_analyzer_checkSyntax' => '1'
        ];
    }

    /**
     * Initialize editor
     *
     * @param int $pageId
     * @param int $templateUid
     * @return bool
     */
    protected function initialize_editor($pageId, $templateUid = 0)
    {
        // Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        // Gets the rootLine
        $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
        $rootLine = $rootlineUtility->get();

        // This generates the constants/config + hierarchy info for the template.
        $this->templateService->runThroughTemplates($rootLine, $templateUid);

        // Get the row of the first VISIBLE template of the page. whereclause like the frontend.
        $this->templateRow = $this->templateService->ext_getFirstTemplate($pageId, $templateUid);
        return is_array($this->templateRow);
    }

    /**
     * Main, called from parent object
     *
     * @return string
     */
    public function main()
    {
        // Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
        // Checking for more than one template an if, set a menu...

        $assigns = [];
        $template_uid = 0;
        $assigns['manyTemplatesMenu'] = $this->pObj->templateMenu($this->request);
        $assigns['LLPrefix'] = 'LLL:' . $this->localLanguageFilePath . ':';
        if ($assigns['manyTemplatesMenu']) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }

        $assigns['existTemplate'] = $this->initialize_editor($this->id, $template_uid);
        if ($assigns['existTemplate']) {
            $assigns['siteTitle'] = trim($this->templateRow['sitetitle']);
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['linkWrappedTemplateTitle'] = $this->pObj->linkWrapTemplateTitle($this->templateRow['title']);
        }

        $this->templateService->clearList_const_temp = array_flip($this->templateService->clearList_const);
        $this->templateService->clearList_setup_temp = array_flip($this->templateService->clearList_setup);
        $pointer = count($this->templateService->hierarchyInfo);
        $hierarchyInfo = $this->templateService->ext_process_hierarchyInfo([], $pointer);
        $assigns['hierarchy'] = implode(array_reverse($this->templateService->ext_getTemplateHierarchyArr(
            $hierarchyInfo,
            '',
            [],
            1
        )), '');

        $urlParameters = [
            'id' => $this->id,
            'template' => 'all'
        ];
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $assigns['moduleLink'] = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);

        $assigns['template'] = $template = ($this->request->getQueryParams()['template'] ?? null);
        $addParams = $template ? '&template=' . $template : '';
        $assigns['checkboxes'] = [
            'ts_analyzer_checkLinenum' => [
                'id' => 'checkTs_analyzer_checkLinenum',
                'll' => 'lineNumbers'
            ],
            'ts_analyzer_checkSyntax' => [
                'id' => 'checkTs_analyzer_checkSyntax',
                'll' => 'syntaxHighlight'
            ]
        ];

        if (!$this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax']) {
            $assigns['checkboxes']['ts_analyzer_checkComments'] = [
                'id' => 'checkTs_analyzer_checkComments',
                'll' => 'comments'
            ];
            $assigns['checkboxes']['ts_analyzer_checkCrop'] = [
                'id' => 'checkTs_analyzer_checkCrop',
                'll' => 'cropLines'
            ];
        }

        foreach ($assigns['checkboxes'] as $key => $conf) {
            $assigns['checkboxes'][$key]['label'] = BackendUtility::getFuncCheck(
                $this->id,
                'SET[' . $key . ']',
                $this->pObj->MOD_SETTINGS[$key],
                '',
                $addParams,
                'id="' . $conf['id'] . '"'
            );
        }

        if ($template) {
            $this->templateService->ext_lineNumberOffset = 0;
            $this->templateService->ext_lineNumberOffset_mode = 'const';
            $assigns['constants'] = [];
            foreach ($this->templateService->constants as $key => $val) {
                $currentTemplateId = $this->templateService->hierarchyInfo[$key]['templateID'];
                if ($currentTemplateId == $template || $template === 'all') {
                    $assigns['constants'][] = [
                        'title' => $this->templateService->hierarchyInfo[$key]['title'],
                        'content' => $this->templateService->ext_outputTS(
                            [$val],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'],
                            0
                        )
                    ];
                    if ($template !== 'all') {
                        break;
                    }
                }
                $this->templateService->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
            }

            // Output Setup
            $this->templateService->ext_lineNumberOffset = 0;
            $this->templateService->ext_lineNumberOffset_mode = 'setup';
            $assigns['setups'] = [];
            foreach ($this->templateService->config as $key => $val) {
                $currentTemplateId = $this->templateService->hierarchyInfo[$key]['templateID'];
                if ($currentTemplateId == $template || $template === 'all') {
                    $assigns['setups'][] = [
                        'title' => $this->templateService->hierarchyInfo[$key]['title'],
                        'content' => $this->templateService->ext_outputTS(
                            [$val],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'],
                            $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'],
                            0
                        )
                    ];
                    if ($template !== 'all') {
                        break;
                    }
                }
                $this->templateService->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
            }
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:tstemplate/Resources/Private/Templates/TemplateAnalyzerModuleFunction.html'
        ));
        $view->assignMultiple($assigns);

        return $view->render();
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
