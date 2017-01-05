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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\Locales;
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
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageContent',
            ],
        ],
    ];

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
        $this->pidOfVersionedMotherRecord = (int)$row['pid'];

        $resourcesPath = PathUtility::getAbsoluteWebPath(
            ExtensionManagementUtility::extPath('rte_ckeditor', 'Resources/Public/')
        );
        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        $fieldId = $this->sanitizeFieldId($parameterArray['itemFormElName']);
        $itemFormElementName = $this->data['parameterArray']['itemFormElName'];

        $value = $this->data['parameterArray']['itemFormElValue'] ?? '';

        $legacyWizards = $this->renderWizards();
        $legacyFieldControlHtml = implode(LF, $legacyWizards['fieldControl']);
        $legacyFieldWizardHtml = implode(LF, $legacyWizards['fieldWizard']);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $legacyFieldControlHtml . $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $legacyFieldWizardHtml . $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $attributes = [
            'style' => 'display:none',
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'id' => $fieldId,
            'name' => htmlspecialchars($itemFormElementName),
        ];

        $html = [];
        $html[] = '<div class="t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-control-wrap">';
        $html[] =       '<div class="form-wizards-wrap">';
        $html[] =           '<div class="form-wizards-element">';
        $html[] =               '<textarea ' . GeneralUtility::implodeAttributes($attributes, true) . '>';
        $html[] =                   htmlspecialchars($value);
        $html[] =               '</textarea>';
        $html[] =           '</div>';
        $html[] =           '<div class="form-wizards-items-aside">';
        $html[] =               '<div class="btn-group">';
        $html[] =                   $fieldControlHtml;
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<div class="form-wizards-items-bottom">';
        $html[] =               $fieldWizardHtml;
        $html[] =           '</div>';
        $html[] =       '</div>';
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['html'] = implode(LF, $html);

        $this->rteConfiguration = $parameterArray['fieldConf']['config']['richtextConfiguration'];

        $resultArray['requireJsModules'] = [];
        $resultArray['requireJsModules'][] =[
            'ckeditor' => $this->getCkEditorRequireJsModuleCode($resourcesPath, $fieldId)
        ];

        return $resultArray;
    }

    /**
     * Determine the contents language iso code
     *
     * @return string
     */
    protected function getContentsLanguage()
    {
        $language = $this->getLanguageService()->lang;
        if ($language === 'default' || !$language) {
            $language = 'en';
        }
        $currentLanguageUid = $this->data['databaseRow']['sys_language_uid'];
        if (is_array($currentLanguageUid)) {
            $currentLanguageUid = $currentLanguageUid[0];
        }
        $contentLanguageUid = (int)max($currentLanguageUid, 0);
        if ($contentLanguageUid) {
            $contentISOLanguage = $language;
            if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
                $tableA = 'sys_language';
                $tableB = 'static_languages';

                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tableA);

                $result = $queryBuilder
                    ->select('a.uid', 'b.lg_iso_2', 'b.lg_country_iso_2')
                    ->from($tableA, 'a')
                    ->where('a.uid', (int)$contentLanguageUid)
                    ->leftJoin(
                        'a',
                        $tableB,
                        'b',
                        $queryBuilder->expr()->eq('a.static_lang_isocode', $queryBuilder->quoteIdentifier('b.uid'))
                    )
                    ->execute();

                while ($languageRow = $result->fetch()) {
                    $contentISOLanguage = strtolower(trim($languageRow['lg_iso_2']) . (trim($languageRow['lg_country_iso_2']) ? '_' . trim($languageRow['lg_country_iso_2']) : ''));
                }
            }
        } else {
            $contentISOLanguage = trim($this->rteConfiguration['defaultContentLanguage'] ?? '') ?: 'en';
            $languageCodeParts = explode('_', $contentISOLanguage);
            $contentISOLanguage = strtolower($languageCodeParts[0]) . ($languageCodeParts[1] ? '_' . strtoupper($languageCodeParts[1]) : '');
            // Find the configured language in the list of localization locales
            /** @var $locales Locales */
            $locales = GeneralUtility::makeInstance(Locales::class);
            // If not found, default to 'en'
            if (!in_array($contentISOLanguage, $locales->getLocales(), true)) {
                $contentISOLanguage = 'en';
            }
        }
        return $contentISOLanguage;
    }

    /**
     * Gets the JavaScript code for CKEditor module
     *
     * @param string $resourcesPath
     * @param string $fieldId
     * @return string
     */
    protected function getCkEditorRequireJsModuleCode(string $resourcesPath, string $fieldId) : string
    {
        $customConfig = [
            'contentsCss' => $resourcesPath . 'Css/contents.css',
            'customConfig' => $resourcesPath . 'JavaScript/defaultconfig.js',
            'toolbar' => 'Basic',
            'uiColor' => '#F8F8F8',
            'stylesSet' => 'default',
            'extraPlugins' => '',
            'RTEtsConfigParams' => $this->getRTEtsConfigParams(),
            'contentsLanguage' => $this->getContentsLanguage(),
        ];

        $externalPlugins = '';
        foreach ($this->getExternalPlugins() as $pluginName => $config) {
            $customConfig[$pluginName] = $config['config'];
            $customConfig['extraPlugins'] .= ',' . $pluginName;

            $externalPlugins .= 'CKEDITOR.plugins.addExternal(';
            $externalPlugins .= GeneralUtility::quoteJSvalue($pluginName) . ',';
            $externalPlugins .= GeneralUtility::quoteJSvalue($config['path']) . ',';
            $externalPlugins .= '\'\');';
        }

        return 'function(CKEDITOR) {
                CKEDITOR.config.height = 400;
                CKEDITOR.contentsCss = "' . $resourcesPath . 'Css/contents.css";
                CKEDITOR.config.width = "auto";
                ' . $externalPlugins . '
                CKEDITOR.replace("' . $fieldId . '", ' . json_encode($customConfig) . ');
        }';
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
     * Get configuration of external/additional plugins
     *
     * @return array
     */
    protected function getExternalPlugins() : array
    {
        // todo: find new name for this option (do we still need this?)
        // Initializing additional attributes
        $additionalAttributes = [];
        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rte_ckeditor']['plugins']['TYPO3Link']['additionalAttributes']) {
            $additionalAttributes = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rte_ckeditor']['plugins']['TYPO3Link']['additionalAttributes'], true);
        }

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        // todo: add api for this https://forge.typo3.org/issues/78929
        $pluginPath = PathUtility::getAbsoluteWebPath(
            ExtensionManagementUtility::extPath('rte_ckeditor', 'Resources/Public/JavaScript/Plugins/typo3link.js')
        );
        $externalPlugins = [
            'typo3link' => [
                'path' => $pluginPath,
                'config' => [
                    'routeUrl' => (string)$uriBuilder->buildUriFromRoute('rteckeditor_wizard_browse_links'),
                    'additionalAttributes' => $additionalAttributes
                ]
            ]
        ];

        return $externalPlugins;
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
