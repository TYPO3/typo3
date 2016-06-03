<?php
namespace TYPO3\CMS\Form\View\Wizard\Element;

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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Extbase\Service\TypoScriptService;
use TYPO3\CMS\Form\Domain\Repository\ContentRepository;
use TYPO3\CMS\Form\Utility\TypoScriptToJsonConverter;

/**
 * form Wizard widget
 */
class FormWizardElement extends AbstractFormElement
{
    /**
     * @var array
     */
    protected $resultArray;

    /**
     * Store initialized resultArray
     */
    protected function initializeResultArray()
    {
        $this->resultArray = parent::initializeResultArray();
    }

    /**
     * @return int
     */
    protected function getCurrentPageId()
    {
        // $this->data used to be globalOptions
        return (int)$this->data['inlineFirstPid'];
    }

    /**
     * @return int
     */
    protected function getCurrentUid()
    {
        return (int)$this->data['databaseRow']['uid'];
    }

    /**
     * @return array
     */
    protected function getPlainPageWizardModTsConfigSettingsProperties()
    {
        $settings = $this->data['pageTsConfig']['mod.']['wizards.']['form.'];
        return $this->getTypoScriptService()->convertTypoScriptArrayToPlainArray($settings);
    }

    /**
     * Gets the repository object.
     *
     * @return ContentRepository
     */
    protected function getRepository()
    {
        return GeneralUtility::makeInstance(ContentRepository::class);
    }

    /**
     * Read and convert the content record to JSON
     *
     * @see \TYPO3\CMS\Form\Domain\Repository\ContentRepository::getRecordAsJson
     * @return TYPO3\CMS\Form\Domain\Model\Json\FormJsonElement|false The JSON object if record exists, FALSE if not
     */
    protected function getRecordAsJson()
    {
        $json = false;
        $record = $this->getRepository()->getRecord($this->getCurrentUid(), 'tt_content');
        if ($record) {
            $typoscript = $record->getTyposcript();
            /** @var $converter TypoScriptToJsonConverter */
            $converter = GeneralUtility::makeInstance(TypoScriptToJsonConverter::class);
            $json = $converter->convert($typoscript);
        }
        return $json;
    }

    /**
     * @return string
     */
    protected function getAjaxUrl()
    {
        /**
         * @see TYPO3.CMS/src/typo3/sysext/backend/Classes/Form/Element/AbstractFormElement.php:267 for wizard type=popup
         */
        $parameterArray = $this->data['parameterArray'];
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $itemName = $parameterArray['itemFormElName'];
        // Resolving script filename and setting URL.

        $params = array();
        $params['fieldConfig'] = $parameterArray['fieldConf'];
        $params['table'] = $table;
        $params['uid'] = $row['uid'];
        $params['pid'] = $row['pid'];
        $params['field'] = $fieldName;

        $params['formName'] = 'editform';
        $params['itemName'] = $itemName;
        $params['hmac'] = GeneralUtility::hmac($params['formName'] . $params['itemName'], 'wizard_js');

        return GeneralUtility::implodeArrayForUrl('', array('P' => $params));
    }

    /**
     * locallang files for return array
     *
     * @return array strings
     */
    protected function getLocalization()
    {
        $wizardLabels = 'EXT:form/Resources/Private/Language/locallang_wizard.xlf';
        $controllerLabels = 'EXT:form/Resources/Private/Language/locallang.xlf';
        return [$controllerLabels, $wizardLabels];
    }

    /**
     * @param array $settings
     * @return string
     */
    protected function resultAddWizardSettingsJson(array $settings)
    {
        $recordJson = $this->getRecordAsJson();
        $settings['Configuration'] = $recordJson;
        $settings['ajaxUrl'] = $this->getAjaxUrl();
        $settingsCommand = 'TYPO3.Form.Wizard.Settings = ' . json_encode($settings) . ';';
        // enhance global variable for requireJs shim "exports: 'TYPO3.Form.Wizard.Settings'" to work
        $this->resultArray['additionalJavaScriptPost'][] = ';TYPO3.Form = TYPO3.Form || {Wizard:{}};';
        $this->resultArray['additionalJavaScriptPost'][] =
            ';define("TYPO3/CMS/Form/Wizard/Settings", function() {'
            . "\n" . '	TYPO3.Form.Wizard.Settings = ' . json_encode($settings) . ';'
            . "\n" . '	return TYPO3.Form.Wizard.Settings;'
            . "\n" . '});';
        return $settingsCommand;
    }

    /**
     * @see \TYPO3\CMS\Form\View\Wizard\WizardView::loadCss
     */
    protected function resultAddWizardCss()
    {
        $cssFiles = array(
            'form.css'
        );
        $baseUrl = ExtensionManagementUtility::extRelPath('form') . 'Resources/Public/Css/';
        // Load the wizards css
        foreach ($cssFiles as $cssFile) {
            $this->resultArray['stylesheetFiles'][] = $baseUrl . $cssFile;
        }
    }

    /**
     * @return array
     */
    public function render()
    {
        $this->initializeResultArray();

        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $beUser */
        $beUser = $GLOBALS['BE_USER'];
        /** @var string $showWizardByDefault */
        $showWizardByDefault = $beUser->getTSConfigVal('setup.default.tx_form.showWizardByDefault');
        if ((int)$showWizardByDefault == 0) {
            $content = '';
            $record = $this->getRepository()->getRecord($this->getCurrentUid(), 'tt_content');
            if ($record) {
                $content = $record->getBodytext();
            }

            $id = StringUtility::getUniqueId('formengine-textarea-');
            $this->resultArray['html'] = '<textarea id="formengine-textarea-' . $id . '"'
            . ' class="form-control t3js-formengine-textarea formengine-textarea" wrap="off"'
            . ' onchange="TBE_EDITOR.fieldChanged(\'tt_content\',\'' . $this->getCurrentUid() . '\',\'bodytext\',\'data[tt_content][' . $this->getCurrentUid() . '][bodytext]\');"'
            . ' rows="15" style="" name="data[tt_content][' . $this->getCurrentUid() . '][bodytext]">' . $content . '</textarea>';
            return $this->resultArray;
        }

        $this->resultAddWizardCss();
        $this->resultArray['additionalInlineLanguageLabelFiles'] += $this->getLocalization();
        $settings = $this->getPlainPageWizardModTsConfigSettingsProperties();
        $settingsCommand = $this->resultAddWizardSettingsJson($settings);
        $this->resultArray['requireJsModules'][] = array(
            'TYPO3/CMS/Form/Wizard' => "function(){\n"
                //. "\t" . 'console.log(this, arguments);' . "\n"
                . "\t" . $settingsCommand . "\n"
                . '}'
        );
        $attributes = [];
        $attributes['id'] = StringUtility::getUniqueId('formengine-form-wizard-');
        /**
         * @see TYPO3.CMS/src/typo3/sysext/backend/Classes/Form/Element/AbstractFormElement.php:267 for wizard type=popup
         */
        $parameterArray = $this->data['parameterArray'];
        $attributes['name'] = $parameterArray['itemFormElName'];

        $attributeString = '';
        foreach ($attributes as $attributeName => $attributeValue) {
            $attributeString .= ' ' . $attributeName . '="' . htmlspecialchars($attributeValue) . '"';
        }

        $input = '<input ' . $attributeString . ' type="hidden" />' . "\n";
        $content = $input . '<div id="form-wizard-element"></div>';
        $this->resultArray['html'] = '<div id="form-wizard-element-container" rel="' . $attributes['id'] . '">'
            . "\n" . $content
            . "\n" . '</div>';
        return $this->resultArray;
    }

    /**
     * @return TypoScriptService
     */
    protected function getTypoScriptService()
    {
        return GeneralUtility::makeInstance(TypoScriptService::class);
    }
}
