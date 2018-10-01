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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * This class displays the Info/Modify screen of the Web > Template module
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptTemplateInformationModuleFunctionController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'pObj' => 'Using TypoScriptTemplateInformationModuleFunctionController::$pObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'function_key' => 'Using TypoScriptTemplateInformationModuleFunctionController::$function_key is deprecated, property will be removed in TYPO3 v10.0.',
        'extClassConf' => 'Using TypoScriptTemplateInformationModuleFunctionController::$extClassConf is deprecated, property will be removed in TYPO3 v10.0.',
        'localLangFile' => 'Using TypoScriptTemplateInformationModuleFunctionController::$localLangFile is deprecated, property will be removed in TYPO3 v10.0.',
        'tce_processed' => 'Using TypoScriptTemplateInformationModuleFunctionController::$tce_processed is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'initialize_editor' => 'Using TypoScriptTemplateInformationModuleFunctionController::initialize_editor() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'tableRowData' => 'Using TypoScriptTemplateInformationModuleFunctionController::tableRowData() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'handleExternalFunctionValue' => 'Using TypoScriptTemplateInformationModuleFunctionController::handleExternalFunctionValue() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

    /**
     * Indicator for t3editor, whether data is stored
     *
     * @var bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $tce_processed = false;

    /**
     * @var TypoScriptTemplateModuleController
     */
    protected $pObj;

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
     * Can be hardcoded to the name of a locallang.xlf file (from the same directory as the class file) to use/load
     * and is included / added to $GLOBALS['LOCAL_LANG']
     *
     * @see init()
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $localLangFile = '';

    /**
     * Contains module configuration parts from TBE_MODULES_EXT if found
     *
     * @see handleExternalFunctionValue()
     * @var array
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $extClassConf;

    /**
     * If this value is set it points to a key in the TBE_MODULES_EXT array (not on the top level..) where another classname/filepath/title can be defined for sub-subfunctions.
     * This is a little hard to explain, so see it in action; it used in the extension 'func_wizards' in order to provide yet a layer of interfacing with the backend module.
     * The extension 'func_wizards' has this description: 'Adds the 'Wizards' item to the function menu in Web>Func. This is just a framework for wizard extensions.' - so as you can see it is designed to allow further connectivity - 'level 2'
     *
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $function_key = '';

    /**
     * Init, called from parent object
     *
     * @param TypoScriptTemplateModuleController $pObj A reference to the parent (calling) object
     */
    public function init($pObj)
    {
        $this->pObj = $pObj;
        // Local lang:
        if (!empty($this->localLangFile)) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
            $this->getLanguageService()->includeLLFile($this->localLangFile);
        }
        $this->id = (int)GeneralUtility::_GP('id');
    }

    /**
     * Gets the data for a row of a HTML table in the fluid template
     *
     * @param string $label The label to be shown (e.g. 'Title:', 'Sitetitle:')
     * @param string $data The data/information to be shown (e.g. 'Template for my site')
     * @param string $field The field/variable to be sent on clicking the edit icon (e.g. 'title', 'sitetitle')
     * @param int $id The field/variable to be sent on clicking the edit icon (e.g. 'title', 'sitetitle')
     * @return array Data for a row of a HTML table
     */
    protected function tableRowData($label, $data, $field, $id)
    {
        $urlParameters = [
            'id' => $this->id,
            'edit' => [
                'sys_template' => [
                    $id => 'edit'
                ]
            ],
            'columnsOnly' => $field,
            'createExtension' => 0,
            'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
        ];
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

        return [
            'url' => $url,
            'data' => $data,
            'label' => $label
        ];
    }

    /**
     * Create an instance of \TYPO3\CMS\Core\TypoScript\ExtendedTemplateService
     * and looks for the first (visible) template
     * record. If $template_uid was given and greater than zero, this record will be checked.
     *
     * Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
     *
     * @param int $pageId The uid of the current page
     * @param int $template_uid The uid of the template record to be rendered (only if more than one template on the current page)
     * @return bool Returns TRUE if a template record was found, otherwise FALSE
     */
    protected function initialize_editor($pageId, $template_uid = 0)
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        // Get the row of the first VISIBLE template of the page. where clause like the frontend.
        $this->templateRow = $this->templateService->ext_getFirstTemplate($pageId, $template_uid);
        if (is_array($this->templateRow)) {
            return true;
        }
        return false;
    }

    /**
     * Main, called from parent object
     *
     * @return string Information of the template status or the taken actions as HTML string
     */
    public function main()
    {
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }
        // Initialize
        $existTemplate = $this->initialize_editor($this->id, $template_uid);
        $saveId = 0;
        if ($existTemplate) {
            $saveId = $this->templateRow['_ORIG_uid'] ?: $this->templateRow['uid'];
        }
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        // Create extension template
        $newId = $this->pObj->createTemplate($this->id, $saveId);
        if ($newId) {
            // Switch to new template
            $urlParameters = [
                'id' => $this->id,
                'SET[templatesOnPage]' => $newId
            ];
            $url = (string)$uriBuilder->buildUriFromRoute('web_ts', $urlParameters);
            HttpUtility::redirect($url);
        }
        $tce = null;
        if ($existTemplate) {
            $lang = $this->getLanguageService();
            $lang->includeLLFile('EXT:tstemplate/Resources/Private/Language/locallang_info.xlf');
            $assigns = [];
            $assigns['LLPrefix'] = 'LLL:EXT:tstemplate/Resources/Private/Language/locallang_info.xlf:';

            $assigns['title'] = trim($this->templateRow['title']);
            $assigns['siteTitle'] = trim($this->templateRow['sitetitle']);
            $assigns['templateRecord'] = $this->templateRow;
            if ($manyTemplatesMenu) {
                $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;
            }

            // Processing:
            $tableRows = [];
            $tableRows[] = $this->tableRowData($lang->getLL('title'), $this->templateRow['title'], 'title', $this->templateRow['uid']);
            $tableRows[] = $this->tableRowData($lang->getLL('sitetitle'), $this->templateRow['sitetitle'], 'sitetitle', $this->templateRow['uid']);
            $tableRows[] = $this->tableRowData($lang->getLL('description'), $this->templateRow['description'], 'description', $this->templateRow['uid']);
            $tableRows[] = $this->tableRowData($lang->getLL('constants'), sprintf($lang->getLL('editToView'), trim($this->templateRow['constants']) ? count(explode(LF, $this->templateRow['constants'])) : 0), 'constants', $this->templateRow['uid']);
            $tableRows[] = $this->tableRowData($lang->getLL('setup'), sprintf($lang->getLL('editToView'), trim($this->templateRow['config']) ? count(explode(LF, $this->templateRow['config'])) : 0), 'config', $this->templateRow['uid']);
            $assigns['tableRows'] = $tableRows;

            // Edit all icon:
            $urlParameters = [
                'edit' => [
                    'sys_template' => [
                        $this->templateRow['uid'] => 'edit'
                    ]
                ],
                'createExtension' => 0,
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ];
            $assigns['editAllUrl'] = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

            // Rendering of the output via fluid
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:tstemplate/Resources/Private/Templates/InformationModule.html'
            ));
            $view->assignMultiple($assigns);
            $theOutput = $view->render();
        } else {
            $theOutput = $this->pObj->noTemplate(1);
        }
        return $theOutput;
    }

    /**
     * If $this->function_key is set (which means there are two levels of object connectivity) then
     * $this->extClassConf is loaded with the TBE_MODULES_EXT configuration for that sub-sub-module
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected function handleExternalFunctionValue()
    {
        // Must clean first to make sure the correct key is set...
        $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), 'web_ts');
        if ($this->function_key) {
            $this->extClassConf = $this->pObj->getExternalItemConfig('web_ts', $this->function_key, $this->pObj->MOD_SETTINGS[$this->function_key]);
        }
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
