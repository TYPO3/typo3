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
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * TypoScript Constant editor
 */
class TypoScriptTemplateConstantEditorModuleFunctionController extends AbstractFunctionModule
{
    /**
     * @var TypoScriptTemplateModuleController
     */
    public $pObj;

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
    public function initialize_editor($pageId, $template_uid = 0)
    {
        $templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $GLOBALS['tmpl'] = $templateService;
        $templateService->init();

        // Get the row of the first VISIBLE template of the page. whereclause like the frontend.
        $GLOBALS['tplRow'] = $templateService->ext_getFirstTemplate($pageId, $template_uid);
        // IF there was a template...
        if (is_array($GLOBALS['tplRow'])) {
            // Gets the rootLine
            $sys_page = GeneralUtility::makeInstance(PageRepository::class);
            $rootLine = $sys_page->getRootLine($pageId);
            // This generates the constants/config + hierarchy info for the template.
            $templateService->runThroughTemplates($rootLine, $template_uid);
            // The editable constants are returned in an array.
            $GLOBALS['theConstants'] = $templateService->generateConfig_constants();
            // The returned constants are sorted in categories, that goes into the $tmpl->categories array
            $templateService->ext_categorizeEditableConstants($GLOBALS['theConstants']);
            // This array will contain key=[expanded constant name], value=line number in template. (after edit_divider, if any)
            $templateService->ext_regObjectPositions($GLOBALS['tplRow']['constants']);
            return true;
        }
        return false;
    }

    /**
     * Get the data for display of an example
     *
     * @return array
     */
    public function getHelpConfig()
    {
        $result = [];
        $templateService = $this->getExtendedTemplateService();
        if ($templateService->helpConfig['description'] || $templateService->helpConfig['header']) {
            $result['header'] = $templateService->helpConfig['header'];
            $result['description'] = explode('//', $templateService->helpConfig['description']);
            $result['bulletList'] = explode('//', $templateService->helpConfig['bulletlist']);
        }
        return $result;
    }

    /**
     * Main
     *
     * @return string
     */
    public function main()
    {
        $assigns = [];
        $assigns['LLPrefix'] = 'LLL:EXT:tstemplate/Resources/Private/Language/locallang_ceditor.xlf:';
        // Create extension template
        $this->pObj->createTemplate($this->pObj->id);
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }

        // initialize
        $existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
        if ($existTemplate) {
            $templateService = $this->getExtendedTemplateService();
            $tplRow = $this->getTemplateRow();
            $theConstants = $this->getConstants();
            $assigns['siteTitle'] = trim($tplRow['sitetitle']);
            $assigns['templateRecord'] = $tplRow;
            if ($manyTemplatesMenu) {
                $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;
            }

            $this->getPageRenderer();
            $saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
            // Update template ?
            if (GeneralUtility::_POST('_savedok')) {
                $templateService->changed = 0;
                $templateService->ext_procesInput(GeneralUtility::_POST(), [], $theConstants, $tplRow);
                if ($templateService->changed) {
                    // Set the data to be saved
                    $recData = [];
                    $recData['sys_template'][$saveId]['constants'] = implode($templateService->raw, LF);
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->start($recData, []);
                    $tce->process_datamap();
                    // Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
                    $tce->clear_cacheCmd('all');
                    // re-read the template ...
                    $this->initialize_editor($this->pObj->id, $template_uid);
                    // re-read the constants as they have changed
                    $templateService = $this->getExtendedTemplateService();
                    $tplRow = $this->getTemplateRow();
                    $theConstants = $this->getConstants();
                }
            }
            // Resetting the menu (start). I wonder if this in any way is a violation of the menu-system. Haven't checked. But need to do it here, because the menu is dependent on the categories available.
            $this->pObj->MOD_MENU['constant_editor_cat'] = $templateService->ext_getCategoryLabelArray();
            $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, GeneralUtility::_GP('SET'), $this->pObj->MCONF['name']);
            // Resetting the menu (stop)
            $assigns['title'] = $this->pObj->linkWrapTemplateTitle($tplRow['title'], 'constants');
            if (!empty($this->pObj->MOD_MENU['constant_editor_cat'])) {
                $assigns['constantsMenu'] = BackendUtility::getDropdownMenu($this->pObj->id, 'SET[constant_editor_cat]', $this->pObj->MOD_SETTINGS['constant_editor_cat'], $this->pObj->MOD_MENU['constant_editor_cat']);
            }
            // Category and constant editor config:
            $category = $this->pObj->MOD_SETTINGS['constant_editor_cat'];
            $templateService->ext_getTSCE_config($category);

            $printFields = trim($templateService->ext_printFields($theConstants, $category));
            foreach ($templateService->getInlineJavaScript() as $name => $inlineJavaScript) {
                $this->pageRenderer->addJsInlineCode($name, $inlineJavaScript);
            }

            if ($printFields) {
                $assigns['printFields'] = $printFields;
            }
            $BE_USER_modOptions = BackendUtility::getModTSconfig(0, 'mod.' . $this->pObj->MCONF['name']);
            if ($BE_USER_modOptions['properties']['constantEditor.']['example'] != 'top') {
                $assigns['helpConfig'] = $this->getHelpConfig();
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
     * @return ExtendedTemplateService
     */
    protected function getExtendedTemplateService()
    {
        return $GLOBALS['tmpl'];
    }

    /**
     * @return array
     */
    protected function getTemplateRow()
    {
        return $GLOBALS['tplRow'];
    }

    /**
     * @return array
     */
    protected function getConstants()
    {
        return $GLOBALS['theConstants'];
    }
}
