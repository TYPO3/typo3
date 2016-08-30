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

/**
 * Hook for tstemplate info
 */
class TypoScriptTemplateInfoHook
{
    /**
     * @var \TYPO3\CMS\T3editor\T3editor
     */
    protected $t3editor = null;

    /**
     * @var string
     */
    protected $ajaxSaveType = 'TypoScriptTemplateInformationModuleFunctionController';

    /**
     * @return \TYPO3\CMS\T3editor\T3editor
     */
    protected function getT3editor()
    {
        if ($this->t3editor == null) {
            $this->t3editor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\T3editor\T3editor::class)->setMode(\TYPO3\CMS\T3editor\T3editor::MODE_TYPOSCRIPT)->setAjaxSaveType($this->ajaxSaveType);
        }
        return $this->t3editor;
    }

    /**
     * Hook-function: inject t3editor JavaScript code before the page is compiled
     * called in \TYPO3\CMS\Backend\Template\DocumentTemplate:startPage
     *
     * @return void
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::startPage
     */
    public function preStartPageHook()
    {
        // Enable editor in Template-Modul
        if (\TYPO3\CMS\Core\Utility\GeneralUtility::_GET('M') === 'web_ts') {
            $t3editor = $this->getT3editor();
            // Insert javascript code
            $t3editor->getJavascriptCode();
        }
    }

    /**
     * Hook-function:
     * called in typo3/sysext/tstemplate_info/Classes/Controller/TypoScriptTemplateInformationModuleFunctionController.php
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController $pObj
     * @return void
     */
    public function postOutputProcessingHook($parameters, $pObj)
    {
        $t3editor = $this->getT3editor();
        $t3editor->getJavascriptCode();
        foreach (['constants', 'config'] as $type) {
            if ($parameters['e'][$type]) {
                $attributes = 'rows="' . (int)$parameters['numberOfRows'] . '" wrap="off"' . $pObj->pObj->doc->formWidth(48, true, 'width:98%;height:60%');
                $title = $GLOBALS['LANG']->getLL('template') . ' ' . $parameters['tplRow']['title'] . $GLOBALS['LANG']->getLL('delimiter') . ' ' . $GLOBALS['LANG']->getLL($type);
                $outCode = $t3editor->getCodeEditor('data[' . $type . ']', 'text-monospace enable-tab', '$1', $attributes, $title, [
                    'pageId' => (int)$pObj->pObj->id
                ]);
                $parameters['theOutput'] = preg_replace('/\\<textarea name="data\\[' . $type . '\\]".*\\>([^\\<]*)\\<\\/textarea\\>/mi', $outCode, $parameters['theOutput']);
            }
        }
    }

    /**
     * Process saving request like in class.tstemplateinfo.php (TCE processing)
     *
     * @return bool TRUE if successful
     */
    public function save($parameters, $pObj)
    {
        $savingsuccess = false;
        if ($parameters['type'] == $this->ajaxSaveType) {
            $pageId = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pageId');
            if (!is_numeric($pageId) || $pageId < 1) {
                return false;
            }
            // If given use the requested template_uid
            // if not, use the first template-record on the page (in this case there should only be one record!)
            $set = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET');
            $template_uid = $set['templatesOnPage'] ?: 0;
            // Defined global here!
            $tmpl = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\ExtendedTemplateService::class);
            // Do not log time-performance information
            $tmpl->tt_track = 0;
            $tmpl->init();
            // Get the first template record on the page, which might be hidden as well
            // (for instance the TypoScript constant editor is persisting to the first template)
            $tplRow = $tmpl->ext_getFirstTemplate($pageId, $template_uid);
            $existTemplate = is_array($tplRow);
            if ($existTemplate) {
                $saveId = $tplRow['_ORIG_uid'] ?: $tplRow['uid'];
                // Update template ?
                $POST = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST();
                if ($POST['submit']) {
                    // Set the data to be saved
                    $recData = [];
                    if (is_array($POST['data'])) {
                        foreach ($POST['data'] as $field => $val) {
                            switch ($field) {
                                case 'constants':
                                case 'config':
                                    // Replace Windows- and Mac linebreaks
                                    $val = str_replace([CRLF, CR], LF, $val);
                                    $recData['sys_template'][$saveId][$field] = $val;
                                    break;
                            }
                        }
                    }
                    if (!empty($recData)) {
                        // process template row before saving
                        $tstemplateinfo = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController::class);
                        /* @var $tstemplateinfo \TYPO3\CMS\Tstemplate\Controller\TypoScriptTemplateInformationModuleFunctionController */
                        // load the MOD_SETTINGS in order to check if the includeTypoScriptFileContent is set
                        $tstemplateinfo->pObj = $pObj;
                        $tstemplateinfo->pObj->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData(['includeTypoScriptFileContent' => true], [], 'web_ts');
                        $recData['sys_template'][$saveId] = $tstemplateinfo->processTemplateRowBeforeSaving($recData['sys_template'][$saveId]);
                        // Create new tce-object
                        $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
                        $tce->stripslashes_values = 0;
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
