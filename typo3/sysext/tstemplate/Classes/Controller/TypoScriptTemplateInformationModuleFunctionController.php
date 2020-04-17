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
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
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
        $manyTemplatesMenu = $this->pObj->templateMenu($this->request);
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
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // Create extension template
        $newId = $this->pObj->createTemplate($this->id, (int)$saveId);
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
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
