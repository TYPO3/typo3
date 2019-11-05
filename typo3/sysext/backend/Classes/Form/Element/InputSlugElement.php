<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Controller\FormSlugAjaxController;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * General type=input element with some additional value.
 */
class InputSlugElement extends AbstractFormElement
{

    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    /**
     * Default field wizards enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldWizard = [
        'localizationStateSelector' => [
            'renderType' => 'localizationStateSelector',
        ],
        'otherLanguageContent' => [
            'renderType' => 'otherLanguageContent',
            'after' => [
                'localizationStateSelector'
            ],
        ],
        'defaultLanguageDifferences' => [
            'renderType' => 'defaultLanguageDifferences',
            'after' => [
                'otherLanguageContent',
            ],
        ],
    ];

    /**
     * This will render a single-line input form field, possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        $languageId = 0;
        if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField']) && !empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
            $languageId = (int)((is_array($row[$languageField]) ? $row[$languageField][0] : $row[$languageField]) ?? 0);
        }
        $baseUrl = $this->getPrefix($this->data['site'], $languageId);

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $size = MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = (int)$this->formMaxWidth($size);

        // Convert UTF-8 characters back (that is important, see Slug class when sanitizing)
        $itemValue = rawurldecode($itemValue);

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);
        $toggleButtonTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.toggleSlugExplanation');
        $recreateButtonTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:buttons.recreateSlugExplanation');

        $successMessage = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:slugCreation.success.' . ($table === 'pages' ? 'page' : 'record')), $baseUrl);
        $errorMessage = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:slugCreation.error'), $baseUrl);

        $thisSlugId = 't3js-form-field-slug-id' . StringUtility::getUniqueId();
        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $mainFieldHtml[] =  $fieldInformationHtml;
        $mainFieldHtml[] =  '<div class="form-control-wrap" style="max-width: ' . $width . 'px" id="' . htmlspecialchars($thisSlugId) . '">';
        $mainFieldHtml[] =      '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =          '<div class="form-wizards-element">';
        $mainFieldHtml[] =              '<div class="input-group">';
        $mainFieldHtml[] =                  ($baseUrl ? '<span class="input-group-addon">' . htmlspecialchars($baseUrl) . '</span>' : '');
        // We deal with 3 fields here: a readonly field for current / default values, an input
        // field to manipulate the value, and the final hidden field used to send the value
        $mainFieldHtml[] =                  '<input';
        $mainFieldHtml[] =                      ' class="form-control t3js-form-field-slug-readonly"';
        $mainFieldHtml[] =                      ' data-toggle="tooltip"';
        $mainFieldHtml[] =                      ' data-title="' . htmlspecialchars($itemValue) . '"';
        $mainFieldHtml[] =                      ' value="' . htmlspecialchars($itemValue) . '"';
        $mainFieldHtml[] =                      ' readonly';
        $mainFieldHtml[] =                  ' />';
        $mainFieldHtml[] =                  '<input type="text"';
        $mainFieldHtml[] =                      ' id="' . htmlspecialchars(StringUtility::getUniqueId('formengine-input-')) . '"';
        $mainFieldHtml[] =                      ' class="form-control t3js-form-field-slug-input hidden"';
        $mainFieldHtml[] =                      ' placeholder="' . htmlspecialchars($row['slug'] ?? '/') . '"';
        $mainFieldHtml[] =                      ' data-formengine-validation-rules="' . htmlspecialchars($this->getValidationDataAsJsonString($config)) . '"';
        $mainFieldHtml[] =                      ' data-formengine-input-params="' . htmlspecialchars(json_encode(['field' => $parameterArray['itemFormElName'], 'evalList' => implode(',', $evalList)])) . '"';
        $mainFieldHtml[] =                      ' data-formengine-input-name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $mainFieldHtml[] =                  ' />';
        $mainFieldHtml[] =                  '<span class="input-group-btn">';
        $mainFieldHtml[] =                      '<button class="btn btn-default t3js-form-field-slug-toggle" type="button" title="' . htmlspecialchars($toggleButtonTitle) . '">';
        $mainFieldHtml[] =                          $this->iconFactory->getIcon('actions-version-workspaces-preview-link', Icon::SIZE_SMALL)->render();
        $mainFieldHtml[] =                      '</button>';
        $mainFieldHtml[] =                      '<button class="btn btn-default t3js-form-field-slug-recreate" type="button" title="' . htmlspecialchars($recreateButtonTitle) . '">';
        $mainFieldHtml[] =                          $this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL)->render();
        $mainFieldHtml[] =                      '</button>';
        $mainFieldHtml[] =                  '</span>';
        $mainFieldHtml[] =                  '<input type="hidden"';
        $mainFieldHtml[] =                      ' class="t3js-form-field-slug-hidden"';
        $mainFieldHtml[] =                      ' name="' . htmlspecialchars($parameterArray['itemFormElName']) . '"';
        $mainFieldHtml[] =                      ' value="' . htmlspecialchars($itemValue) . '"';
        $mainFieldHtml[] =                  ' />';
        $mainFieldHtml[] =              '</div>';
        $mainFieldHtml[] =          '</div>';
        if (!empty($fieldControlHtml)) {
            $mainFieldHtml[] =      '<div class="form-wizards-items-aside">';
            $mainFieldHtml[] =          '<div class="btn-group">';
            $mainFieldHtml[] =              $fieldControlHtml;
            $mainFieldHtml[] =          '</div>';
            $mainFieldHtml[] =      '</div>';
        }
        $mainFieldHtml[] =          '<div class="form-wizards-items-bottom">';
        $mainFieldHtml[] =              '<span class="t3js-form-proposal-accepted hidden label label-success">' . htmlspecialchars($successMessage) . '<span>/abc/</span></span>';
        $mainFieldHtml[] =              '<span class="t3js-form-proposal-different hidden label label-warning">' . htmlspecialchars($errorMessage) . '<span>/abc/</span></span>';
        $mainFieldHtml[] =              $fieldWizardHtml;
        $mainFieldHtml[] =          '</div>';
        $mainFieldHtml[] =      '</div>';
        $mainFieldHtml[] =  '</div>';
        $mainFieldHtml[] = '</div>';

        $resultArray['html'] = implode(LF, $mainFieldHtml);

        [$commonElementPrefix] = GeneralUtility::revExplode('[', $parameterArray['itemFormElName'], 2);
        $validInputNamesToListenTo = [];
        $includeUidInValues = false;
        foreach ($config['generatorOptions']['fields'] ?? [] as $fieldNameParts) {
            if (is_string($fieldNameParts)) {
                $fieldNameParts = GeneralUtility::trimExplode(',', $fieldNameParts);
            }
            foreach ($fieldNameParts as $listenerFieldName) {
                if ($listenerFieldName === 'uid') {
                    $includeUidInValues = true;
                    continue;
                }
                $validInputNamesToListenTo[$listenerFieldName] = $commonElementPrefix . '[' . htmlspecialchars($listenerFieldName) . ']';
            }
        }
        $parentPageId = $this->data['parentPageRow']['uid'] ?? 0;
        $signature = GeneralUtility::hmac(
            implode(
                '',
                [
                    $table,
                    $this->data['effectivePid'],
                    $row['uid'],
                    $languageId,
                    $this->data['fieldName'],
                    $this->data['command'],
                    $parentPageId
                ]
            ),
            FormSlugAjaxController::class
        );
        $optionsForModule = [
            'pageId' => $this->data['effectivePid'],
            'recordId' => $row['uid'],
            'tableName' => $table,
            'fieldName' => $this->data['fieldName'],
            'config' => $config,
            'listenerFieldNames' => $validInputNamesToListenTo,
            'language' => $languageId,
            'originalValue' => $itemValue,
            'signature' => $signature,
            'command' => $this->data['command'],
            'parentPageId' => $parentPageId,
            'includeUidInValues' => $includeUidInValues,
        ];
        $resultArray['requireJsModules'][] = ['TYPO3/CMS/Backend/FormEngine/Element/SlugElement' => '
            function(SlugElement) {
                new SlugElement(' . GeneralUtility::quoteJSvalue('#' . $thisSlugId) . ', ' . json_encode($optionsForModule) . ');
            }'
        ];
        return $resultArray;
    }

    /**
     * Render the prefix for the input field.
     *
     * @param SiteInterface $site
     * @param int $requestLanguageId
     * @return string
     */
    protected function getPrefix(SiteInterface $site, int $requestLanguageId = 0): string
    {
        try {
            $language = ($requestLanguageId < 0) ? $site->getDefaultLanguage() : $site->getLanguageById($requestLanguageId);
            $base = $language->getBase();
            $baseUrl = (string)$base;
            $baseUrl = rtrim($baseUrl, '/');
            if (!empty($baseUrl) && empty($base->getScheme()) && $base->getHost() !== '') {
                $baseUrl = 'http:' . $baseUrl;
            }
        } catch (\InvalidArgumentException $e) {
            // No site / language found
            $baseUrl = '';
        }
        return $baseUrl;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
