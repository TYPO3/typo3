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

        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        $languageId = (int)($row[$languageField][0] ?? 0);
        $baseUrl = $this->getPrefix($this->data['site'], $languageId);

        $itemValue = $parameterArray['itemFormElValue'];
        $config = $parameterArray['fieldConf']['config'];
        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        $size = MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);
        $width = (int)$this->formMaxWidth($size);

        // Convert UTF-8 characters back (that is important, see Slug class when sanitizing)
        $itemValue = rawurldecode($itemValue);

        $idAttribute = StringUtility::getUniqueId('formengine-input-');
        $attributes = [
            'value' => '',
            'id' => $idAttribute,
            'class' => 'form-control',
            'disabled' => 'disabled',
            'placeholder' => '/',
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-formengine-input-params' => json_encode([
                'field' => $parameterArray['itemFormElName'],
                'evalList' => implode(',', $evalList),
                'is_in' => trim($config['is_in'] ?? '')
            ]),
            'data-formengine-input-name' => $parameterArray['itemFormElName'],
        ];

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $mainFieldHtml[] = $fieldInformationHtml;
        $mainFieldHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $mainFieldHtml[] =  '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =      '<div class="form-wizards-element">';
        $mainFieldHtml[] =          '<div class="input-group">' . ($baseUrl ? '<span class="input-group-addon">' . htmlspecialchars($baseUrl) . '</span>' : '') . '<input type="text"' . GeneralUtility::implodeAttributes($attributes, true) . ' /></div>';
        $mainFieldHtml[] =          '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($itemValue) . '" />';
        $mainFieldHtml[] =      '</div>';
        if (!empty($fieldControlHtml)) {
            $mainFieldHtml[] =  '<div class="form-wizards-items-aside">';
            $mainFieldHtml[] =      '<div class="btn-group">';
            $mainFieldHtml[] =          $fieldControlHtml;
            $mainFieldHtml[] =      '</div>';
            $mainFieldHtml[] =  '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $mainFieldHtml[] =  '<div class="form-wizards-items-bottom">';
            $mainFieldHtml[] =      $fieldWizardHtml;
            $mainFieldHtml[] =  '</div>';
        }
        $mainFieldHtml[] =  '</div>';
        $mainFieldHtml[] = '</div>';
        $mainFieldHtml[] = '</div>';

        $resultArray['html'] = implode(LF, $mainFieldHtml);
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
        $language = $site->getLanguageById($requestLanguageId);
        $base = $language->getBase();
        $baseUrl = (string)$base;
        $baseUrl = rtrim($baseUrl, '/');
        if (!empty($baseUrl) && empty($base->getScheme()) && $base->getHost() !== '') {
            $baseUrl = 'http:' . $baseUrl;
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
