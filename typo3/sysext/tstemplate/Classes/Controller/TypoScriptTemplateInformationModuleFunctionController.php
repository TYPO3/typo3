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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

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
     * The currently selected sys_template record
     * @var array
     */
    protected $templateRow;

    /**
     * @var ExtendedTemplateService
     */
    protected $templateService;

    /**
     * Gets the data for a row of a HTML table in the fluid template
     *
     * @param string $label The label to be shown (e.g. 'Title:', 'Sitetitle:')
     * @param string $data The data/information to be shown (e.g. 'Template for my site')
     * @param string $field The field/variable to be sent on clicking the edit icon (e.g. 'title', 'sitetitle')
     * @param int $id The field/variable to be sent on clicking the edit icon (e.g. 'title', 'sitetitle')
     * @return array Data for a row of a HTML table
     */
    public function tableRowData($label, $data, $field, $id)
    {
        $urlParameters = [
            'id' => $this->pObj->id,
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
    public function initialize_editor($pageId, $template_uid = 0)
    {
        $this->templateService = GeneralUtility::makeInstance(ExtendedTemplateService::class);
        $this->templateService->init();

        // Get the row of the first VISIBLE template of the page. where clause like the frontend.
        $this->templateRow = $this->templateService->ext_getFirstTemplate($pageId, $template_uid);
        if (is_array($this->templateRow)) {
            return true;
        }
        return false;
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
        // Checking for more than one template an if, set a menu...
        $manyTemplatesMenu = $this->pObj->templateMenu();
        $template_uid = 0;
        if ($manyTemplatesMenu) {
            $template_uid = $this->pObj->MOD_SETTINGS['templatesOnPage'];
        }
        // Initialize
        $existTemplate = $this->initialize_editor($this->pObj->id, $template_uid);
        $saveId = 0;
        if ($existTemplate) {
            $saveId = $this->templateRow['_ORIG_uid'] ? : $this->templateRow['uid'];
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
            $assigns['editAllUrl'] = BackendUtility::getModuleUrl('record_edit', $urlParameters);

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
}
