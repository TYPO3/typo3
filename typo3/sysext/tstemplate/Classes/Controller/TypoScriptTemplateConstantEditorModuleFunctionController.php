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
use TYPO3\CMS\Backend\Utility\BackendUtility;
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

    /**
     * @var TypoScriptTemplateModuleController
     */
    protected $pObj;

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
     * @var array
     */
    protected $constants;

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
     * @param TypoScriptTemplateModuleController $pObj A reference to the parent (calling) object
     * @param ServerRequestInterface $request
     */
    public function init($pObj, ServerRequestInterface $request)
    {
        $this->pObj = $pObj;
        $this->request = $request;
        $this->id = (int)($request->getParsedBody()['id'] ?? $request->getQueryParams()['id'] ?? 0);
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
            $this->templateService->ext_regObjectPositions((string)$this->templateRow['constants']);
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
        // Create extension template
        $this->pObj->createTemplate($this->id);
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu($this->request);
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }

        // initialize
        $existTemplate = $this->initialize_editor($this->id, $template_uid);
        if ($existTemplate) {
            $assigns['templateRecord'] = $this->templateRow;
            $assigns['manyTemplatesMenu'] = $manyTemplatesMenu;

            $saveId = empty($this->templateRow['_ORIG_uid']) ? $this->templateRow['uid'] : $this->templateRow['_ORIG_uid'];
            // Update template ?
            if ($this->request->getParsedBody()['_savedok'] ?? false) {
                $this->templateService->changed = false;
                $this->templateService->ext_procesInput($this->request->getParsedBody(), [], $this->constants, $this->templateRow);
                if ($this->templateService->changed) {
                    // Set the data to be saved
                    $recData = [];
                    $recData['sys_template'][$saveId]['constants'] = implode(LF, $this->templateService->raw);
                    // Create new  tce-object
                    $tce = GeneralUtility::makeInstance(DataHandler::class);
                    $tce->start($recData, []);
                    $tce->process_datamap();
                    // re-read the template ...
                    // re-read the constants as they have changed
                    $this->initialize_editor($this->id, $template_uid);
                }
            }
            // Resetting the menu (start). I wonder if this in any way is a violation of the menu-system. Haven't checked. But need to do it here, because the menu is dependent on the categories available.
            $this->pObj->MOD_MENU['constant_editor_cat'] = $this->templateService->ext_getCategoryLabelArray();
            $this->pObj->MOD_SETTINGS = BackendUtility::getModuleData($this->pObj->MOD_MENU, $this->request->getParsedBody()['SET'] ?? $this->request->getQueryParams()['SET'] ?? [], 'web_ts');
            // Resetting the menu (stop)
            $assigns['title'] = $this->pObj->linkWrapTemplateTitle($this->templateRow['title'], 'constants');
            if (!empty($this->pObj->MOD_MENU['constant_editor_cat'])) {
                $assigns['constantsMenu'] = BackendUtility::getDropdownMenu($this->id, 'SET[constant_editor_cat]', $this->pObj->MOD_SETTINGS['constant_editor_cat'], $this->pObj->MOD_MENU['constant_editor_cat']);
            }
            // Category and constant editor config:
            $category = $this->pObj->MOD_SETTINGS['constant_editor_cat'];

            $assigns['editorFields'] = $this->templateService->ext_printFields($this->constants, $category);
            foreach ($this->templateService->getJavaScriptInstructions() as $instruction) {
                $this->getPageRenderer()->getJavaScriptRenderer()->addJavaScriptModuleInstruction($instruction);
            }

            // Rendering of the output via fluid
            $view = GeneralUtility::makeInstance(StandaloneView::class);
            $view->setTemplatePathAndFilename('EXT:tstemplate/Resources/Private/Templates/ConstantEditor.html');
            $view->assignMultiple($assigns);
            $theOutput = $view->render();
        } else {
            $theOutput = $this->pObj->noTemplate(1);
        }
        return $theOutput;
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
