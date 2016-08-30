<?php
namespace TYPO3\CMS\T3editor\Hook;

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
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\T3editor\T3editor;
use TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController;

/**
 * Hook for tstemplate info
 */
class TypoScriptTemplateInfoHook
{
    /**
     * @var string
     */
    protected $ajaxSaveType = 'TypoScriptTemplateInformationModuleFunctionController';

    /**
     * Hook-function:
     * called in typo3/sysext/tstemplate_info/Classes/Controller/TypoScriptTemplateInformationModuleFunctionController.php
     *
     * @param array $parameters
     * @param TypoScriptTemplateInformationModuleFunctionController $pObj
     * @return void
     */
    public function preOutputProcessingHook(&$parameters, $pObj)
    {
        foreach ($parameters['formData']['processedTca']['columns'] as $column => &$definition) {
            if ($definition['config']['type'] === 'text') {
                $definition['config']['renderType'] = 't3editor';
                $definition['config']['format'] = 'typoscript';
                $definition['config']['ajaxSaveType'] = $this->ajaxSaveType;
            }
        }
    }

    /**
     * Process saving request like in class.tstemplateinfo.php (TCE processing)
     *
     * @param array $parameters
     * @param T3editor $pObj
     * @return bool TRUE if successful
     */
    public function save($parameters, $pObj)
    {
        $savingsuccess = false;
        if ($parameters['type'] == $this->ajaxSaveType) {
            $pageId = (int)GeneralUtility::_GP('id');
            if (!is_numeric($pageId) || $pageId < 1) {
                return false;
            }
            // If given use the requested template_uid
            // if not, use the first template-record on the page (in this case there should only be one record!)
            $set = GeneralUtility::_GP('SET');
            $template_uid = $set['templatesOnPage'] ?: 0;
            // Defined global here!
            $tmpl = GeneralUtility::makeInstance(ExtendedTemplateService::class);
            $tmpl->init();
            // Get the first template record on the page, which might be hidden as well
            // (for instance the TypoScript constant editor is persisting to the first template)
            $tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
            $existTemplate = is_array($tplRow);
            if ($existTemplate) {
                $saveId = $tplRow['_ORIG_uid'] ?: $tplRow['uid'];
                // Update template ?
                $POST = GeneralUtility::_POST();
                if ($POST['submit']) {
                    // Set the data to be saved
                    $recData = [];
                    if (is_array($POST['data'])) {
                        $recData = $POST['data'];
                    }
                    if (!empty($recData)) {
                        // process template row before saving
                        $tstemplateinfo = GeneralUtility::makeInstance(TypoScriptTemplateInformationModuleFunctionController::class);
                        /* @var $tstemplateinfo TypoScriptTemplateInformationModuleFunctionController */
                        // load the MOD_SETTINGS in order to check if the includeTypoScriptFileContent is set
                        $tstemplateinfo->pObj = $pObj;
                        $tstemplateinfo->pObj->MOD_SETTINGS = BackendUtility::getModuleData(['includeTypoScriptFileContent' => true], [], 'web_ts');
                        $recData['sys_template'][$saveId] = $tstemplateinfo->processTemplateRowBeforeSaving($recData['sys_template'][$saveId]);
                        // Create new tce-object
                        $tce = GeneralUtility::makeInstance(DataHandler::class);
                        // Initialize
                        $tce->start($recData, []);
                        // Saved the stuff
                        $tce->process_datamap();
                        // Clear the cache (note: currently only admin-users can clear the
                        // cache in tce_main.php)
                        $tce->clear_cacheCmd('all');
                        $savingsuccess = true;
                    }
                }
            }
        }
        return $savingsuccess;
    }
}
