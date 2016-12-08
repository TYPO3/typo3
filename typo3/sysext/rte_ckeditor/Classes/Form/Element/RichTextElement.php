<?php
declare(strict_types=1);
namespace TYPO3\CMS\RteCKEditor\Form\Element;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Render rich text editor in FormEngine
 */
class RichTextElement extends AbstractFormElement
{

    /**
     * pid of fixed versioned record.
     * This is the pid of the record in normal cases, but is changed to the pid
     * of the "mother" record in case the handled record is a versioned overlay
     * and "mother" is located at a different pid.
     *
     * @var int
     */
    protected $pidOfVersionedMotherRecord;

    /**
     * RTE configuration
     * This property contains "processed" configuration
     * where table and type specific RTE setup is merged into 'default.' array.
     *
     * @var array
     */
    protected $rteConfiguration = [];

    /**
     * Renders the ckeditor element
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function render() : array
    {
        $resultArray = $this->initializeResultArray();
        $row = $this->data['databaseRow'];
        BackendUtility::fixVersioningPid($this->data['tableName'], $row);
        $this->pidOfVersionedMotherRecord = (int)$row['pid'];

        $resourcesPath = PathUtility::getAbsoluteWebPath(
            ExtensionManagementUtility::extPath('rte_ckeditor', 'Resources/Public/')
        );
        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $defaultExtras = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
        BackendUtility::fixVersioningPid($table, $row);

        $fieldId = $this->sanitizeFieldId($parameterArray['itemFormElName']);
        $resultArray['html'] = $this->renderWizards(
            [$this->getHtml($fieldId)],
            $parameterArray['fieldConf']['config']['wizards'],
            $table,
            $row,
            $this->data['fieldName'],
            $parameterArray,
            $parameterArray['itemFormElName'],
            $defaultExtras,
            true
        );

        $vanillaRteTsConfig = $this->getBackendUserAuthentication()->getTSConfig('RTE', BackendUtility::getPagesTSconfig($this->data['effectivePid']));
        $this->rteConfiguration = BackendUtility::RTEsetup(
            $vanillaRteTsConfig['properties'],
            $table,
            $this->data['fieldName'],
            $this->data['recordTypeValue']
        );

        $resultArray['requireJsModules'] = [];
        $resultArray['requireJsModules'][] =[
            'ckeditor' => $this->getCkEditorRequireJsModuleCode($resourcesPath, $fieldId)
        ];

        return $resultArray;
    }

    /**
     * Gets the JavaScript code for ckeditor module
     *
     * @param string $resourcesPath
     * @param string $fieldId
     * @return string
     */
    protected function getCkEditorRequireJsModuleCode(string $resourcesPath, string $fieldId) : string
    {
        // todo: find new name for this option (do we still need this?)
        // Initializing additional attributes
        $additionalAttributes = [];
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes']) {
            $additionalAttributes = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes'], true);
        }

        $customConfig = [
            'contentsCss' => $resourcesPath . 'Css/contents.css',
            'customConfig' => $resourcesPath . 'JavaScript/defaultconfig.js',
            'toolbar' => 'Basic',
            'uiColor' => '#F8F8F8',
            'stylesSet' => 'default',
            'RTEtsConfigParams' => $this->getRTEtsConfigParams(),
            'elementbrowser' => [
                'link' => [
                    'moduleUrl' => BackendUtility::getModuleUrl('rteckeditor_wizard_browse_links'),
                    'additionalAttributes' => $additionalAttributes
                ]
            ]
        ];

        return 'function(CKEDITOR) {
                CKEDITOR.config.height = 400;
                CKEDITOR.contentsCss = "' . $resourcesPath . 'Css/contents.css";
                CKEDITOR.config.width = "auto";
                CKEDITOR.replace("' . $fieldId . '", ' . json_encode($customConfig) . ');
        }';
    }

    /**
     * Create <textarea> element
     *
     * @param string $fieldId
     * @return string Main HTML to render
     */
    protected function getHtml(string $fieldId) : string
    {
        $itemFormElementName = $this->data['parameterArray']['itemFormElName'];
        $value = $this->data['parameterArray']['itemFormElValue'] ?? '';

        return '<textarea ' . $this->getValidationDataAsDataAttribute($this->data['parameterArray']['fieldConf']['config']) . ' id="' . $fieldId . '" name="' . htmlspecialchars($itemFormElementName) . '">' . htmlspecialchars($value) . '</textarea>';
    }

    /**
     * A list of parameters that is mostly given as GET/POST to other RTE controllers.
     *
     * @return string
     */
    protected function getRTEtsConfigParams() : string
    {
        $result = [
            $this->data['tableName'],
            $this->data['databaseRow']['uid'],
            $this->data['fieldName'],
            $this->pidOfVersionedMotherRecord,
            $this->data['recordTypeValue'],
            $this->data['effectivePid'],
        ];
        return implode(':', $result);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService() : LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication() : BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @param string $itemFormElementName
     * @return string
     */
    protected function sanitizeFieldId(string $itemFormElementName) : string
    {
        $fieldId = preg_replace('/[^a-zA-Z0-9_:.-]/', '_', $itemFormElementName);
        return htmlspecialchars(preg_replace('/^[^a-zA-Z]/', 'x', $fieldId));
    }
}
