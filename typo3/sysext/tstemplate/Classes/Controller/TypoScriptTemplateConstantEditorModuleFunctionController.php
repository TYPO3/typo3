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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * TypoScript Constant editor
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
class TypoScriptTemplateConstantEditorModuleFunctionController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'pObj' => 'Using TypoScriptTemplateConstantEditorModuleFunctionController::$pObj is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'function_key' => 'Using TypoScriptTemplateConstantEditorModuleFunctionController::$function_key is deprecated, property will be removed in TYPO3 v10.0.',
        'extClassConf' => 'Using TypoScriptTemplateConstantEditorModuleFunctionController::$extClassConf is deprecated, property will be removed in TYPO3 v10.0.',
        'localLangFile' => 'Using TypoScriptTemplateConstantEditorModuleFunctionController::$localLangFile is deprecated, property will be removed in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'initialize_editor' => 'Using TypoScriptTemplateConstantEditorModuleFunctionController::initialize_editor() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'handleExternalFunctionValue' => 'Using TypoScriptTemplateConstantEditorModuleFunctionController::handleExternalFunctionValue() is deprecated, method will be removed in TYPO3 v10.0.',
    ];

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
     * @var array
     */
    protected $constants;

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
     * Initialize editor
     *
     * Initializes the module.
     * Done in this function because we may need to re-initialize if data is submitted!
     *
     * @param int $pageId
     * @param int $template_uid
     * @return bool
     */
    protected function initialize_editor($pageId, $template_uid = 0)
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);

        // Get the row of the first VISIBLE template of the page. whereclause like the frontend.
        $this->templateRow = $this->templateService->ext_getFirstTemplate($pageId, $template_uid);
        // IF there was a template...
        if (is_array($this->templateRow)) {
            // Gets the rootLine
            $rootlineUtility = GeneralUtility::makeInstance(RootlineUtility::class, $pageId);
            $rootLine = $rootlineUtility->get();
            // This generates the constants/config + hierarchy info for the template.
            $this->templateService->runThroughTemplates($rootLine, $template_uid);
            // The editable constants are returned in an array.
            $this->constants = $this->templateService->generateConfig_constants();
            // The returned constants are sorted in categories, that goes into the $tmpl->categories array
            $this->templateService->ext_categorizeEditableConstants($this->constants);
            // This array will contain key=[expanded constant name], value=line number in template.
            $this->templateService->ext_regObjectPositions($this->templateRow['constants']);
            return true;
        }
        return false;
    }

    /**
     * Main, called from parent object
     *
     * @return string
     */
    public function main()
    {
        $assigns = [];
        $assigns['LLPrefix'] = 'LLL:EXT:tstemplate/Resources/Private/Language/locallang_ceditor.xlf:';
        // Create extension template
        $this->pObj->createTemplate($this->id);
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }

        // initialize
        $existTemplate = $this->initialize_editor($this->id, $template_uid);
        if ($existTemplate) {
            $assigns['siteTitle'] = trim($this->templateRow['sitetitle']);
            $assigns['templateRecord'] = $this->templateRow;
            if ($manyTemplatesMenu) {
                $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;
            }

            $saveId = $this->templateRow['_ORIG_uid'] ?: $this->templateRow['uid'];
            // Update template ?
            if (GeneralUtility::_POST('_savedok')) {
                $this->templateService->changed = 0;
                $this->templateService->ext_procesInput(GeneralUtility::_POST(), [], $this->constants, $this->templateRow);
                if ($this->templateService->changed) {
                    // Set the data to be saved
                    $recData = [];
                    $recData['sys_template'][$saveId]['constants'] = implode($this->templateService->raw, LF);
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->start($recData, []);
                    $tce->process_datamap();
                    // Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
                    $tce->clear_cacheCmd('all');
                    // re-read the template ...
                    // re-read the constants as they have changed
                    $this->initialize_editor($this->id, $template_uid);
                }
            }
            // Resetting the menu (start). I wonder if this in any way is a violation of the menu-system. Haven't checked. But need to do it here, because the menu is dependent on the categories available.
            $this->pObj->MOD_MENU['constant_editor_cat'] = $this->templateService->ext_getCategoryLabelArray();
            $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), 'web_ts');
            // Resetting the menu (stop)
            $assigns['title'] = $this->pObj->linkWrapTemplateTitle($this->templateRow['title'], 'constants');
            if (!empty($this->pObj->MOD_MENU['constant_editor_cat'])) {
                $assigns['constantsMenu'] = BackendUtility::getDropdownMenu($this->id, 'SET[constant_editor_cat]', $this->pObj->MOD_SETTINGS['constant_editor_cat'], $this->pObj->MOD_MENU['constant_editor_cat']);
            }
            // Category and constant editor config:
            $category = $this->pObj->MOD_SETTINGS['constant_editor_cat'];

            $printFields = trim($this->templateService->ext_printFields($this->constants, $category));
            foreach ($this->templateService->getInlineJavaScript() as $name => $inlineJavaScript) {
                $this->getPageRenderer()->addJsInlineCode($name, $inlineJavaScript);
            }

            if ($printFields) {
                $assigns['printFields'] = $printFields;
            }
            // Rendering of the output via fluid
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
                'EXT:tstemplate/Resources/Private/Templates/ConstantEditor.html'
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

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
