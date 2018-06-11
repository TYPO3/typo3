<?php
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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Base class for form elements of FormEngine. Contains several helper methods used by single elements.
 */
abstract class AbstractFormElement extends AbstractNode
{
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
     */
    protected $iconFactory;

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
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
     *
     * @return bool
     */
    protected function hasNullCheckboxButNoPlaceholder(): bool
    {
        $hasNullCheckboxNoPlaceholder = false;
        $parameterArray = $this->data['parameterArray'];
        $mode = $parameterArray['fieldConf']['config']['mode'] ?? '';
        if (empty($this->data['flexFormDataStructureIdentifier'])
            && !empty($parameterArray['fieldConf']['config']['eval'])
            && GeneralUtility::inList($parameterArray['fieldConf']['config']['eval'], 'null')
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
     *
     * @return bool
     */
    protected function hasNullCheckboxWithPlaceholder(): bool
    {
        $hasNullCheckboxWithPlaceholder = false;
        $parameterArray = $this->data['parameterArray'];
        $mode = $parameterArray['fieldConf']['config']['mode'] ?? '';
        if (empty($this->data['flexFormDataStructureIdentifier'])
            && !empty($parameterArray['fieldConf']['config']['eval'])
            && GeneralUtility::inList($parameterArray['fieldConf']['config']['eval'], 'null')
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
    protected function formatValue($format, $itemValue, $formatOptions = [])
    {
        switch ($format) {
            case 'date':
                if ($itemValue) {
                    $option = isset($formatOptions['option']) ? trim($formatOptions['option']) : '';
                    if ($option) {
                        if (isset($formatOptions['strftime']) && $formatOptions['strftime']) {
                            $value = strftime($option, $itemValue);
                        } else {
                            $value = date($option, $itemValue);
                        }
                    } else {
                        $value = date('d-m-Y', $itemValue);
                    }
                } else {
                    $value = '';
                }
                if (isset($formatOptions['appendAge']) && $formatOptions['appendAge']) {
                    $age = BackendUtility::calcAge(
                        $GLOBALS['EXEC_TIME'] - $itemValue,
                        $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.minutesHoursDaysYears')
                    );
                    $value .= ' (' . $age . ')';
                }
                $itemValue = $value;
                break;
            case 'datetime':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && !is_null($itemValue)) {
                    $itemValue = BackendUtility::datetime((int)$itemValue);
                }
                break;
            case 'time':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && !is_null($itemValue)) {
                    $itemValue = BackendUtility::time((int)$itemValue, false);
                }
                break;
            case 'timesec':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && !is_null($itemValue)) {
                    $itemValue = BackendUtility::time((int)$itemValue);
                }
                break;
            case 'year':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && !is_null($itemValue)) {
                    $itemValue = date('Y', (int)$itemValue);
                }
                break;
            case 'int':
                $baseArr = ['dec' => 'd', 'hex' => 'x', 'HEX' => 'X', 'oct' => 'o', 'bin' => 'b'];
                $base = isset($formatOptions['base']) ? trim($formatOptions['base']) : '';
                $format = isset($baseArr[$base]) ? $baseArr[$base] : 'd';
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
                $format = isset($formatOptions['option']) ? trim($formatOptions['option']) : '';
                $itemValue = sprintf('%' . $format, $itemValue);
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
        return $itemValue;
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

        $size = round($size * $compensationForLargeDocuments);
        return ceil($size * $compensationForFormFields);
    }

    /**
     * @return bool TRUE if wizards are disabled on a global level
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9 - remove together with renderWizards(), log is thrown in renderWizards()
     */
    protected function isWizardsDisabled()
    {
        return !empty($this->data['disabledWizards']);
    }

    /**
     * Rendering wizards for form fields.
     *
     * Deprecated, old "wizard" API. This method will be removed in v9, but is kept for
     * backwards compatibility. Extensions that give the item HTML in $itemKinds, trigger
     * the legacy mode of this method which wraps calculated wizards around the given item HTML.
     *
     * This method is deprecated and will vanish in v9. Migrate old wizards to the "fieldWizard",
     * "fieldInformation" and "fieldControl" API instead.
     *
     * @param array|null $itemKinds Array with the real item in the first value. Array in legacy mode, else null
     * @param array $wizConf The "wizards" key from the config array for the field (from TCA)
     * @param string $table Table name
     * @param array $row The record array
     * @param string $fieldName The field name
     * @param array $PA Additional configuration array.
     * @param string $itemName The field name
     * @param array $specConf Special configuration if available.
     * @param bool $RTE Whether the RTE could have been loaded.
     * @return string|array String in legacy mode, an array with the buttons and the controls in non-legacy mode
     * @throws \InvalidArgumentException
     * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
     */
    protected function renderWizards(
        $itemKinds = null,
        $wizConf = null,
        $table = null,
        $row = null,
        $fieldName = null,
        $PA = null,
        $itemName = null,
        $specConf = null,
        $RTE = null
    ) {
        if ($itemKinds !== null) {
            // Deprecation log if the old $itemsKinds array comes in containing the item HTML - all core elements
            // deliver null here. If not null, the legacy mode of the method is enabled that wraps the calculated
            // wizards around given item HTML.
            GeneralUtility::logDeprecatedFunction();
        }
        $item = '';
        if (is_array($itemKinds)) {
            $item = $itemKinds[0];
        }

        if ($wizConf === null) {
            $wizConf = $this->data['parameterArray']['fieldConf']['config']['wizards'] ?? null;
        }
        if ($table === null) {
            $table = $this->data['tableName'];
        }
        if ($row === null) {
            $row = $this->data['databaseRow'];
        }
        if ($fieldName === null) {
            $fieldName = $this->data['fieldName'];
        }
        if ($PA === null) {
            $PA = $this->data['parameterArray'];
        }
        if ($itemName === null) {
            $itemName = $PA['itemFormElName'];
        }
        if ($RTE === null) {
            $RTE = false;
            if ((bool)$this->data['parameterArray']['fieldConf']['config']['enableRichtext'] === true) {
                $RTE = true;
            }
        }

        // Return not changed main item directly if wizards are disabled
        if (!is_array($wizConf) || $this->isWizardsDisabled()) {
            if ($itemKinds === null) {
                return [
                    'fieldControl' => [],
                    'fieldWizard' => [],
                ];
            }
            return $item;
        }

        $languageService = $this->getLanguageService();

        $fieldChangeFunc = $PA['fieldChangeFunc'];
        $md5ID = 'ID' . GeneralUtility::shortMD5($itemName);
        $prefixOfFormElName = 'data[' . $table . '][' . $row['uid'] . '][' . $fieldName . ']';
        $flexFormPath = '';
        if (GeneralUtility::isFirstPartOfStr($PA['itemFormElName'], $prefixOfFormElName)) {
            $flexFormPath = str_replace('][', '/', substr($PA['itemFormElName'], strlen($prefixOfFormElName) + 1, -1));
        }

        // Add a suffix-value if the item is a selector box with renderType "selectSingleBox":
        if ($PA['fieldConf']['config']['type'] === 'select' && (int)$PA['fieldConf']['config']['maxitems'] > 1 && $PA['fieldConf']['config']['renderType'] === 'selectSingleBox') {
            $itemName .= '[]';
        }

        $buttonWizards = [];
        $otherWizards = [];
        foreach ($wizConf as $wizardIdentifier => $wizardConfiguration) {
            if (!isset($wizardConfiguration['module']['name']) && isset($wizardConfiguration['script'])) {
                throw new \InvalidArgumentException('The way registering a wizard in TCA has changed in 6.2 and was removed in CMS 7. '
                    . 'Please set module[name]=module_name instead of using script=path/to/script.php in your TCA. ', 1437750231);
            }

            // If an identifier starts with "_", this is a configuration option like _POSITION and not a wizard
            if ($wizardIdentifier[0] === '_') {
                continue;
            }

            // Sanitize wizard type
            $wizardConfiguration['type'] = (string)$wizardConfiguration['type'];

            // Wizards can be shown based on selected "type" of record. If this is the case, the wizard configuration
            // is set to enableByTypeConfig = 1, and the wizardIdentifier is found in $wizardsEnabledByType
            $wizardIsEnabled = true;
            // Disable if wizard is for RTE fields only and the handled field is no RTE field or RTE can not be loaded
            if (isset($wizardConfiguration['RTEonly']) && (bool)$wizardConfiguration['RTEonly'] && !$RTE) {
                $wizardIsEnabled = false;
            }
            // Disable if wizard is for not-new records only and we're handling a new record
            if (isset($wizardConfiguration['notNewRecords']) && $wizardConfiguration['notNewRecords'] && !MathUtility::canBeInterpretedAsInteger($row['uid'])) {
                $wizardIsEnabled = false;
            }
            // Wizard types script, colorbox and popup must contain a module name configuration
            if (!isset($wizardConfiguration['module']['name']) && in_array($wizardConfiguration['type'], ['script', 'colorbox', 'popup'], true)) {
                $wizardIsEnabled = false;
            }

            if (!$wizardIsEnabled) {
                continue;
            }

            // Title / icon:
            $iTitle = htmlspecialchars($languageService->sL($wizardConfiguration['title']));
            if (isset($wizardConfiguration['icon'])) {
                $icon = FormEngineUtility::getIconHtml($wizardConfiguration['icon'], $iTitle, $iTitle);
            } else {
                $icon = $iTitle;
            }

            switch ($wizardConfiguration['type']) {
                case 'userFunc':
                    GeneralUtility::logDeprecatedFunction();
                    $params = [];
                    $params['params'] = $wizardConfiguration['params'];
                    $params['exampleImg'] = $wizardConfiguration['exampleImg'];
                    $params['table'] = $table;
                    $params['uid'] = $row['uid'];
                    $params['pid'] = $row['pid'];
                    $params['field'] = $fieldName;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['returnUrl'] = $this->data['returnUrl'];

                    $params['formName'] = 'editform';
                    $params['itemName'] = $itemName;
                    $params['hmac'] = GeneralUtility::hmac($params['formName'] . $params['itemName'], 'wizard_js');
                    $params['fieldChangeFunc'] = $fieldChangeFunc;
                    $params['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($fieldChangeFunc));

                    $params['item'] = $item;
                    $params['icon'] = $icon;
                    $params['iTitle'] = $iTitle;
                    $params['wConf'] = $wizardConfiguration;
                    $params['row'] = $row;
                    $otherWizards[] = GeneralUtility::callUserFunction($wizardConfiguration['userFunc'], $params, $this);
                    break;

                case 'script':
                    GeneralUtility::logDeprecatedFunction();
                    $params = [];
                    $params['params'] = $wizardConfiguration['params'];
                    $params['exampleImg'] = $wizardConfiguration['exampleImg'];
                    $params['table'] = $table;
                    $params['uid'] = $row['uid'];
                    $params['pid'] = $row['pid'];
                    $params['field'] = $fieldName;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['returnUrl'] = $this->data['returnUrl'];

                    // Resolving script filename and setting URL.
                    $urlParameters = [];
                    if (isset($wizardConfiguration['module']['urlParameters']) && is_array($wizardConfiguration['module']['urlParameters'])) {
                        $urlParameters = $wizardConfiguration['module']['urlParameters'];
                    }
                    $wScript = BackendUtility::getModuleUrl($wizardConfiguration['module']['name'], $urlParameters);
                    $url = $wScript . (strstr($wScript, '?') ? '' : '?') . GeneralUtility::implodeArrayForUrl('', ['P' => $params]);
                    $buttonWizards[] =
                        '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" onclick="this.blur(); return !TBE_EDITOR.isFormChanged();">'
                            . $icon .
                        '</a>';
                    break;

                case 'popup':
                    GeneralUtility::logDeprecatedFunction();
                    $params = [];
                    $params['params'] = $wizardConfiguration['params'];
                    $params['exampleImg'] = $wizardConfiguration['exampleImg'];
                    $params['table'] = $table;
                    $params['uid'] = $row['uid'];
                    $params['pid'] = $row['pid'];
                    $params['field'] = $fieldName;
                    $params['flexFormPath'] = $flexFormPath;
                    $params['md5ID'] = $md5ID;
                    $params['returnUrl'] = $this->data['returnUrl'];

                    $params['formName'] = 'editform';
                    $params['itemName'] = $itemName;
                    $params['hmac'] = GeneralUtility::hmac($params['formName'] . $params['itemName'], 'wizard_js');
                    $params['fieldChangeFunc'] = $fieldChangeFunc;
                    $params['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($fieldChangeFunc));

                    // Resolving script filename and setting URL.
                    $urlParameters = [];
                    if (isset($wizardConfiguration['module']['urlParameters']) && is_array($wizardConfiguration['module']['urlParameters'])) {
                        $urlParameters = $wizardConfiguration['module']['urlParameters'];
                    }
                    $wScript = BackendUtility::getModuleUrl($wizardConfiguration['module']['name'], $urlParameters);
                    $url = $wScript . (strstr($wScript, '?') ? '' : '?') . GeneralUtility::implodeArrayForUrl('', ['P' => $params]);

                    $onlyIfSelectedJS = '';
                    if (isset($wizardConfiguration['popup_onlyOpenIfSelected']) && $wizardConfiguration['popup_onlyOpenIfSelected']) {
                        $notSelectedText = $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.noSelItemForEdit');
                        $onlyIfSelectedJS =
                            'if (!TBE_EDITOR.curSelected(' . GeneralUtility::quoteJSvalue($itemName) . ')){' .
                                'alert(' . GeneralUtility::quoteJSvalue($notSelectedText) . ');' .
                                'return false;' .
                            '}';
                    }
                    $aOnClick =
                        'this.blur();' .
                        $onlyIfSelectedJS .
                        'vHWin=window.open(' . GeneralUtility::quoteJSvalue($url) . '+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode(' .
                                'document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value' .
                            ')' .
                            '+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(' . GeneralUtility::quoteJSvalue($itemName) . '),' .
                            GeneralUtility::quoteJSvalue('popUp' . $md5ID) . ',' .
                            GeneralUtility::quoteJSvalue($wizardConfiguration['JSopenParams']) .
                        ');' .
                        'vHWin.focus();' .
                        'return false;';

                    $buttonWizards[] =
                        '<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
                            $icon .
                        '</a>';
                    break;
            }
        }

        if ($itemKinds === null) {
            // Return an array with the two wizard types directly if the legacy mode
            // is not enabled.
            return [
                'fieldControl' => $buttonWizards,
                'fieldWizard' => $otherWizards,
            ];
        }

        // For each rendered wizard, put them together around the item.
        if (!empty($buttonWizards) || !empty($otherWizards)) {
            $innerContent = '';
            if (!empty($buttonWizards)) {
                $innerContent .= '<div class="btn-group' . ($wizConf['_VERTICAL'] ? ' btn-group-vertical' : '') . '">' . implode('', $buttonWizards) . '</div>';
            }
            $innerContent .= implode(' ', $otherWizards);

            // Position
            if ($wizConf['_POSITION'] === 'left') {
                $innerContent = '<div class="form-wizards-items-aside">' . $innerContent . '</div><div class="form-wizards-element">' . $item . '</div>';
            } elseif ($wizConf['_POSITION'] === 'top') {
                $innerContent = '<div class="form-wizards-items-top">' . $innerContent . '</div><div class="form-wizards-element">' . $item . '</div>';
            } elseif ($wizConf['_POSITION'] === 'bottom') {
                $innerContent = '<div class="form-wizards-element">' . $item . '</div><div class="form-wizards-items-bottom">' . $innerContent . '</div>';
            } else {
                $innerContent = '<div class="form-wizards-element">' . $item . '</div><div class="form-wizards-items-aside">' . $innerContent . '</div>';
            }
            $item = '
				<div class="form-wizards-wrap">
					' . $innerContent . '
				</div>';
        }

        return $item;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
