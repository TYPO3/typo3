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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This class displays the Info/Modify screen of the Web > Template module
 */
class TypoScriptTemplateInformationModuleFunctionController extends AbstractFunctionModule
{
    /**
     * Indicator for t3editor, whether data is stored
     *
     * @var bool
     */
    public $tce_processed = false;

    /**
     * @var TypoScriptTemplateModuleController
     */
    public $pObj;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Creates a row for a HTML table
     *
     * @param string $label The label to be shown (e.g. 'Title:', 'Sitetitle:')
     * @param string $data The data/information to be shown (e.g. 'Template for my site')
     * @param string $field The field/variable to be sent on clicking the edit icon (e.g. 'title', 'sitetitle')
     * @param int $id The field/variable to be sent on clicking the edit icon (e.g. 'title', 'sitetitle')
     * @return string A row for a HTML table
     */
    public function tableRow($label, $data, $field, $id)
    {
        $lang = $this->getLanguageService();
        if ($field === 'config' || $field === 'constants') {
            $urlParameters = [
                'id' => $this->pObj->id,
                'e' => [
                    $field => 1
                ]
            ];
            $url = BackendUtility::getModuleUrl('web_ts', $urlParameters);
        } else {
            $urlParameters = [
                'edit' => [
                    'sys_template' => [
                        $id => 'edit'
                    ]
                ],
                'columnsOnly' => $field,
                'createExtension' => 0,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
        }
        $title = $lang->sL('LLL:EXT:lang/locallang_common.xlf:editField', true);
        $startAnchor = '<a href="' . htmlspecialchars($url) . '" title="' . $title . '">';
        $icon = $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render();
        $ret = '<tr><td>';
        $ret .= $startAnchor . '<strong>' . $label . '</strong></a>';
        $ret .= '</td><td width="80%">' . $data . '</td><td>' . $startAnchor . '<span class="btn btn-default">' . $icon . '</span></a></td></tr>';
        return $ret;
    }

    /**
     * Create an instance of \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService in
     * $GLOBALS['tmpl'] and looks for the first (visible) template
     * record. If $template_uid was given and greater than zero, this record will be checked.
     *
     * Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
     *
     * @param int $pageId The uid of the current page
     * @param int $template_uid: The uid of the template record to be rendered (only if more than one template on the current page)
     * @return bool Returns TRUE if a template record was found, otherwise FALSE
     */
    public function initialize_editor($pageId, $template_uid = 0)
    {
        /** @var ExtendedTemplateService $tmpl */
        $tmpl = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $GLOBALS['tmpl'] = $tmpl;

        // Do not log time-performance information
        $tmpl->tt_track = false;
        $tmpl->init();

        // Get the row of the first VISIBLE template of the page. where clause like the frontend.
        $GLOBALS['tplRow'] = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
        if (is_array($GLOBALS['tplRow'])) {
            $GLOBALS['tplRow'] = $this->processTemplateRowAfterLoading($GLOBALS['tplRow']);
            return true;
        }
        return false;
    }

    /**
     * Process template row after loading
     *
     * @param array $tplRow Template row
     * @return array Preprocessed template row
     */
    public function processTemplateRowAfterLoading(array $tplRow)
    {
        if ($this->pObj->MOD_SETTINGS['includeTypoScriptFileContent']) {
            // Let the recursion detection counter start at 91, so that only 10 recursive calls will be resolved
            // Otherwise the editor will be bloated with way to many lines making it hard the break the cyclic recursion.
            $tplRow['config'] = TypoScriptParser::checkIncludeLines($tplRow['config'], 91);
            $tplRow['constants'] = TypoScriptParser::checkIncludeLines($tplRow['constants'], 91);
        }
        return $tplRow;
    }

    /**
     * Process template row before saving
     *
     * @param array $tplRow Template row
     * @return array Preprocessed template row
     */
    public function processTemplateRowBeforeSaving(array $tplRow)
    {
        if ($this->pObj->MOD_SETTINGS['includeTypoScriptFileContent']) {
            $tplRow = TypoScriptParser::extractIncludes_array($tplRow);
        }
        return $tplRow;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * The main processing method if this class
     *
     * @return string Information of the template status or the taken actions as HTML string
     */
    public function main()
    {
        $lang = $this->getLanguageService();
        $lang->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_info.xlf');
        $this->pObj->MOD_MENU['includeTypoScriptFileContent'] = true;
        $e = $this->pObj->e;
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }
        // Initialize
        $existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
        $tplRow = $GLOBALS['tplRow'];
        $saveId = 0;
        if ($existTemplate) {
            $saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
        }
        // Create extension template
        $newId = $this->pObj->createTemplate($this->pObj->id, $saveId);
        if ($newId) {
            // Switch to new template
            $urlParameters = [
                'id' => $this->pObj->id,
                'SET[templatesOnPage]' => $newId
            ];
            $url = BackendUtility::getModuleUrl('web_ts', $urlParameters);
            HttpUtility::redirect($url);
        }
        $tce = null;
        $theOutput = '';
        if ($existTemplate) {
            // Update template ?
            $POST = GeneralUtility::_POST();
            if (
                isset($POST['_savedok'])
                || isset($POST['_saveandclosedok'])
            ) {
                // Set the data to be saved
                $recData = [];
                $alternativeFileName = [];
                if (is_array($POST['data'])) {
                    foreach ($POST['data'] as $field => $val) {
                        switch ($field) {
                            case 'constants':
                            case 'config':
                                $recData['sys_template'][$saveId][$field] = $val;
                            break;
                        }
                    }
                }
                if (!empty($recData)) {
                    $recData['sys_template'][$saveId] = $this->processTemplateRowBeforeSaving($recData['sys_template'][$saveId]);
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->stripslashes_values = false;
                    $tce->alternativeFileName = $alternativeFileName;
                    // Initialize
                    $tce->start($recData, []);
                    // Saved the stuff
                    $tce->process_datamap();
                    // Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
                    $tce->clear_cacheCmd('all');
                    // tce were processed successfully
                    $this->tce_processed = true;
                    // re-read the template ...
                    $this->initialize_editor($this->pObj->id, $template_uid);
                    $tplRow = $GLOBALS['tplRow'];
                    // reload template menu
                    $manyTemplatesMenu = $this->pObj->templateMenu();
                }
            }
            // Hook post updating template/TCE processing
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook'])) {
                $postTCEProcessingHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postTCEProcessingHook'];
                if (is_array($postTCEProcessingHook)) {
                    $hookParameters = [
                        'POST' => $POST,
                        'tce' => $tce
                    ];
                    foreach ($postTCEProcessingHook as $hookFunction) {
                        GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
                    }
                }
            }
            $content = '<a href="#" class="t3-js-clickmenutrigger" data-table="sys_template" data-uid="' . $tplRow['uid'] . '" data-listframe="1">' . $this->iconFactory->getIconForRecord('sys_template', $tplRow, Icon::SIZE_SMALL)->render() . '</a><strong>' . htmlspecialchars($tplRow['title']) . '</strong>' . (trim($tplRow['sitetitle']) ? htmlspecialchars(' (' . $tplRow['sitetitle'] . ')') : '');
            $theOutput .= '<h2>' . $lang->getLL('templateInformation', true) . '</h2><div>' . $content . '</div>';
            if ($manyTemplatesMenu) {
                $theOutput .= '<div>' . $manyTemplatesMenu . '</div>';
            }
            $theOutput .= '<div style="padding-top: 10px;"></div>';
            $numberOfRows = 35;
            // If abort pressed, nothing should be edited:
            if (isset($POST['_saveandclosedok'])) {
                unset($e);
            }
            if (isset($e['constants'])) {
                $outCode = '<textarea name="data[constants]" rows="' . $numberOfRows . '" wrap="off" class="text-monospace enable-tab"' . $this->pObj->doc->formWidth(48, true, 'width:98%;height:70%') . ' class="text-monospace">' . htmlspecialchars($tplRow['constants']) . '</textarea>';
                $outCode .= '<input type="hidden" name="e[constants]" value="1">';
                // Display "Include TypoScript file content?" checkbox
                $outCode .= '<div class="checkbox"><label for="checkIncludeTypoScriptFileContent">' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[includeTypoScriptFileContent]', $this->pObj->MOD_SETTINGS['includeTypoScriptFileContent'], '', '&e[constants]=1', 'id="checkIncludeTypoScriptFileContent"');
                $outCode .= $lang->getLL('includeTypoScriptFileContent') . '</label></div><br />';
                $theOutput .= '<div style="padding-top: 15px;"></div>';
                $theOutput .= '<h3>' . $lang->getLL('constants', true) . '</h3>';
                $theOutput .= $outCode;
            }
            if (isset($e['config'])) {
                $outCode = '<textarea name="data[config]" rows="' . $numberOfRows . '" wrap="off" class="text-monospace enable-tab"' . $this->pObj->doc->formWidth(48, true, 'width:98%;height:70%') . ' class="text-monospace">' . htmlspecialchars($tplRow['config']) . '</textarea>';
                $outCode .= '<input type="hidden" name="e[config]" value="1">';
                // Display "Include TypoScript file content?" checkbox
                $outCode .= '<div class="checkbox"><label for="checkIncludeTypoScriptFileContent">' . BackendUtility::getFuncCheck($this->pObj->id, 'SET[includeTypoScriptFileContent]', $this->pObj->MOD_SETTINGS['includeTypoScriptFileContent'], '', '&e[config]=1', 'id="checkIncludeTypoScriptFileContent"');
                $outCode .= $lang->getLL('includeTypoScriptFileContent') . '</label></div><br />';
                $theOutput .= '<div style="padding-top: 15px;"></div>';
                $theOutput .= '<h3>' . $lang->getLL('setup', true) . '</h3>';
                $theOutput .= $outCode;
            }

            // Processing:
            $outCode = '';
            $outCode .= $this->tableRow($lang->getLL('title'), htmlspecialchars($tplRow['title']), 'title', $tplRow['uid']);
            $outCode .= $this->tableRow($lang->getLL('sitetitle'), htmlspecialchars($tplRow['sitetitle']), 'sitetitle', $tplRow['uid']);
            $outCode .= $this->tableRow($lang->getLL('description'), nl2br(htmlspecialchars($tplRow['description'])), 'description', $tplRow['uid']);
            $outCode .= $this->tableRow($lang->getLL('constants'), sprintf($lang->getLL('editToView'), trim($tplRow['constants']) ? count(explode(LF, $tplRow['constants'])) : 0), 'constants', $tplRow['uid']);
            $outCode .= $this->tableRow($lang->getLL('setup'), sprintf($lang->getLL('editToView'), trim($tplRow['config']) ? count(explode(LF, $tplRow['config'])) : 0), 'config', $tplRow['uid']);
            $outCode = '<div class="table-fit"><table class="table table-striped table-hover">' . $outCode . '</table></div>';

            // Edit all icon:
            $urlParameters = [
                'edit' => [
                    'sys_template' => [
                        $tplRow['uid'] => 'edit'
                    ]
                ],
                'createExtension' => 0,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $url = BackendUtility::getModuleUrl('record_edit', $urlParameters);
            $title = $lang->getLL('editTemplateRecord', true);
            $icon = $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render();
            $outCode .= '<br /><a class="btn btn-default" href="' . htmlspecialchars($url)
                . '"><strong>' . $icon . '&nbsp;' . $title . '</strong></a>';
            $theOutput .= '<div>' . $outCode . '</div>';

                // hook	after compiling the output
            if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook'])) {
                $postOutputProcessingHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook'];
                if (is_array($postOutputProcessingHook)) {
                    $hookParameters = [
                        'theOutput' => &$theOutput,
                        'POST' => $POST,
                        'e' => $e,
                        'tplRow' => $tplRow,
                        'numberOfRows' => $numberOfRows
                    ];
                    foreach ($postOutputProcessingHook as $hookFunction) {
                        GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
                    }
                }
            }
        } else {
            $theOutput .= $this->pObj->noTemplate(1);
        }
        return $theOutput;
    }
}
