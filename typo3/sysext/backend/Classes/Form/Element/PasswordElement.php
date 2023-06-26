<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyAction;
use TYPO3\CMS\Core\PasswordPolicy\PasswordPolicyValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * General type=password element.
 */
class PasswordElement extends AbstractFormElement
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
     * This will render a single-line password form field, possibly with various control/validation features
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        // @deprecated since v12, will be removed with v13 when all elements handle label/legend on their own
        $resultArray['labelHasBeenHandled'] = true;
        $config = $parameterArray['fieldConf']['config'];
        $passwordPolicyValidator = null;

        $itemValue = $parameterArray['itemFormElValue'];
        $width = $this->formMaxWidth(
            MathUtility::forceIntegerInRange($config['size'] ?? $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth)
        );
        $fieldId = StringUtility::getUniqueId('formengine-input-');
        $renderedLabel = $this->renderLabel($fieldId);

        $passwordPolicy = $config['passwordPolicy'] ?? null;

        // Ignore password policy for frontend users, if "security.usePasswordPolicyForFrontendUsers" is disabled
        $features = GeneralUtility::makeInstance(Features::class);
        if ($table === 'fe_users' && !$features->isFeatureEnabled('security.usePasswordPolicyForFrontendUsers')) {
            $passwordPolicy = null;
        }

        if ($passwordPolicy) {
            // We always use PasswordPolicyAction::NEW_USER_PASSWORD here, since the password is not set by the user,
            // but either by an admin or an editor
            $passwordPolicyValidator = GeneralUtility::makeInstance(
                PasswordPolicyValidator::class,
                PasswordPolicyAction::NEW_USER_PASSWORD,
                is_string($passwordPolicy) ? $passwordPolicy : ''
            );
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        if ($config['readOnly'] ?? false) {
            $html = [];
            $html[] = $renderedLabel;
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =   $fieldInformationHtml;
            $html[] =   '<div class="form-wizards-wrap">';
            $html[] =       '<div class="form-wizards-element">';
            $html[] =           '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
            $html[] =               '<input class="form-control" id="' . htmlspecialchars($fieldId) . '" value="' . ($itemValue ? '*********' : '') . '" type="text" disabled>';
            $html[] =           '</div>';
            $html[] =       '</div>';
            $html[] =   '</div>';
            $html[] = '</div>';
            $resultArray['html'] = implode(LF, $html);
            return $resultArray;
        }

        $languageService = $this->getLanguageService();
        $itemName = (string)$parameterArray['itemFormElName'];

        // Always add "trim" and "password" (required for JS validation)
        $evalList = ['trim', 'password'];
        if ($config['nullable'] ?? false) {
            $evalList[] = 'null';
        }

        $attributes = [
            'value' => '',
            'id' => $fieldId,
            'spellcheck' => 'false',
            'class' => implode(' ', [
                'form-control',
                'form-control-clearable',
                't3js-clearable',
                'hasDefaultValue',
            ]),
            'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            'data-formengine-input-params' => (string)json_encode([
                'field' => $itemName,
                'evalList' => implode(',', $evalList),
            ], JSON_THROW_ON_ERROR),
            'data-formengine-input-name' => $itemName,
        ];

        if (!empty($config['placeholder'])) {
            $attributes['placeholder'] = trim($config['placeholder']);
        }

        $attributes['autocomplete'] = ($config['autocomplete'] ?? false) ? 'new-' . $fieldName : 'off';

        $fieldControlResult = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $mainFieldHtml = [];
        $mainFieldHtml[] = '<div class="form-control-wrap" style="max-width: ' . $width . 'px">';
        $mainFieldHtml[] =  '<div class="form-wizards-wrap">';
        $mainFieldHtml[] =      '<div class="form-wizards-element">';
        $mainFieldHtml[] =          '<input type="password" ' . GeneralUtility::implodeAttributes($attributes, true) . ' />';
        $mainFieldHtml[] =          '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars((string)$itemValue) . '" />';
        $mainFieldHtml[] =      '</div>';
        if (!empty($fieldControlHtml)) {
            $mainFieldHtml[] =      '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $mainFieldHtml[] =          '<div class="btn-group">';
            $mainFieldHtml[] =              $fieldControlHtml;
            $mainFieldHtml[] =          '</div>';
            $mainFieldHtml[] =      '</div>';
        }
        if (!empty($fieldWizardHtml)) {
            $mainFieldHtml[] = '<div class="form-wizards-items-bottom">';
            $mainFieldHtml[] = $fieldWizardHtml;
            $mainFieldHtml[] = '</div>';
        }
        $mainFieldHtml[] =  '</div>';
        $mainFieldHtml[] = '</div>';
        $mainFieldHtml = implode(LF, $mainFieldHtml);

        $nullControlNameEscaped = htmlspecialchars('control[active][' . $table . '][' . $this->data['databaseRow']['uid'] . '][' . $fieldName . ']');

        $fullElement = $mainFieldHtml;
        if ($this->hasNullCheckboxButNoPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $fullElement = [];
            $fullElement[] = '<div class="t3-form-field-disable"></div>';
            $fullElement[] = '<div class="form-check t3-form-field-eval-null-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.nullCheckbox');
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = $mainFieldHtml;
            $fullElement = implode(LF, $fullElement);
        } elseif ($this->hasNullCheckboxWithPlaceholder()) {
            $checked = $itemValue !== null ? ' checked="checked"' : '';
            $placeholder = $shortenedPlaceholder = trim($config['placeholder'] ?? '');
            if ($placeholder !== '') {
                $shortenedPlaceholder = GeneralUtility::fixed_lgd_cs($placeholder, 20);
                if ($placeholder !== $shortenedPlaceholder) {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        '<span title="' . htmlspecialchars($placeholder) . '">' . htmlspecialchars($shortenedPlaceholder) . '</span>'
                    );
                } else {
                    $overrideLabel = sprintf(
                        $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override'),
                        htmlspecialchars($placeholder)
                    );
                }
            } else {
                $overrideLabel = $languageService->sL(
                    'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.placeholder.override_not_available'
                );
            }
            $fullElement = [];
            $fullElement[] = '<div class="form-check t3js-form-field-eval-null-placeholder-checkbox">';
            $fullElement[] =     '<input type="hidden" name="' . $nullControlNameEscaped . '" value="0" />';
            $fullElement[] =     '<input type="checkbox" class="form-check-input" name="' . $nullControlNameEscaped . '" id="' . $nullControlNameEscaped . '" value="1"' . $checked . ' />';
            $fullElement[] =     '<label class="form-check-label" for="' . $nullControlNameEscaped . '">';
            $fullElement[] =         $overrideLabel;
            $fullElement[] =     '</label>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-placeholder">';
            $fullElement[] =    '<div class="form-control-wrap" style="max-width:' . $width . 'px">';
            $fullElement[] =        '<input type="text" class="form-control" disabled="disabled" value="' . htmlspecialchars($shortenedPlaceholder) . '" />';
            $fullElement[] =    '</div>';
            $fullElement[] = '</div>';
            $fullElement[] = '<div class="t3js-formengine-placeholder-formfield">';
            $fullElement[] =    $mainFieldHtml;
            $fullElement[] = '</div>';
            $fullElement = implode(LF, $fullElement);
        }

        $passwordPolicyInfo = '';
        if ($passwordPolicy) {
            $passwordPolicyInfo = $this->renderPasswordPolicyRequirements($passwordPolicyValidator, $fieldId);
        }

        $passwordElementAttributes['class'] = 'formengine-field-item t3js-formengine-field-item';
        $passwordElementAttributes['recordFieldId'] = $fieldId;
        $passwordElementAttributes['passwordPolicy'] = $passwordPolicy;

        $resultArray['html'] = $renderedLabel . '
            <typo3-formengine-element-password ' . GeneralUtility::implodeAttributes($passwordElementAttributes, true) . '>
                ' . $fieldInformationHtml . '
                ' . $fullElement . '
                ' . $passwordPolicyInfo . '
            </typo3-formengine-element-password>';

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/element/password-element.js'
        );

        return $resultArray;
    }

    private function renderPasswordPolicyRequirements(
        PasswordPolicyValidator $passwordPolicyValidator,
        string $fieldId
    ): string {
        if (empty($passwordPolicyValidator->getRequirements())) {
            return '';
        }

        $passwordPolicyElement = [];
        $requirements = [];

        foreach ($passwordPolicyValidator->getRequirements() as $id => $requirement) {
            $requirements[] = '<li data-id="' . htmlspecialchars($fieldId . '-' . $id) . '">' . $requirement . '</li>';
        }

        $calloutTitle = $this->getLanguageService()->sL(
            'LLL:EXT:core/Resources/Private/Language/locallang_password_policy.xlf:passwordRequirements.description'
        );

        $passwordPolicyElement[] = '<div id="password-policy-info-' . htmlspecialchars($fieldId) . '" class="mt-2 callout callout-secondary hidden">';
        $passwordPolicyElement[] = '  <h5 class="callout-title">' . htmlspecialchars($calloutTitle) . '</h5>';
        $passwordPolicyElement[] = '  <div class="callout-body">';
        $passwordPolicyElement[] = '    <ul>';
        $passwordPolicyElement[] =        implode(LF, $requirements);
        $passwordPolicyElement[] = '    </ul>';
        $passwordPolicyElement[] = '  </div>';
        $passwordPolicyElement[] = '</div>';

        return implode(LF, $passwordPolicyElement);
    }
}
