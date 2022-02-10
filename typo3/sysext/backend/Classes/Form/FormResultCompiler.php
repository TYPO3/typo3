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

namespace TYPO3\CMS\Backend\Form;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This is form engine - Class for creating the backend editing forms.
 *
 * @internal This class and its exposed method and method signatures will change
 */
class FormResultCompiler
{
    use FormResultTrait;

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
    protected $additionalJavaScriptPost = [];

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
     * @var list<JavaScriptModuleInstruction>
     */
    protected $requireJsModules = [];

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * Merge existing data with the given result array
     *
     * @param array $resultArray Array returned by child
     * @internal Temporary method to use FormEngine class as final data merger
     */
    public function mergeResult(array $resultArray)
    {
        $this->doSaveFieldName = $resultArray['doSaveFieldName'] ?? '';
        // @todo deprecate inline JavaScript in TYPO3 v12.0
        foreach ($resultArray['additionalJavaScriptPost'] as $element) {
            $this->additionalJavaScriptPost[] = $element;
        }
        foreach ($resultArray['requireJsModules'] ?? [] as $module) {
            if (!$module instanceof JavaScriptModuleInstruction) {
                throw new \LogicException(
                    sprintf(
                        'Module must be a %s, type "%s" given',
                        JavaScriptModuleInstruction::class,
                        gettype($module)
                    ),
                    1638264590
                );
            }
            $this->requireJsModules[] = $module;
        }
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
     * Adds CSS files BEFORE the form is drawn
     *
     * @return string
     */
    public function addCssFiles()
    {
        $pageRenderer = $this->getPageRenderer();
        foreach ($this->stylesheetFiles as $stylesheetFile) {
            $pageRenderer->addCssFile($stylesheetFile);
        }
        return '';
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

        return $this->JSbottom();
    }

    /**
     * JavaScript bottom code
     *
     * @return string A section with JavaScript - if $update is FALSE, embedded in <script></script>
     */
    protected function JSbottom()
    {
        $pageRenderer = $this->getPageRenderer();
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        // @todo: this is messy here - "additional hidden fields" should be handled elsewhere
        $html = implode(LF, $this->hiddenFieldAccum);
        // load the main module for FormEngine with all important JS functions
        $this->requireJsModules[] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine.js')
                ->invoke(
                    'initialize',
                    (string)$uriBuilder->buildUriFromRoute('wizard_element_browser')
                );
        $this->requireJsModules[] = JavaScriptModuleInstruction::create('@typo3/backend/form-engine-review.js');

        foreach ($this->requireJsModules as $module) {
            $pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction($module);
        }

        // Needed for FormEngine manipulation (date picker)
        $dateFormat = ['DD-MM-Y', 'HH:mm DD-MM-Y'];
        $pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);

        $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_upload');
        if (!empty($this->additionalInlineLanguageLabelFiles)) {
            foreach ($this->additionalInlineLanguageLabelFiles as $additionalInlineLanguageLabelFile) {
                $pageRenderer->addInlineLanguageLabelFile($additionalInlineLanguageLabelFile);
            }
        }

        $pageRenderer->loadJavaScriptModule('@typo3/backend/form-engine/request-update.js');

        // todo: change these things in JS
        $pageRenderer->addInlineLanguageLabelArray([
            'FormEngine.noRecordTitle'          => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.no_title'),
            'FormEngine.fieldsChanged'          => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.fieldsChanged'),
            'FormEngine.fieldsMissing'          => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.fieldsMissing'),
            'FormEngine.maxItemsAllowed'        => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.maxItemsAllowed'),
            'FormEngine.refreshRequiredTitle'   => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.refreshRequired.title'),
            'FormEngine.refreshRequiredContent' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.refreshRequired.content'),
            'FormEngine.remainingCharacters'    => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remainingCharacters'),
        ]);

        // Add JS required for inline fields
        if (!empty($this->inlineData)) {
            $pageRenderer->addInlineSettingArray('FormEngineInline', $this->inlineData);
        }
        // @todo deprecate inline JavaScript in TYPO3 v12.0
        $out = LF . implode(LF, $this->additionalJavaScriptPost);

        return $html . LF . "\t" . GeneralUtility::wrapJS($out);
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
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
