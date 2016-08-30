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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Generation of TCEform elements of the type "text"
 */
class TextElement extends AbstractFormElement
{
    /**
     * The number of chars expected per row when the height of a text area field is
     * automatically calculated based on the number of characters found in the field content.
     *
     * @var int
     */
    protected $charactersPerRow = 40;

    /**
     * This will render a <textarea>
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();
        $backendUser = $this->getBackendUserAuthentication();

        $config = $parameterArray['fieldConf']['config'];

        // Setting columns number
        $cols = MathUtility::forceIntegerInRange($config['cols'] ?: $this->defaultInputWidth, $this->minimumInputWidth, $this->maxInputWidth);

        // Setting number of rows
        $rows = MathUtility::forceIntegerInRange($config['rows'] ?: 5, 1, 20);
        $originalRows = $rows;

        $itemFormElementValueLength = strlen($parameterArray['itemFormElValue']);
        if ($itemFormElementValueLength > $this->charactersPerRow * 2) {
            $cols = $this->maxInputWidth;
            $rows = MathUtility::forceIntegerInRange(
                round($itemFormElementValueLength / $this->charactersPerRow),
                count(explode(LF, $parameterArray['itemFormElValue'])),
                20
            );
            if ($rows < $originalRows) {
                $rows = $originalRows;
            }
        }

        // must be called after the cols and rows calculation, so the parameters are applied
        // to read-only fields as well.
        // @todo: Same as in InputTextElement ...
        if ($config['readOnly']) {
            $config['cols'] = $cols;
            $config['rows'] = $rows;
            $options = $this->data;
            $options['parameterArray'] = [
                'fieldConf' => [
                    'config' => $config,
                ],
                'itemFormElValue' => $parameterArray['itemFormElValue'],
            ];
            $options['renderType'] = 'none';
            return $this->nodeFactory->create($options)->render();
        }

        $evalList = GeneralUtility::trimExplode(',', $config['eval'], true);
        // "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
        $specialConfiguration = BackendUtility::getSpecConfParts($parameterArray['fieldConf']['defaultExtras']);
        $html = '';

        // Show message, if no RTE (field can only be edited with RTE!)
        if ($specialConfiguration['rte_only']) {
            $html = '<p><em>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noRTEfound')) . '</em></p>';
        } else {
            $attributes = [];
            // validation
            foreach ($evalList as $func) {
                if ($func === 'required') {
                    $attributes['data-formengine-validation-rules'] = $this->getValidationDataAsJsonString(['required' => true]);
                } else {
                    // @todo: This is ugly: The code should find out on it's own whether a eval definition is a
                    // @todo: keyword like "date", or a class reference. The global registration could be dropped then
                    // Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
                    // There is a similar hook for "evaluateFieldValue" in DataHandler and InputTextElement
                    if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func])) {
                        if (class_exists($func)) {
                            $evalObj = GeneralUtility::makeInstance($func);
                            if (method_exists($evalObj, 'deevaluateFieldValue')) {
                                $_params = [
                                    'value' => $parameterArray['itemFormElValue']
                                ];
                                $parameterArray['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
                            }
                        }
                    }
                }
            }

            // calculate classes
            $classes = [];
            $classes[] = 'form-control';
            $classes[] = 't3js-formengine-textarea';
            $classes[] = 'formengine-textarea';
            if ($specialConfiguration['fixed-font']) {
                $classes[] = 'text-monospace';
            }
            if ($specialConfiguration['enable-tab']) {
                $classes[] = 't3js-enable-tab';
            }

            // calculate styles
            $styles = [];
            // add the max-height from the users' preference to it
            $maximumHeight = (int)$backendUser->uc['resizeTextareas_MaxHeight'];
            if ($maximumHeight > 0) {
                $styles[] = 'max-height: ' . $maximumHeight . 'px';
            }

            // calculate attributes
            $attributes['id'] = StringUtility::getUniqueId('formengine-textarea-');
            $attributes['name'] = htmlspecialchars($parameterArray['itemFormElName']);
            $attributes['data-formengine-input-name'] = htmlspecialchars($parameterArray['itemFormElName']);
            if (!empty($styles)) {
                $attributes['style'] = implode(' ', $styles);
            }
            if (!empty($classes)) {
                $attributes['class'] = implode(' ', $classes);
            }
            $attributes['rows'] = $rows;
            $attributes['wrap'] = $specialConfiguration['nowrap'] ? 'off' : ($config['wrap'] ?: 'virtual');
            $attributes['onChange'] = implode('', $parameterArray['fieldChangeFunc']);
            if (isset($config['max']) && (int)$config['max'] > 0) {
                $attributes['maxlength'] = (int)$config['max'];
            }
            $attributeString = '';
            foreach ($attributes as $attributeName => $attributeValue) {
                $attributeString .= ' ' . $attributeName . '="' . htmlspecialchars($attributeValue) . '"';
            }

            // Build the textarea
            $placeholderAttribute = '';
            if (!empty($config['placeholder'])) {
                $placeholderAttribute = ' placeholder="' . htmlspecialchars(trim($config['placeholder'])) . '" ';
            }

            $html .= '<textarea'
                . $attributeString
                . $placeholderAttribute
                . $parameterArray['onFocus']
                . '>' . htmlspecialchars($parameterArray['itemFormElValue']) . '</textarea>';

            // Wrap a wizard around the item?
            $html = $this->renderWizards(
                [$html],
                $config['wizards'],
                $table,
                $row,
                $fieldName,
                $parameterArray,
                $parameterArray['itemFormElName'],
                $specialConfiguration,
                false
            );

            $maximumWidth = (int)$this->formMaxWidth($cols);
            $html = '<div class="form-control-wrap"' . ($maximumWidth ? ' style="max-width: ' . $maximumWidth . 'px"' : '') . '>' . $html . '</div>';
        }

        $resultArray['html'] = $html;
        return $resultArray;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
