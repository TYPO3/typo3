<?php
namespace TYPO3\CMS\Backend\Form;

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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This is form engine - Class for creating the backend editing forms.
 *
 * @internal This class and its exposed method and method signatures will change
 */
class FormResultCompiler
{
    /**
     * @var string
     */
    protected $extJSCODE = '';

    /**
     * @var array HTML of additional hidden fields rendered by sub containers
     */
    protected $hiddenFieldAccum = [];

    /**
     * Can be set to point to a field name in the form which will be set to '1' when the form
     * is submitted with a *save* button. This way the recipient script can determine that
     * the form was submitted for save and not "close" for example.
     *
     * @var string
     */
    protected $doSaveFieldName = '';

    /**
     * @var array Data array from IRRE pushed to frontend as json array
     */
    protected $inlineData = [];

    /**
     * List of additional style sheet files to load
     *
     * @var array
     */
    protected $stylesheetFiles = [];

    /**
     * Additional JavaScript printed after the form
     *
     * @var array
     */
    protected $additionalJS_post = [];

    /**
     * Additional JavaScript executed on submit; If you set "OK" variable it will raise an error
     * about RTEs not being loaded and offer to block further submission.
     *
     * @var array
     */
    protected $additionalJS_submit = [];

    /**
     * Additional language label files to include.
     *
     * @var array
     */
    protected $additionalInlineLanguageLabelFiles = [];

    /**
     * Array with requireJS modules, use module name as key, the value could be callback code.
     * Use NULL as value if no callback is used.
     *
     * @var array
     */
    protected $requireJsModules = [];

    /**
     * @var PageRenderer
     */
    protected $pageRenderer = null;

    /**
     * Merge existing data with the given result array
     *
     * @param array $resultArray Array returned by child
     * @return void
     * @internal Temporary method to use FormEngine class as final data merger
     */
    public function mergeResult(array $resultArray)
    {
        $this->doSaveFieldName = $resultArray['doSaveFieldName'];
        foreach ($resultArray['additionalJavaScriptPost'] as $element) {
            $this->additionalJS_post[] = $element;
        }
        foreach ($resultArray['additionalJavaScriptSubmit'] as $element) {
            $this->additionalJS_submit[] = $element;
        }
        if (!empty($resultArray['requireJsModules'])) {
            foreach ($resultArray['requireJsModules'] as $module) {
                $moduleName = null;
                $callback = null;
                if (is_string($module)) {
                    // if $module is a string, no callback
                    $moduleName = $module;
                    $callback = null;
                } elseif (is_array($module)) {
                    // if $module is an array, callback is possible
                    foreach ($module as $key => $value) {
                        $moduleName = $key;
                        $callback = $value;
                        break;
                    }
                }
                if ($moduleName !== null) {
                    if (!empty($this->requireJsModules[$moduleName]) && $callback !== null) {
                        $existingValue = $this->requireJsModules[$moduleName];
                        if (!is_array($existingValue)) {
                            $existingValue = [$existingValue];
                        }
                        $existingValue[] = $callback;
                        $this->requireJsModules[$moduleName] = $existingValue;
                    } else {
                        $this->requireJsModules[$moduleName] = $callback;
                    }
                }
            }
        }
        $this->extJSCODE = $this->extJSCODE . LF . $resultArray['extJSCODE'];
        $this->inlineData = $resultArray['inlineData'];
        foreach ($resultArray['additionalHiddenFields'] as $element) {
            $this->hiddenFieldAccum[] = $element;
        }
        foreach ($resultArray['stylesheetFiles'] as $stylesheetFile) {
            if (!in_array($stylesheetFile, $this->stylesheetFiles)) {
                $this->stylesheetFiles[] = $stylesheetFile;
            }
        }

        if (!empty($resultArray['inlineData'])) {
            $resultArrayInlineData = $this->inlineData;
            $resultInlineData = $resultArray['inlineData'];
            ArrayUtility::mergeRecursiveWithOverrule($resultArrayInlineData, $resultInlineData);
            $this->inlineData = $resultArrayInlineData;
        }

        if (!empty($resultArray['additionalInlineLanguageLabelFiles'])) {
            foreach ($resultArray['additionalInlineLanguageLabelFiles'] as $additionalInlineLanguageLabelFile) {
                $this->additionalInlineLanguageLabelFiles[] = $additionalInlineLanguageLabelFile;
            }
        }
    }

    /**
     * JavaScript code added BEFORE the form is drawn:
     *
     * @return string A <script></script> section with JavaScript.
     */
    public function JStop()
    {
        $stylesheetHtml = [];
        foreach ($this->stylesheetFiles as $stylesheetFile) {
            $stylesheetHtml[] = '<link rel="stylesheet" type="text/css" href="' . $stylesheetFile . '" />';
        }
        return implode(LF, $stylesheetHtml);
    }

    /**
     * Prints necessary JavaScript for TCEforms (after the form HTML).
     * currently this is used to transform page-specific options in the TYPO3.Settings array for JS
     * so the JS module can access these values
     *
     * @return string
     */
    public function printNeededJSFunctions()
    {
        // set variables to be accessible for JS
        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->addInlineSetting('FormEngine', 'formName', 'editform');
        $pageRenderer->addInlineSetting('FormEngine', 'backPath', '');

        return $this->JSbottom('editform');
    }

    /**
     * JavaScript bottom code
     *
     * @param string $formname The identification of the form on the page.
     * @return string A section with JavaScript - if $update is FALSE, embedded in <script></script>
     */
    protected function JSbottom($formname = 'forms[0]')
    {
        $languageService = $this->getLanguageService();
        $jsFile = [];

        // @todo: this is messy here - "additional hidden fields" should be handled elsewhere
        $html = implode(LF, $this->hiddenFieldAccum);
        $backendRelPath = ExtensionManagementUtility::extRelPath('backend');
        $this->loadJavascriptLib($backendRelPath . 'Resources/Public/JavaScript/md5.js');
        // load the main module for FormEngine with all important JS functions
        $this->requireJsModules['TYPO3/CMS/Backend/FormEngine'] = 'function(FormEngine) {
			FormEngine.setBrowserUrl(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('wizard_element_browser')) . ');
		}';
        $this->requireJsModules['TYPO3/CMS/Backend/FormEngineValidation'] = 'function(FormEngineValidation) {
			FormEngineValidation.setUsMode(' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? '1' : '0') . ');
			FormEngineValidation.registerReady();
		}';

        $pageRenderer = $this->getPageRenderer();
        foreach ($this->requireJsModules as $moduleName => $callbacks) {
            if (!is_array($callbacks)) {
                $callbacks = [$callbacks];
            }
            foreach ($callbacks as $callback) {
                $pageRenderer->loadRequireJsModule($moduleName, $callback);
            }
        }
        $pageRenderer->loadJquery();
        $pageRenderer->loadExtJS();
        // Load tree stuff here
        $pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/tree.js');
        $pageRenderer->addInlineLanguageLabelFile(ExtensionManagementUtility::extPath('lang') . 'locallang_csh_corebe.xlf', 'tcatree');
        $pageRenderer->addJsFile($backendRelPath . 'Resources/Public/JavaScript/notifications.js');
        if (ExtensionManagementUtility::isLoaded('rtehtmlarea')) {
            // This js addition is hackish ... it will always load this file even if not RTE
            // is added here. But this simplifies RTE initialization a lot and is thus kept for now.
            $pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('rtehtmlarea') . 'Resources/Public/JavaScript/HTMLArea/NameSpace/NameSpace.js');
        }

        $beUserAuth = $this->getBackendUserAuthentication();
        // Make textareas resizable and flexible ("autogrow" in height)
        $textareaSettings = [
            'autosize'  => (bool)$beUserAuth->uc['resizeTextareas_Flexible']
        ];
        $pageRenderer->addInlineSettingArray('Textarea', $textareaSettings);

        $this->loadJavascriptLib($backendRelPath . 'Resources/Public/JavaScript/jsfunc.tbe_editor.js');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ValueSlider');
        // Needed for FormEngine manipulation (date picker)
        $dateFormat = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? ['MM-DD-YYYY', 'HH:mm MM-DD-YYYY'] : ['DD-MM-YYYY', 'HH:mm DD-MM-YYYY']);
        $pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);

        // support placeholders for IE9 and lower
        $clientInfo = GeneralUtility::clientInfo();
        if ($clientInfo['BROWSER'] == 'msie' && $clientInfo['VERSION'] <= 9) {
            $this->loadJavascriptLib('sysext/core/Resources/Public/JavaScript/Contrib/placeholders.min.js');
        }

        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileListLocalisation');

        $pageRenderer->addInlineLanguagelabelFile(
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('lang') . 'locallang_core.xlf',
            'file_upload'
        );
        if (!empty($this->additionalInlineLanguageLabelFiles)) {
            foreach ($this->additionalInlineLanguageLabelFiles as $additionalInlineLanguageLabelFile) {
                $pageRenderer->addInlineLanguageLabelFile($additionalInlineLanguageLabelFile);
            }
        }
        // Load codemirror for T3Editor
        if (ExtensionManagementUtility::isLoaded('t3editor')) {
            $this->loadJavascriptLib(ExtensionManagementUtility::extRelPath('t3editor') . 'Resources/Public/JavaScript/Contrib/codemirror/js/codemirror.js');
        }
        // We want to load jQuery-ui inside our js. Enable this using requirejs.
        $this->loadJavascriptLib($backendRelPath . 'Resources/Public/JavaScript/jsfunc.inline.js');
        $out = '
		inline.setNoTitleString(' . GeneralUtility::quoteJSvalue(BackendUtility::getNoRecordTitle(true)) . ');
		';

        $out .= '
		TBE_EDITOR.formname = "' . $formname . '";
		TBE_EDITOR.formnameUENC = "' . rawurlencode($formname) . '";
		TBE_EDITOR.backPath = "";
		TBE_EDITOR.isPalettedoc = null;
		TBE_EDITOR.doSaveFieldName = "' . ($this->doSaveFieldName ? addslashes($this->doSaveFieldName) : '') . '";
		TBE_EDITOR.labels.fieldsChanged = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.fieldsChanged')) . ';
		TBE_EDITOR.labels.fieldsMissing = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.fieldsMissing')) . ';
		TBE_EDITOR.labels.maxItemsAllowed = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.maxItemsAllowed')) . ';
		TBE_EDITOR.labels.refresh_login = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login')) . ';
		TBE_EDITOR.labels.refreshRequired = {};
		TBE_EDITOR.labels.refreshRequired.title = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:mess.refreshRequired.title')) . ';
		TBE_EDITOR.labels.refreshRequired.content = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:mess.refreshRequired.content')) . ';
		TBE_EDITOR.labels.remainingCharacters = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.remainingCharacters')) . ';
		TBE_EDITOR.customEvalFunctions = {};
		';

        // Add JS required for inline fields
        if (!empty($this->inlineData)) {
            $out .= '
			inline.addToDataArray(' . json_encode($this->inlineData) . ');
			';
        }
        // $this->additionalJS_submit:
        if ($this->additionalJS_submit) {
            $additionalJS_submit = implode('', $this->additionalJS_submit);
            $additionalJS_submit = str_replace([CR, LF], '', $additionalJS_submit);
            $out .= '
			TBE_EDITOR.addActionChecks("submit", "' . addslashes($additionalJS_submit) . '");
			';
        }
        $out .= LF . implode(LF, $this->additionalJS_post) . LF . $this->extJSCODE;

        $spacer = LF . TAB;
        $out = $html . $spacer . implode($spacer, $jsFile) . GeneralUtility::wrapJS($out);

        return $out;
    }

    /**
     * Includes a javascript library that exists in the core /typo3/ directory. The
     * backpath is automatically applied.
     *
     * @param string $lib Library name. Call it with the full path like "sysext/core/Resources/Public/JavaScript/QueryGenerator.js" to load it
     * @return void
     */
    protected function loadJavascriptLib($lib)
    {
        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->addJsFile($lib);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Wrapper for access to the current page renderer object
     *
     * @return \TYPO3\CMS\Core\Page\PageRenderer
     */
    protected function getPageRenderer()
    {
        if ($this->pageRenderer === null) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }
        return $this->pageRenderer;
    }
}
