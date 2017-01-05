<?php
namespace TYPO3\CMS\Backend\Form\Wizard;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Wizard for rendering an AJAX selector for records
 *
 * This is the old implementation of the slider as wizard, that has been called
 * via "renderWizards()" method. This is no longer used and the slider implementation
 * has been integrated to the "InputTextElement" directly as config option "slider".
 *
 * @deprecated since TYPO3 v8, will be removed in TYPO3 v9
 */
class ValueSliderWizard
{
    /**
     * Renders the slider value wizard
     *
     * @param array $params
     * @return string
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function renderWizard($params)
    {
        GeneralUtility::logDeprecatedFunction();
        $field = $params['field'];
        if (is_array($params['row'][$field])) {
            $value = $params['row'][$field][0];
        } else {
            $value = $params['row'][$field];
        }
        // If Slider is used in a flexform
        if (!empty($params['flexFormPath'])) {
            $flexFormTools = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
            $flexFormValue = $flexFormTools->getArrayValueByPath($params['flexFormPath'], $params['row'][$field]);
            if ($flexFormValue !== null) {
                $value = $flexFormValue;
            }
        }
        $itemName = $params['itemName'];
        // Set default values (which correspond to those of the JS component)
        $min = 0;
        $max = 10000;
        // Use the range property, if defined, to set min and max values
        if (isset($params['fieldConfig']['range'])) {
            $min = isset($params['fieldConfig']['range']['lower']) ? (int)$params['fieldConfig']['range']['lower'] : 0;
            $max = isset($params['fieldConfig']['range']['upper']) ? (int)$params['fieldConfig']['range']['upper'] : 10000;
        }
        $elementType = $params['fieldConfig']['type'];
        $step = $params['wConf']['step'] ?: 1;
        $width = (int)$params['wConf']['width'] ?: 400;
        $type = 'null';
        if (isset($params['fieldConfig']['eval'])) {
            $eval = GeneralUtility::trimExplode(',', $params['fieldConfig']['eval'], true);
            if (in_array('int', $eval, true)) {
                $type = 'int';
                $value = (int)$value;
            } elseif (in_array('double2', $eval, true)) {
                $type = 'double';
                $value = (double)$value;
            }
        }
        if (isset($params['fieldConfig']['items'])) {
            $type = 'array';
            $index = 0;
            $itemAmount = count($params['fieldConfig']['items']);
            for (; $index < $itemAmount; ++$index) {
                $item = $params['fieldConfig']['items'][$index];
                if ((string)$item[1] === $value) {
                    break;
                }
            }
            $min = 0;
            $max = $itemAmount -1;
            $step = 1;
            $value = $index;
        }
        $callbackParams = [ $params['table'], $params['row']['uid'], $params['field'], $params['itemName'] ];
        $id = 'slider-' . $params['md5ID'];
        $content =
            '<div'
                . ' id="' . $id . '"'
                . ' data-slider-id="' . $id . '"'
                . ' data-slider-min="' . $min . '"'
                . ' data-slider-max="' . $max . '"'
                . ' data-slider-step="' . htmlspecialchars($step) . '"'
                . ' data-slider-value="' . htmlspecialchars($value) . '"'
                . ' data-slider-value-type="' . htmlspecialchars($type) . '"'
                . ' data-slider-item-name="' . htmlspecialchars($itemName) . '"'
                . ' data-slider-element-type="' . htmlspecialchars($elementType) . '"'
                . ' data-slider-callback-params="' . htmlspecialchars(json_encode($callbackParams)) . '"'
                . ' style="width: ' . $width . 'px;"'
            . '></div>';

        return $content;
    }
}
