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

use TYPO3\CMS\Backend\Module\AbstractFunctionModule;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * TypoScript template analyzer
 */
class TemplateAnalyzerModuleFunctionController extends AbstractFunctionModule
{
    /**
     * @var TypoScriptTemplateModuleController
     */
    public $pObj;

    /**
     * Init
     *
     * @param TypoScriptTemplateModuleController $pObj
     * @param array $conf
     * @return void
     */
    public function init(&$pObj, $conf)
    {
        parent::init($pObj, $conf);
        $this->getLanguageService()->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_analyzer.xlf');
        $this->pObj->modMenu_setDefaultList .= ',ts_analyzer_checkLinenum,ts_analyzer_checkSyntax';
    }

    /**
     * Mod menu
     *
     * @return array
     */
    public function modMenu()
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
     * @param int $template_uid
     * @return bool
     */
    public function initialize_editor($pageId, $template_uid = 0)
    {
        // Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
        $templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $GLOBALS['tmpl'] = $templateService;

        // Do not log time-performance information
        $templateService->tt_track = false;
        $templateService->init();

        // Gets the rootLine
        $sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $GLOBALS['rootLine'] = $sys_page->getRootLine($pageId);

        // This generates the constants/config + hierarchy info for the template.
        $templateService->runThroughTemplates($GLOBALS['rootLine'], $template_uid);

        // Get the row of the first VISIBLE template of the page. whereclause like the frontend.
        $GLOBALS['tplRow'] = $templateService->ext_getFirstTemplate($pageId, $template_uid);
        return is_array($GLOBALS['tplRow']);
    }

    /**
     * Main
     *
     * @return string
     */
    public function main()
    {
        $theOutput = '';

        // Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }

        $existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);

        // initialize
        $lang = $this->getLanguageService();
        if ($existTemplate) {
            $siteTitle = trim($GLOBALS['tplRow']['sitetitle']);
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            $theOutput .= '<h3>' . $lang->getLL('currentTemplate', true) . '</h3>';
            $theOutput .= $iconFactory->getIconForRecord('sys_template', $GLOBALS['tplRow'], Icon::SIZE_SMALL)->render()
                . '<strong>' . $this->pObj->linkWrapTemplateTitle($GLOBALS['tplRow']['title']) . '</strong>'
                . htmlspecialchars($siteTitle ? ' (' . $siteTitle . ')' : '');
        }
        if ($manyTemplatesMenu) {
            $theOutput .= '<div>' . $manyTemplatesMenu . '</div>';
        }
        $templateService = $this->getExtendedTemplateService();
        $templateService->clearList_const_temp = array_flip($templateService->clearList_const);
        $templateService->clearList_setup_temp = array_flip($templateService->clearList_setup);
        $pointer = count($templateService->hierarchyInfo);
        $hierarchyInfo = $templateService->ext_process_hierarchyInfo([], $pointer);
        $head = '<thead><tr>';
        $head .= '<th>' . $lang->getLL('title', true) . '</th>';
        $head .= '<th>' . $lang->getLL('rootlevel', true) . '</th>';
        $head .= '<th>' . $lang->getLL('clearSetup', true) . '</th>';
        $head .= '<th>' . $lang->getLL('clearConstants', true) . '</th>';
        $head .= '<th>' . $lang->getLL('pid', true) . '</th>';
        $head .= '<th>' . $lang->getLL('rootline', true) . '</th>';
        $head .= '<th>' . $lang->getLL('nextLevel', true) . '</th>';
        $head .= '</tr></thead>';
        $hierar = implode(array_reverse($templateService->ext_getTemplateHierarchyArr($hierarchyInfo, '', [], 1)), '');
        $hierar = '<div class="table-fit"><table class="table table-striped table-hover" id="ts-analyzer">' . $head . $hierar . '</table></div>';
        $theOutput .= '<div style="padding-top: 5px;"></div>';
        $theOutput .= '<h2>' . $lang->getLL('templateHierarchy', true) . '</h2>';
        $theOutput .= '<div>' . $hierar . '</div>';
        $urlParameters = [
            'id' => $GLOBALS['SOBE']->id,
            'template' => 'all'
        ];
        $aHref = BackendUtility::getModuleUrl('web_ts', $urlParameters);

        $completeLink = '<p><a href="' . htmlspecialchars($aHref) . '" class="btn btn-default">' . $lang->getLL('viewCompleteTS', true) . '</a></p>';
        $theOutput .= '<div style="padding-top: 5px;"></div>';
        $theOutput .= '<h2>' . $lang->getLL('completeTS', true) . '</h2>';
        $theOutput .= '<div>' . $completeLink . '</div>';
        $theOutput .= '<div style="padding-top: 15px;"></div>';
        // Output options
        $theOutput .= '<h2>' . $lang->getLL('displayOptions', true) . '</h2>';

        $template = GeneralUtility::_GET('template');
        $addParams = $template ? '&template=' . $template : '';
        $theOutput .= '<div class="tst-analyzer-options">' .
            '<div class="checkbox"><label for="checkTs_analyzer_checkLinenum">' .
                BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkLinenum]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'], '', $addParams, 'id="checkTs_analyzer_checkLinenum"') .
                $lang->getLL('lineNumbers', true) .
            '</label></div>' .
            '<div class="checkbox"><label for="checkTs_analyzer_checkSyntax">' .
                BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkSyntax]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'], '', $addParams, 'id="checkTs_analyzer_checkSyntax"') .
                $lang->getLL('syntaxHighlight', true) . '</label> ' .
            '</label></div>';
        if (!$this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax']) {
            $theOutput .=
                '<div class="checkbox"><label for="checkTs_analyzer_checkComments">' .
                    BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkComments]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'], '', $addParams, 'id="checkTs_analyzer_checkComments"') .
                    $lang->getLL('comments', true) .
                '</label></div>' .
                '<div class="checkbox"><label for="checkTs_analyzer_checkCrop">' .
                    BackendUtility::getFuncCheck($this->pObj->id, 'SET[ts_analyzer_checkCrop]', $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'], '', $addParams, 'id="checkTs_analyzer_checkCrop"') .
                    $lang->getLL('cropLines', true) .
                '</label></div>';
        }
        $theOutput .=  '</div>';
        $theOutput .= '<div style="padding-top: 25px;"></div>';

        if ($template) {
            // Output Constants
            $theOutput .= '<h2>' . $lang->getLL('constants', true) . '</h2>';

            $templateService->ext_lineNumberOffset = 0;
            $templateService->ext_lineNumberOffset_mode = 'const';
            foreach ($templateService->constants as $key => $val) {
                $currentTemplateId = $templateService->hierarchyInfo[$key]['templateID'];
                if ($currentTemplateId == $template || $template === 'all') {
                    $theOutput .= '
						<h3>' . htmlspecialchars($templateService->hierarchyInfo[$key]['title']) . '</h3>
						<div class="nowrap">' .
                            $templateService->ext_outputTS([$val], $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'], 0) .
                        '</div>
					';
                    if ($template !== 'all') {
                        break;
                    }
                }
                $templateService->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
            }

            // Output Setup
            $theOutput .= '<div style="padding-top: 15px;"></div>';
            $theOutput .= '<h2>' . $lang->getLL('setup', true) . '</h2>';
            $templateService->ext_lineNumberOffset = 0;
            $templateService->ext_lineNumberOffset_mode = 'setup';
            foreach ($templateService->config as $key => $val) {
                $currentTemplateId = $templateService->hierarchyInfo[$key]['templateID'];
                if ($currentTemplateId == $template || $template == 'all') {
                    $theOutput .= '
						<h3>' . htmlspecialchars($templateService->hierarchyInfo[$key]['title']) . '</h3>
						<div class="nowrap">' .
                            $templateService->ext_outputTS([$val], $this->pObj->MOD_SETTINGS['ts_analyzer_checkLinenum'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkComments'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkCrop'], $this->pObj->MOD_SETTINGS['ts_analyzer_checkSyntax'], 0) .
                        '</div>
					';
                    if ($template !== 'all') {
                        break;
                    }
                }
                $templateService->ext_lineNumberOffset += count(explode(LF, $val)) + 1;
            }
        }
        return $theOutput;
    }

    /**
     * @return ExtendedTemplateService
     */
    protected function getExtendedTemplateService()
    {
        return $GLOBALS['tmpl'];
    }
}
