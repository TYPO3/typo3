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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Generation of TCEform elements where no rendering could be found
 */
class NoneElement extends AbstractFormElement
{
    /**
     * This will render a non-editable display of the content of the field.
     *
     * @return string The HTML code for the TCEform field
     */
    public function render()
    {
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $itemValue = $parameterArray['itemFormElValue'];

        if ($config['format']) {
            $itemValue = $this->formatValue($config, $itemValue);
        }
        if (!$config['pass_content']) {
            $itemValue = htmlspecialchars($itemValue);
        }

        $resultArray = $this->initializeResultArray();
        $rows = (int)$config['rows'];
        // Render as textarea
        if ($rows > 1 || $config['type'] === 'text') {
            $cols = MathUtility::forceIntegerInRange($config['cols'] ?: $this->defaultInputWidth, 5, $this->maxInputWidth);
            $width = $this->formMaxWidth($cols);
            $html = '
				<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px"' : '') . '>
					<textarea class="form-control" rows="' . $rows . '" disabled>' . $itemValue . '</textarea>
				</div>';
        } else {
            $cols = $config['cols'] ?: ($config['size'] ?: $this->defaultInputWidth);
            $size = MathUtility::forceIntegerInRange($cols ?: $this->defaultInputWidth, 5, $this->maxInputWidth);
            $width = $this->formMaxWidth($size);
            $html = '
				<div class="form-control-wrap"' . ($width ? ' style="max-width: ' . $width . 'px"' : '') . '>
					<input class="form-control" value="' . $itemValue . '" type="text" disabled>
				</div>';
        }
        $resultArray['html'] = $html;
        return $resultArray;
    }

    /**
     * Format field content if $config['format'] is set to date, filesize, ..., user
     *
     * @param array $config Configuration for the display
     * @param string $itemValue The value to display
     * @return string Formatted field value
     */
    protected function formatValue($config, $itemValue)
    {
        $format = trim($config['format']);
        switch ($format) {
            case 'date':
                if ($itemValue) {
                    $option = isset($config['format.']['option']) ? trim($config['format.']['option']) : '';
                    if ($option) {
                        if (isset($config['format.']['strftime']) && $config['format.']['strftime']) {
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
                if (isset($config['format.']['appendAge']) && $config['format.']['appendAge']) {
                    $age = BackendUtility::calcAge(
                        $GLOBALS['EXEC_TIME'] - $itemValue,
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')
                    );
                    $value .= ' (' . $age . ')';
                }
                $itemValue = $value;
                break;
            case 'datetime':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && !is_null($itemValue)) {
                    $itemValue = date('H:i d-m-Y', (int)$itemValue);
                }
                break;
            case 'time':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && !is_null($itemValue)) {
                    $itemValue = date('H:i', (int)$itemValue);
                }
                break;
            case 'timesec':
                // compatibility with "eval" (type "input")
                if ($itemValue !== '' && !is_null($itemValue)) {
                    $itemValue = date('H:i:s', (int)$itemValue);
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
                $base = isset($config['format.']['base']) ? trim($config['format.']['base']) : '';
                $format = isset($baseArr[$base]) ? $baseArr[$base] : 'd';
                $itemValue = sprintf('%' . $format, $itemValue);
                break;
            case 'float':
                // default precision
                $precision = 2;
                if (isset($config['format.']['precision'])) {
                    $precision = MathUtility::forceIntegerInRange($config['format.']['precision'], 1, 10, $precision);
                }
                $itemValue = sprintf('%.' . $precision . 'f', $itemValue);
                break;
            case 'number':
                $format = isset($config['format.']['option']) ? trim($config['format.']['option']) : '';
                $itemValue = sprintf('%' . $format, $itemValue);
                break;
            case 'md5':
                $itemValue = md5($itemValue);
                break;
            case 'filesize':
                // We need to cast to int here, otherwise empty values result in empty output,
                // but we expect zero.
                $value = GeneralUtility::formatSize((int)$itemValue);
                if (!empty($config['format.']['appendByteSize'])) {
                    $value .= ' (' . $itemValue . ')';
                }
                $itemValue = $value;
                break;
            case 'user':
                $func = trim($config['format.']['userFunc']);
                if ($func) {
                    $params = [
                        'value' => $itemValue,
                        'args' => $config['format.']['userFunc'],
                        'config' => $config,
                    ];
                    $itemValue = GeneralUtility::callUserFunction($func, $params, $this);
                }
                break;
            default:
                // Do nothing e.g. when $format === ''
        }
        return $itemValue;
    }
}
