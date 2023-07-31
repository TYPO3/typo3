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

namespace TYPO3\CMS\Backend\Form\Element;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait;
use TYPO3\CMS\Backend\Form\Behavior\UpdateBitmaskOnFieldChange;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Base class for form elements of FormEngine. Contains several helper methods used by single elements.
 */
abstract class AbstractFormElement extends AbstractNode
{
    use OnFieldChangeTrait;

    /**
     * Default width value for a couple of elements like text
     *
     * @var int
     */
    protected $defaultInputWidth = 30;

    /**
     * Minimum width value for a couple of elements like text
     *
     * @var int
     */
    protected $minimumInputWidth = 10;

    /**
     * Maximum width value for a couple of elements like text
     *
     * @var int
     */
    protected $maxInputWidth = 50;

    /**
     * @var IconFactory
     * @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0.
     */
    protected $iconFactory;

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @deprecated since TYPO3 v12.4. Default constructor will be removed in v13.
     */
    public function __construct(NodeFactory $nodeFactory = null, array $data = [])
    {
        parent::__construct($nodeFactory, $data);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Merge field information configuration with default and render them.
     *
     * @return array Result array
     */
    protected function renderFieldInformation(): array
    {
        $options = $this->data;
        $fieldInformation = $this->defaultFieldInformation;
        $fieldInformationFromTca = $options['parameterArray']['fieldConf']['config']['fieldInformation'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fieldInformation, $fieldInformationFromTca);
        $options['renderType'] = 'fieldInformation';
        $options['renderData']['fieldInformation'] = $fieldInformation;
        return $this->nodeFactory->create($options)->render();
    }

    /**
     * Merge field control configuration with default controls and render them.
     *
     * @return array Result array
     */
    protected function renderFieldControl(): array
    {
        $options = $this->data;
        $fieldControl = $this->defaultFieldControl;
        $fieldControlFromTca = $options['parameterArray']['fieldConf']['config']['fieldControl'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fieldControl, $fieldControlFromTca);
        $options['renderType'] = 'fieldControl';
        $options['renderData']['fieldControl'] = $fieldControl;
        return $this->nodeFactory->create($options)->render();
    }

    /**
     * Merge field wizard configuration with default wizards and render them.
     *
     * @return array Result array
     */
    protected function renderFieldWizard(): array
    {
        $options = $this->data;
        $fieldWizard = $this->defaultFieldWizard;
        $fieldWizardFromTca = $options['parameterArray']['fieldConf']['config']['fieldWizard'] ?? [];
        ArrayUtility::mergeRecursiveWithOverrule($fieldWizard, $fieldWizardFromTca);
        $options['renderType'] = 'fieldWizard';
        $options['renderData']['fieldWizard'] = $fieldWizard;
        return $this->nodeFactory->create($options)->render();
    }

    /**
     * Render a label element for the current field by given id.
     */
    protected function renderLabel(string $for): string
    {
        $label = htmlspecialchars($this->data['parameterArray']['fieldConf']['label'] ?? '');
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] && $this->getBackendUser()->isAdmin()) {
            $fieldName = $this->data['flexFormFieldName'] ?? $this->data['fieldName'];
            $label .= ' <code>[' . htmlspecialchars($fieldName) . ']</code>';
        }
        return '<label for="' . htmlspecialchars($for) . '" class="form-label t3js-formengine-label">' . $label . '</label>';
    }

    /**
     * Elements that don't render a simple input field can't have a '<label for="..."'.
     * A fieldset with a legend is used instead.
     */
    protected function wrapWithFieldsetAndLegend(string $innerHTML): string
    {
        $legend = htmlspecialchars($this->data['parameterArray']['fieldConf']['label'] ?? '');
        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['debug'] && $this->getBackendUser()->isAdmin()) {
            $fieldName = $this->data['flexFormFieldName'] ?? $this->data['fieldName'];
            $legend .= ' <code>[' . htmlspecialchars($fieldName) . ']</code>';
        }
        $html = [];
        $html[] = '<fieldset>';
        $html[] =     '<legend class="form-legend t3js-formengine-legend">' . $legend . '</legend>';
        $html[] =     $innerHTML;
        $html[] = '</fieldset>';
        return implode(LF, $html);
    }

    /**
     * Returns true if the "null value" checkbox should be rendered. This is used in some
     * "text" based types like "text" and "input" for some renderType's.
     *
     * A field has eval=null set, but has no useOverridePlaceholder defined.
     * Goal is to have a field that can distinct between NULL and empty string in the database.
     * A checkbox and an additional hidden field will be created, both with the same name
     * and prefixed with "control[active]". If the checkbox is set (value 1), the value from the casual
     * input field will be written to the database. If the checkbox is not set, the hidden field
     * transfers value=0 to DataHandler, the value of the input field will then be reset to NULL by the
     * DataHandler at an early point in processing, so NULL will be written to DB as field value.
     *
     * All that only works if the field is not within flex form scope since flex forms
     * can not store a "null" value or distinct it from "empty string".
     */
    protected function hasNullCheckboxButNoPlaceholder(): bool
    {
        $hasNullCheckboxNoPlaceholder = false;
        $parameterArray = $this->data['parameterArray'];
        $mode = $parameterArray['fieldConf']['config']['mode'] ?? '';
        if (empty($this->data['flexFormDataStructureIdentifier'])
            && ($parameterArray['fieldConf']['config']['nullable'] ?? false)
            && ($mode !== 'useOrOverridePlaceholder')
        ) {
            $hasNullCheckboxNoPlaceholder = true;
        }
        return $hasNullCheckboxNoPlaceholder;
    }

    /**
     * Returns true if the "null value" checkbox should be rendered and the placeholder
     * handling is enabled. This is used in some "text" based types like "text" and
     * "input" for some renderType's.
     *
     * A field has useOverridePlaceholder set and null in eval and is not within a flex form.
     * Here, a value from a deeper DB structure can be "fetched up" as value, and can also be overridden by a
     * local value. This is used in FAL, where eg. the "title" field can have the default value from sys_file_metadata,
     * the title field of sys_file_reference is then set to NULL. Or the "override" checkbox is set, and a string
     * or an empty string is then written to the field of sys_file_reference.
     * The situation is similar to hasNullCheckboxButNoPlaceholder(), but additionally a "default" value should be shown.
     * To achieve this, again a hidden control[hidden] field is added together with a checkbox with the same name
     * to transfer the information whether the default value should be used or not: Checkbox checked transfers 1 as
     * value in control[active], meaning the overridden value should be used.
     * Additionally to the casual input field, a second field is added containing the "placeholder" value. This
     * field has no name attribute and is not transferred at all. Those two are then hidden / shown depending
     * on the state of the above checkbox in via JS.
     */
    protected function hasNullCheckboxWithPlaceholder(): bool
    {
        $hasNullCheckboxWithPlaceholder = false;
        $parameterArray = $this->data['parameterArray'];
        $mode = $parameterArray['fieldConf']['config']['mode'] ?? '';
        if (empty($this->data['flexFormDataStructureIdentifier'])
            && ($parameterArray['fieldConf']['config']['nullable'] ?? false)
            && ($mode === 'useOrOverridePlaceholder')
        ) {
            $hasNullCheckboxWithPlaceholder = true;
        }
        return $hasNullCheckboxWithPlaceholder;
    }

    /**
     * Format field content if 'format' is set to date, filesize, ..., user
     *
     * @param string $format Configuration for the display.
     * @param string $itemValue The value to display
     * @param array $formatOptions Format options
     * @return string Formatted field value
     */
    protected function formatValue($format, $itemValue, $formatOptions = []): string
    {
        switch ($format) {
            case 'date':
                if ($itemValue) {
                    $option = isset($formatOptions['option']) ? trim($formatOptions['option']) : '';
                    if ($option) {
                        if (isset($formatOptions['strftime']) && $formatOptions['strftime']) {
                            $user = $this->getBackendUser();
                            if ($user->user['lang'] ?? false) {
                                $locale = GeneralUtility::makeInstance(Locales::class)->createLocale($user->user['lang']);
                            } else {
                                $locale = new Locale();
                            }
                            $value = (new DateFormatter())->strftime($option, (int)$itemValue, $locale);
                        } else {
                            $value = date($option, (int)$itemValue);
                        }
                    } else {
                        $value = date('d-m-Y', (int)$itemValue);
                    }
                } else {
                    $value = '';
                }
                if (isset($formatOptions['appendAge']) && $formatOptions['appendAge']) {
                    $age = BackendUtility::calcAge(
                        $GLOBALS['EXEC_TIME'] - $itemValue,
                        $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                    );
                    $value .= ' (' . $age . ')';
                }
                $itemValue = $value;
                break;
            case 'datetime':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && $itemValue !== null) {
                    $itemValue = BackendUtility::datetime((int)$itemValue);
                }
                break;
            case 'time':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && $itemValue !== null) {
                    $itemValue = BackendUtility::time((int)$itemValue, false);
                }
                break;
            case 'timesec':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && $itemValue !== null) {
                    $itemValue = BackendUtility::time((int)$itemValue);
                }
                break;
            case 'year':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && $itemValue !== null) {
                    $itemValue = date('Y', (int)$itemValue);
                }
                break;
            case 'int':
                $baseArr = ['dec' => 'd', 'hex' => 'x', 'HEX' => 'X', 'oct' => 'o', 'bin' => 'b'];
                $base = isset($formatOptions['base']) ? trim($formatOptions['base']) : '';
                $format = $baseArr[$base] ?? 'd';
                $itemValue = sprintf('%' . $format, $itemValue);
                break;
            case 'float':
                // default precision
                $precision = 2;
                if (isset($formatOptions['precision'])) {
                    $precision = MathUtility::forceIntegerInRange($formatOptions['precision'], 1, 10, $precision);
                }
                $itemValue = sprintf('%.' . $precision . 'f', $itemValue);
                break;
            case 'number':
                $format = isset($formatOptions['option']) ? '%' . trim($formatOptions['option']) : '';
                $itemValue = sprintf($format, $itemValue);
                break;
            case 'md5':
                $itemValue = md5($itemValue);
                break;
            case 'filesize':
                // We need to cast to int here, otherwise empty values result in empty output,
                // but we expect zero.
                $value = GeneralUtility::formatSize((int)$itemValue);
                if (!empty($formatOptions['appendByteSize'])) {
                    $value .= ' (' . $itemValue . ')';
                }
                $itemValue = $value;
                break;
            case 'user':
                $func = trim($formatOptions['userFunc']);
                if ($func) {
                    $params = [
                        'value' => $itemValue,
                        'args' => $formatOptions['userFunc'],
                        'config' => [
                            'type' => 'none',
                            'format' => $format,
                            'format.' => $formatOptions,
                        ],
                    ];
                    $itemValue = GeneralUtility::callUserFunction($func, $params, $this);
                }
                break;
            default:
                // Do nothing e.g. when $format === ''
        }
        // Make sure we have a string in the end. $itemValue could be null, for instance.
        return (string)$itemValue;
    }

    /**
     * Returns the max width in pixels for an elements like input and text
     *
     * @param int $size The abstract size value (1-48)
     * @return int Maximum width in pixels
     */
    protected function formMaxWidth($size = 48)
    {
        $compensationForLargeDocuments = 1.33;
        $compensationForFormFields = 12;

        $compensatedSize = round($size * $compensationForLargeDocuments);
        return (int)ceil($compensatedSize * $compensationForFormFields);
    }

    /**
     * Handle custom javascript `eval` implementations. $evalObject is a hook object
     * for custom eval's. It is transferred to JS as a JavaScriptModuleInstruction if possible.
     * This is used by a couple of renderType's like various type="input", should
     * be used with care and is internal for now.
     *
     * @internal
     */
    protected function resolveJavaScriptEvaluation(array $resultArray, string $name, ?object $evalObject): array
    {
        if (!is_object($evalObject) || !method_exists($evalObject, 'returnFieldJS')) {
            return $resultArray;
        }

        $javaScriptEvaluation = $evalObject->returnFieldJS();
        if ($javaScriptEvaluation instanceof JavaScriptModuleInstruction) {
            if ($javaScriptEvaluation->shallLoadRequireJs()) {
                // just use the module name and export-name
                // @deprecated will be removed in TYPO3 v13.0
                $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::forRequireJS(
                    $javaScriptEvaluation->getName(),
                    $javaScriptEvaluation->getExportName(),
                    // silence deprecation error, has already been triggered by the original JavaScriptModuleInstruction instance
                    true
                )->invoke('registerCustomEvaluation', $name);
            } else {
                // just use the module name and export-name
                $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
                    $javaScriptEvaluation->getName(),
                    $javaScriptEvaluation->getExportName()
                )->invoke('registerCustomEvaluation', $name);
            }
        } else {
            trigger_error(
                sprintf('Using inline JavaScript for custom eval function in "%s" is deprecated. Use JavaScript modules instead.', $name),
                E_USER_DEPRECATED
            );
            // @deprecated since TYPO3 v12.4. will be removed in TYPO3 v13.0.
            $resultArray['additionalJavaScriptPost'][] = sprintf(
                'var TBE_EDITOR = TBE_EDITOR || { customEvalFunctions: {} }; TBE_EDITOR.customEvalFunctions[%s] = function(value) { %s };',
                GeneralUtility::quoteJSvalue($name),
                $javaScriptEvaluation
            );
        }

        return $resultArray;
    }

    /***********************************************
     * CheckboxElement related methods
     ***********************************************/

    /**
     * Creates checkbox parameters
     *
     * @param string $itemName Form element name
     * @param int $formElementValue The value of the checkbox (representing checkboxes with the bits)
     * @param int $checkbox Checkbox # (0-9?)
     * @param int $checkboxesCount Total number of checkboxes in the array.
     * @param array $fieldChangeFuncs `fieldChangeFunc` items for client-side handling
     * @param bool $invert Inverts the state of the checkbox (but not of the bit value)
     * @return string either `onclick` attr or `data-formengine-field-change-*` attrs + possibly the checked-option set
     * @internal
     */
    protected function checkBoxParams(
        string $itemName,
        int $formElementValue,
        int $checkbox,
        int $checkboxesCount,
        array $fieldChangeFuncs = [],
        bool $invert = false
    ): string {
        array_unshift($fieldChangeFuncs, new UpdateBitmaskOnFieldChange(
            $checkbox,
            $checkboxesCount,
            $invert,
            $itemName
        ));
        $checkboxPow = 2 ** $checkbox;
        $checked = $formElementValue & $checkboxPow;
        $attrs = $this->getOnFieldChangeAttrs('click', $fieldChangeFuncs);
        if ($checked xor $invert) {
            $attrs['checked'] = 'checked';
        }
        return GeneralUtility::implodeAttributes($attrs, true);
    }

    /**
     * Calculates the bootstrap grid classes based on the amount of columns
     * defined in the checkbox item TCA
     *
     * @internal
     */
    protected function calculateColumnMarkup(int $cols): array
    {
        $colWidth = (int)floor(12 / $cols);
        $colClass = 'col';
        $colClear = [];
        if ($colWidth === 6) {
            $colClass = 'col col-sm-6';
            $colClear = [
                2 => 'd-sm-block',
            ];
        } elseif ($colWidth === 4) {
            $colClass = 'col col-sm-4';
            $colClear = [
                3 => 'd-sm-block',
            ];
        } elseif ($colWidth === 3) {
            $colClass = 'col col-sm-6 col-md-3';
            $colClear = [
                2 => 'd-sm-block d-md-none',
                4 => 'd-sm-block d-md-block d-lg-none',
            ];
        } elseif ($colWidth <= 2) {
            $colClass = 'col col-sm-6 col-md-3 col-lg-2';
            $colClear = [
                2 => 'd-sm-block',
                4 => 'd-sm-block d-md-block d-lg-none',
                6 => 'd-sm-block d-md-block d-lg-block d-xl-none',
            ];
        }
        return [$colClass, $colClear];
    }

    /**
     * Append the value of a form field to its label
     */
    protected function appendValueToLabelInDebugMode(string|int $label, string|int $value): string
    {
        if ($value !== '' && $this->getBackendUser()->shallDisplayDebugInformation()) {
            return trim($label . ' [' . $value . ']');
        }

        return trim((string)$label);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
