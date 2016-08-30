<?php
namespace TYPO3\CMS\Form\View\Wizard;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Repository\ContentRepository;

/**
 * The form wizard view
 */
class WizardView
{
    /**
     * Is the referenced record available
     *
     * @var bool TRUE if available, FALSE if not
     */
    protected $recordIsAvailable = false;

    /**
     * @var ContentRepository
     */
    protected $repository;

    /**
     * The document template object
     *
     * Needs to be a local variable of the class.
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public $doc;

    /**
     * @var PageRenderer
     */
    protected $pageRenderer;

    /**
     * Constructs this view
     *
     * Defines the global variable SOBE. Normally this is used by the wizards
     * which are one file only. This view is now the class with the global
     * variable name SOBE.
     *
     * Defines the document template object.
     *
     * @param ContentRepository $repository
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    public function __construct(ContentRepository $repository)
    {
        $this->setRepository($repository);
        $this->getLanguageService()->includeLLFile('EXT:form/Resources/Private/Language/locallang_wizard.xlf');
        $GLOBALS['SOBE'] = $this;
        // Define the document template object
        $this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
        $this->doc->setModuleTemplate('EXT:form/Resources/Private/Templates/Wizard.html');
    }

    /**
     * Sets the content repository to be used.
     *
     * @param ContentRepository $repository
     * @return void
     */
    public function setRepository(ContentRepository $repository)
    {
        $this->repository = $repository;
    }
    /**
     * The main render method
     *
     * Gathers all content and returns it
     *
     * @return string
     */
    public function render()
    {
        $docHeaderButtons = [];
        // Check if the referenced record is available
        $this->recordIsAvailable = $this->repository->hasRecord();
        if ($this->recordIsAvailable) {
            // Load necessary JavaScript
            $this->loadJavascript();
            // Load necessary CSS
            $this->loadCss();
            // Load the settings
            $this->loadSettings();
            // Localization
            $this->loadLocalization();
            // Setting up the buttons and markers for docheader
            $docHeaderButtons = $this->getButtons();
            $markers['CSH'] = $docHeaderButtons['csh'];
            // Hook
            $this->callRenderHook();
        }
        // Getting the body content
        $markers['CONTENT'] = $this->getBodyContent();
        // Build the HTML for the module
        $content = $this->doc->startPage($this->getLanguageService()->getLL('title', true));
        $content .= $this->doc->moduleBody([], $docHeaderButtons, $markers);
        $content .= $this->doc->endPage();
        return $this->doc->insertStylesAndJS($content);
    }

    /**
     * Load the necessary javascript
     *
     * This will only be done when the referenced record is available
     *
     * @return void
     */
    protected function loadJavascript()
    {
        $compress = true;
        $javascriptFiles = [
            'Initialize.js',
            'Ux/Ext.ux.merge.js',
            'Ux/Ext.ux.isemptyobject.js',
            'Ux/Ext.ux.spinner.js',
            'Ux/Ext.ux.form.spinnerfield.js',
            'Ux/Ext.ux.form.textfieldsubmit.js',
            'Ux/Ext.ux.form.ValueCheckbox.js', // required by Viewport/Left/Options/Forms/Attributes.js
            'Ux/Ext.ux.grid.CheckColumn.js',
            'Ux/Ext.ux.grid.SingleSelectCheckColumn.js',
            'Ux/Ext.ux.grid.ItemDeleter.js',
            'Helpers/History.js',
            'Helpers/Element.js',
            'Elements/ButtonGroup.js',
            'Elements/Container.js',
            'Elements/Elements.js',
            'Elements/Dummy.js',
            'Elements/Basic/Button.js',
            'Elements/Basic/Checkbox.js',
            'Elements/Basic/Fieldset.js',
            'Elements/Basic/Fileupload.js',
            'Elements/Basic/Form.js',
            'Elements/Basic/Hidden.js',
            'Elements/Basic/Password.js',
            'Elements/Basic/Radio.js',
            'Elements/Basic/Reset.js',
            'Elements/Basic/Select.js',
            'Elements/Basic/Submit.js',
            'Elements/Basic/Textarea.js',
            'Elements/Basic/Textline.js',
            'Elements/Predefined/Email.js',
            'Elements/Predefined/CheckboxGroup.js',
            'Elements/Predefined/Name.js',
            'Elements/Predefined/RadioGroup.js',
            'Elements/Content/Header.js',
            'Elements/Content/Textblock.js',
            'Viewport.js',
            'Viewport/Left.js',
            'Viewport/Right.js',
            'Viewport/Left/Elements.js',
            'Viewport/Left/Elements/ButtonGroup.js',
            'Viewport/Left/Elements/Basic.js',
            'Viewport/Left/Elements/Predefined.js',
            'Viewport/Left/Elements/Content.js',
            'Viewport/Left/Options.js',
            'Viewport/Left/Options/Dummy.js',
            'Viewport/Left/Options/Panel.js',
            'Viewport/Left/Options/Forms/Attributes.js',
            'Viewport/Left/Options/Forms/Label.js',
            'Viewport/Left/Options/Forms/Legend.js',
            'Viewport/Left/Options/Forms/Options.js',
            'Viewport/Left/Options/Forms/Various.js',
            'Viewport/Left/Options/Forms/Filters.js',
            'Viewport/Left/Options/Forms/Filters/Filter.js',
            'Viewport/Left/Options/Forms/Filters/Dummy.js',
            'Viewport/Left/Options/Forms/Filters/Alphabetic.js',
            'Viewport/Left/Options/Forms/Filters/Alphanumeric.js',
            'Viewport/Left/Options/Forms/Filters/Currency.js',
            'Viewport/Left/Options/Forms/Filters/Digit.js',
            'Viewport/Left/Options/Forms/Filters/Integer.js',
            'Viewport/Left/Options/Forms/Filters/LowerCase.js',
            'Viewport/Left/Options/Forms/Filters/RegExp.js',
            'Viewport/Left/Options/Forms/Filters/RemoveXSS.js',
            'Viewport/Left/Options/Forms/Filters/StripNewLines.js',
            'Viewport/Left/Options/Forms/Filters/TitleCase.js',
            'Viewport/Left/Options/Forms/Filters/Trim.js',
            'Viewport/Left/Options/Forms/Filters/UpperCase.js',
            'Viewport/Left/Options/Forms/Validation.js',
            'Viewport/Left/Options/Forms/Validation/Rule.js',
            'Viewport/Left/Options/Forms/Validation/Dummy.js',
            'Viewport/Left/Options/Forms/Validation/Alphabetic.js',
            'Viewport/Left/Options/Forms/Validation/Alphanumeric.js',
            'Viewport/Left/Options/Forms/Validation/Between.js',
            'Viewport/Left/Options/Forms/Validation/Date.js',
            'Viewport/Left/Options/Forms/Validation/Digit.js',
            'Viewport/Left/Options/Forms/Validation/Email.js',
            'Viewport/Left/Options/Forms/Validation/Equals.js',
            'Viewport/Left/Options/Forms/Validation/FileAllowedTypes.js',
            'Viewport/Left/Options/Forms/Validation/FileMaximumSize.js',
            'Viewport/Left/Options/Forms/Validation/FileMinimumSize.js',
            'Viewport/Left/Options/Forms/Validation/Float.js',
            'Viewport/Left/Options/Forms/Validation/GreaterThan.js',
            'Viewport/Left/Options/Forms/Validation/InArray.js',
            'Viewport/Left/Options/Forms/Validation/Integer.js',
            'Viewport/Left/Options/Forms/Validation/Ip.js',
            'Viewport/Left/Options/Forms/Validation/Length.js',
            'Viewport/Left/Options/Forms/Validation/LessThan.js',
            'Viewport/Left/Options/Forms/Validation/RegExp.js',
            'Viewport/Left/Options/Forms/Validation/Required.js',
            'Viewport/Left/Options/Forms/Validation/Uri.js',
            'Viewport/Left/Form.js',
            'Viewport/Left/Form/Behaviour.js',
            'Viewport/Left/Form/Attributes.js',
            'Viewport/Left/Form/Prefix.js',
            'Viewport/Left/Form/PostProcessor.js',
            'Viewport/Left/Form/PostProcessors/PostProcessor.js',
            'Viewport/Left/Form/PostProcessors/Dummy.js',
            'Viewport/Left/Form/PostProcessors/Mail.js',
            'Viewport/Left/Form/PostProcessors/Redirect.js'
        ];
        // Load ExtJS
        $this->getPageRenderer()->loadExtJS();
        // Load the wizards javascript
        $baseUrl = ExtensionManagementUtility::extRelPath('form') . 'Resources/Public/JavaScript/Wizard/';
        foreach ($javascriptFiles as $javascriptFile) {
            $this->getPageRenderer()->addJsFile($baseUrl . $javascriptFile, 'text/javascript', $compress, false);
        }
    }

    /**
     * Load the necessary css
     *
     * This will only be done when the referenced record is available
     *
     * @return void
     */
    protected function loadCss()
    {
        // @todo Set to TRUE when finished
        $compress = false;
        $cssFiles = [
            'Wizard/Form.css',
            'Wizard/Wizard.css'
        ];
        $baseUrl = ExtensionManagementUtility::extRelPath('form') . 'Resources/Public/CSS/';
        // Load the wizards css
        foreach ($cssFiles as $cssFile) {
            $this->getPageRenderer()->addCssFile($baseUrl . $cssFile, 'stylesheet', 'all', '', $compress, false);
        }
    }

    /**
     * Load the settings
     *
     * The settings are defined in pageTSconfig mod.wizards.form
     *
     * @return void
     */
    protected function loadSettings()
    {
        $record = $this->repository->getRecord();
        $pageId = $record->getPageId();
        $modTSconfig = BackendUtility::getModTSconfig($pageId, 'mod.wizards.form');
        $settings = $modTSconfig['properties'];
        $this->removeTrailingDotsFromTyposcript($settings);
        $this->doc->JScode .= $this->doc->wrapScriptTags('TYPO3.Form.Wizard.Settings = ' . json_encode($settings) . ';');
    }

    /**
     * Reads locallang file into array (for possible include in header)
     *
     * @return void
     */
    protected function loadLocalization()
    {
        $wizardLabels = $this->getLanguageService()->includeLLFile('EXT:form/Resources/Private/Language/locallang_wizard.xlf', false, true);
        $controllerLabels = $this->getLanguageService()->includeLLFile('EXT:form/Resources/Private/Language/locallang.xlf', false, true);
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($controllerLabels, $wizardLabels);
        $this->getPageRenderer()->addInlineLanguageLabelArray($controllerLabels['default']);
    }

    /**
     * Hook to extend the wizard interface.
     *
     * The hook is called just before content rendering. Use it by adding your function to the array
     * $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['form']['hooks']['renderWizard']
     *
     * @return void
     */
    protected function callRenderHook()
    {
        $params = [];
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['form']['hooks']['renderWizard'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['form']['hooks']['renderWizard'] as $funcRef) {
                GeneralUtility::callUserFunction($funcRef, $params, $this);
            }
        }
    }

    /**
     * Remove the trailing dots from the values in Typoscript
     *
     * @param array $array The array with the trailing dots
     * @return void
     */
    protected function removeTrailingDotsFromTyposcript(array &$array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $this->removeTrailingDotsFromTyposcript($value);
            }
            if (substr($key, -1) === '.') {
                $newKey = substr($key, 0, -1);
                unset($array[$key]);
                $array[$newKey] = $value;
            }
        }
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform
     * operations.
     *
     * @return array all available buttons as an assoc. array
     */
    protected function getButtons()
    {
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $buttons = [
            'csh' => '',
            'csh_buttons' => '',
            'close' => '',
            'save' => '',
            'save_close' => '',
            'reload' => ''
        ];
        // CSH
        $buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_forms_wiz');
        // CSH Buttons
        $buttons['csh_buttons'] = BackendUtility::cshItem('xMOD_csh_corebe', 'wizard_forms_wiz_buttons');
        // Close
        $getPostVariables = GeneralUtility::_GP('P');
        $title = 'title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', true) . '"';
        $onClick = htmlspecialchars('jumpToUrl(unescape(\'' .
            rawurlencode(GeneralUtility::sanitizeLocalUrl($getPostVariables['returnUrl'])) . '\')); return false;');
        $buttons['close'] = '<a href="#" onclick="' . $onClick . '" ' . $title . '>' .
            $iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL)->render() . '</a>';
        return $buttons;
    }

    /**
     * Generate the body content
     *
     * If there is an error, no reference to a record, a Flash Message will be
     * displayed
     *
     * @return string The body content
     */
    protected function getBodyContent()
    {
        if (!$this->recordIsAvailable) {
            /** @var $flashMessage FlashMessage */
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class,
                $this->getLanguageService()->getLL('errorMessage', true),
                $this->getLanguageService()->getLL('errorTitle', true),
                FlashMessage::ERROR);
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        return '';
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        if (!isset($this->pageRenderer)) {
            $this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        }

        return $this->pageRenderer;
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
