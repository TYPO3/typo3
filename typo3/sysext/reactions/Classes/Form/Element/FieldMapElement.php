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

namespace TYPO3\CMS\Reactions\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\FlexFormSegment;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Reactions\Validation\CreateRecordReactionField;

/**
 * Creates a dynamic element to add values to table fields.
 *
 * This is rendered for config type=json, renderType=fieldMap
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class FieldMapElement extends AbstractFormElement
{
    private const TYPES_WITHOUT_OWN_CONTROL = ['text'];

    public function __construct(
        private readonly TcaSchemaFactory $schemaFactory
    ) {}

    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $itemValue = $parameterArray['itemFormElValue'];
        $itemName = $parameterArray['itemFormElName'];

        $tableName = (string)($this->data['databaseRow']['table_name'][0] ?? '');

        $fieldsHtml = '';
        $hasRows = false;
        if ($this->schemaFactory->has($tableName)) {
            // The map is stored as JSON and the record may be written by hand or through the
            // API, so a configured value may decode to anything. Only a scalar can ever be a
            // field value, and anything else would be read as a string further down.
            $itemValue = array_map(
                strval(...),
                array_filter(is_array($itemValue) ? $itemValue : [], is_scalar(...))
            );
            $columns = [];
            $backendUser = $this->getBackendUser();
            foreach ($this->schemaFactory->get($tableName)->getFields() as $fieldName => $fieldInfo) {
                // The rows are rendered without the container the editing form of a record
                // builds them in, which is where a field the integrator may not edit is
                // taken out, so the rule is applied here instead.
                if (CreateRecordReactionField::isMappable($fieldInfo)
                    && CreateRecordReactionField::isWritableBy($fieldInfo, $tableName, (string)$fieldName, $backendUser)
                ) {
                    $columns[(string)$fieldName] = [
                        'label' => $fieldInfo->getLabel(),
                        'config' => $fieldInfo->getConfiguration(),
                    ];
                }
            }
            $compiled = $this->compileTargetTable($tableName, $columns);
            // A field the map does not carry is one the reaction does not write, whether
            // the map was never saved or the integrator took the field out of it. The row
            // shows the control of the target table disabled, so what the table would
            // apply stays visible without the reaction writing it on every call.
            foreach ($compiled['processedTca']['columns'] as $fieldName => $column) {
                $fieldName = (string)$fieldName;
                $hasRows = true;
                $fieldsHtml .= $this->renderRow(
                    $fieldName,
                    $column,
                    $itemName,
                    (string)($itemValue[$fieldName] ?? ''),
                    $compiled,
                    $resultArray
                );
            }
        }

        if ($fieldsHtml !== '') {
            $fieldsHtml = '<div class="panel panel-default">' . $fieldsHtml . '</div>';
        } else {
            $fieldsHtml = '
                <div class="alert alert-warning">
                    ' . htmlspecialchars(sprintf($languageService->sL('reactions.db:fieldMapElement.noFields'), $tableName)) . '
                </div>';
        }

        if ($hasRows) {
            $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create('@typo3/reactions/field-map-element.js');
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =     $fieldInformationHtml;
        $html[] =     '<div class="form-control-wrap">';
        $html[] =         '<div class="form-wizards-wrap">';
        $html[] =             '<div class="form-wizards-item-element">';
        $html[] =                 '<input type="hidden" name="' . htmlspecialchars($itemName) . '" value="">';
        $html[] =                 $fieldsHtml;
        $html[] =             '</div>';
        $html[] =         '</div>';
        $html[] =     '</div>';
        $html[] = '</div>';
        $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        return $resultArray;
    }

    /**
     * Hands the mappable columns of the target table through the FormEngine data providers
     * the way a FlexForm container does.
     *
     * @param array<string, array{label: string, config: array}> $columns
     */
    protected function compileTargetTable(string $tableName, array $columns): array
    {
        if ($columns === []) {
            return ['processedTca' => ['columns' => []]];
        }
        // The reaction writes its records to the storage page, while the reaction record
        // itself sits on the root, sys_reaction being a rootLevel table. Items a field
        // composes for a page, and the TCEFORM of that page, therefore have to be resolved
        // for the page the records actually land on.
        $storagePid = $this->resolveStoragePid();
        $input = [
            'request' => $this->data['request'],
            'tableName' => $tableName,
            'command' => 'new',
            'pageTsConfig' => BackendUtility::getPagesTSconfig($storagePid),
            'databaseRow' => ['uid' => 0, 'pid' => $storagePid],
            'processedTca' => [
                'ctrl' => [],
                'columns' => $columns,
            ],
            'selectTreeCompileItems' => $this->data['selectTreeCompileItems'] ?? false,
            'flexParentDatabaseRow' => $this->data['databaseRow'],
            'effectivePid' => $storagePid,
            'inlineFirstPid' => $this->data['inlineFirstPid'] ?? 0,
            'tcaSchemata' => $this->data['tcaSchemata'],
            'fullTca' => $this->data['fullTca'],
        ];
        return GeneralUtility::makeInstance(FormDataCompiler::class)
            ->compile($input, GeneralUtility::makeInstance(FlexFormSegment::class));
    }

    /**
     * The page the reaction creates its records on. It is a relation, so the compiled row
     * carries it as the resolved record rather than as the uid that was stored.
     */
    protected function resolveStoragePid(): int
    {
        $storagePid = $this->data['databaseRow']['storage_pid'] ?? 0;
        if (is_array($storagePid)) {
            $storagePid = $storagePid[0]['uid'] ?? 0;
        }
        return MathUtility::canBeInterpretedAsInteger($storagePid) ? (int)$storagePid : 0;
    }

    /**
     * The control of a row whose type the field map does not hand to the target table. It
     * is named like the control of any other row, so the row drives it the same way.
     */
    protected function renderFallbackControl(string $fieldName, array $column, string $elementName, string $fieldValue): string
    {
        $fieldId = StringUtility::getUniqueId('formengine-fieldmap-');
        return '
            <label class="form-label" for="' . htmlspecialchars($fieldId) . '">
                ' . htmlspecialchars($this->resolveFieldLabel($fieldName, $column)) . '
            </label>
            <textarea class="form-control" id="' . htmlspecialchars($fieldId) . '" rows="3"
                name="' . htmlspecialchars($elementName) . '">' . htmlspecialchars($fieldValue) . '</textarea>';
    }

    /**
     * A row offering the field's own control beside a text input for a payload placeholder,
     * and a select choosing between them and "do not set".
     *
     * Exactly one hidden input carries the field name and holds what the row contributes
     * to the map. The two controls beside it are named outside the data array on purpose:
     * nothing they submit is stored, and the form's named getter answers with a single
     * element instead of a list of same-named ones.
     */
    protected function renderRow(
        string $fieldName,
        array $column,
        string $itemName,
        string $fieldValue,
        array $compiled,
        array &$resultArray
    ): string {
        $languageService = $this->getLanguageService();
        $mode = $this->determineMode($fieldValue, $column);
        $isPayload = $mode === FieldMapMode::PAYLOAD;
        $controlValue = $fieldValue !== '' ? $fieldValue : $this->resolveDefaultValue($fieldName, $compiled);
        $fixedName = 'fieldMapFixed[' . $fieldName . ']';
        $payloadName = 'fieldMapPayload[' . $fieldName . ']';
        $label = $this->resolveFieldLabel($fieldName, $column);
        // The payload input addresses the column as it is stored, which a label does not
        // always say: a checkbox shown inverted is labelled for the state it displays, not
        // for the value the reaction writes. The column name is therefore always beside it.
        $payloadLabelHtml = htmlspecialchars($label) . ' <code>[' . htmlspecialchars($fieldName) . ']</code>';
        $modeLabel = $languageService->sL('reactions.db:fieldMapElement.valueSource');
        $payloadId = StringUtility::getUniqueId('formengine-fieldmap-payload-');

        $control = in_array($column['config']['type'] ?? '', self::TYPES_WITHOUT_OWN_CONTROL, true)
            ? $this->renderFallbackControl($fieldName, $column, $fixedName, $isPayload ? '' : $controlValue)
            : $this->renderDedicatedControl(
                $fieldName,
                $column,
                $fixedName,
                $this->coerceControlValue($isPayload ? '' : $controlValue, $column),
                $compiled,
                $resultArray
            );

        $hintHtml = '';
        if ($this->isInvertedCheckbox($column)) {
            $hintHtml = '
                            <div class="form-text">' . htmlspecialchars($languageService->sL('reactions.db:fieldMapElement.valueSource.payload.inverted')) . '</div>';
        }

        $options = '';
        foreach (FieldMapMode::cases() as $option) {
            $options .= '<option value="' . $option->value . '"' . ($mode === $option ? ' selected' : '') . '>'
                . htmlspecialchars($languageService->sL('reactions.db:fieldMapElement.valueSource.' . $option->value)) . '</option>';
        }

        return '
            <div class="form-section">
                <typo3-reactions-field-map-row mode="' . htmlspecialchars($mode->value) . '" payload-default="' . htmlspecialchars('${' . $fieldName . '}') . '">
                    <input type="hidden" data-fieldmap-value data-formengine-validation-rules="[]"
                        name="' . htmlspecialchars($itemName . '[' . $fieldName . ']') . '"
                        value="' . htmlspecialchars($isPayload ? $fieldValue : $controlValue) . '"' . ($mode === FieldMapMode::UNSET ? ' disabled' : '') . '>
                    <div class="row g-2">
                        <div class="col" data-fieldmap-pane="fixed" data-fieldmap-source="' . htmlspecialchars($fixedName) . '"' . ($isPayload ? ' hidden' : '') . '>' . $control . '</div>
                        <div class="col" data-fieldmap-pane="payload" data-fieldmap-source="' . htmlspecialchars($payloadName) . '"' . ($isPayload ? '' : ' hidden') . '>
                            <label class="form-label" for="' . htmlspecialchars($payloadId) . '">' . $payloadLabelHtml . '</label>
                            <input type="text" class="form-control" id="' . htmlspecialchars($payloadId) . '"
                                name="' . htmlspecialchars($payloadName) . '"
                                placeholder="${path.to.value}"
                                value="' . htmlspecialchars($isPayload ? $fieldValue : '') . '">' . $hintHtml . '
                        </div>
                        <div class="col-auto">
                            <span class="form-label invisible" aria-hidden="true">' . htmlspecialchars($modeLabel) . '</span>
                            <select class="form-select" data-fieldmap-mode aria-label="' . htmlspecialchars($modeLabel . ': ' . $label) . '">
                                ' . $options . '
                            </select>
                        </div>
                    </div>
                </typo3-reactions-field-map-row>
            </div>';
    }

    protected function resolveDefaultValue(string $fieldName, array $compiled): string
    {
        $default = $compiled['databaseRow'][$fieldName] ?? '';
        if (is_array($default)) {
            $default = $default[0] ?? '';
        }
        if ($default instanceof \DateTimeInterface) {
            return $default->format(\DateTimeInterface::ATOM);
        }
        return is_scalar($default) ? (string)$default : '';
    }

    /**
     * Which of the three states a row starts in, read back off the stored map so that the
     * map stays a flat string per field.
     */
    protected function determineMode(string $fieldValue, array $column): FieldMapMode
    {
        if ($fieldValue === '') {
            return FieldMapMode::UNSET;
        }
        if (str_contains($fieldValue, '${')) {
            return FieldMapMode::PAYLOAD;
        }
        return $this->isShownByFixedControl($fieldValue, $column) ? FieldMapMode::FIXED : FieldMapMode::PAYLOAD;
    }

    protected function isShownByFixedControl(string $fieldValue, array $column): bool
    {
        $config = $column['config'];
        $type = (string)($config['type'] ?? '');
        if ($type === 'datetime') {
            return $this->parseDateTime($fieldValue) !== null;
        }
        if (!CreateRecordReactionField::hasItems($type)) {
            // A control taking a free value shows whatever is stored, so nothing about the
            // value itself says it was meant as a placeholder.
            return true;
        }
        if ($type === 'check') {
            // A checkbox holds a bitmask, so any integer is a state it can show.
            return MathUtility::canBeInterpretedAsInteger($fieldValue);
        }
        return array_any($config['items'] ?? [], fn($item) => (string)($item['value'] ?? '') === $fieldValue);
    }

    protected function coerceControlValue(string $fieldValue, array $column): mixed
    {
        if ((string)($column['config']['type'] ?? '') !== 'datetime') {
            return $fieldValue;
        }
        return $this->parseDateTime($fieldValue);
    }

    protected function parseDateTime(string $fieldValue): ?\DateTimeImmutable
    {
        if ($fieldValue === '') {
            return null;
        }
        try {
            return new \DateTimeImmutable($fieldValue);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * A checkbox declaring invertStateDisplay is labelled for
     * the state it shows rather than for the value it stores.
     */
    protected function isInvertedCheckbox(array $column): bool
    {
        if (($column['config']['type'] ?? '') !== 'check') {
            return false;
        }
        return array_any($column['config']['items'] ?? [], fn($item) => $item['invertStateDisplay'] ?? false);
    }

    protected function renderDedicatedControl(string $fieldName, array $column, string $elementName, mixed $fieldValue, array $compiled, array &$resultArray): string
    {
        $config = $column['config'];
        // The hints, wizards and controls a field declares belong to the editing form of its
        // own table. Here they would describe a record that does not exist yet, and some of
        // them refuse to render at all outside the table they were written for.
        unset($config['fieldInformation'], $config['fieldWizard'], $config['fieldControl']);
        // A link field is the exception: its element puts its own browser back in, and that
        // one is configured from the field alone rather than from the record, so it opens
        // for a row of the field map as it does for an editing form.
        // A value is optional here whatever the target table demands of it, because the
        // reaction may leave the field to its default. The rules would be validated against
        // a control the integrator cannot reach while the row takes its value from the
        // payload, and would mark the reaction record itself as invalid.
        unset($config['required'], $config['range'], $config['minitems'], $config['maxitems']);
        // A nullable column renders a checkbox activating the field, named for the record
        // it belongs to: "control[active][<target table>][0][<field>]". That record is not
        // in the datamap of the reaction, and DataHandler walks the one against the other
        // when it saves, so the reaction record could not be saved at all. The field map
        // knows two states anyway, a value and none, and "do not set" is the second.
        unset($config['nullable']);
        // The child is a field of the target table, so it is handed that table's compiled
        // data rather than this element's own. Given the reaction record instead, a wizard
        // reading the field out of the row looks it up in sys_reaction and finds nothing.
        $options = $compiled;
        $options['fieldName'] = $fieldName;
        $options['renderType'] = $config['renderType'] ?? $config['type'];
        $options['parameterArray'] = [
            'itemFormElName' => $elementName,
            'itemFormElValue' => $fieldValue,
            'fieldChangeFunc' => [],
            'fieldConf' => [
                'label' => $this->resolveFieldLabel($fieldName, $column),
                'config' => $config,
            ],
        ];
        $childResult = $this->nodeFactory->create($options)->render();
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childResult, false);
        return $childResult['html'];
    }

    protected function resolveFieldLabel(string $fieldName, array $column): string
    {
        return $this->getLanguageService()->sL((string)($column['label'] ?? '')) ?: $fieldName;
    }
}
