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

/**
 * Render elements of type radio
 */
class RadioElement extends AbstractFormElement
{
    /**
     * This will render a series of radio buttons.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $disabled = '';
        if ($this->data['parameterArray']['fieldConf']['config']['readOnly']) {
            $disabled = ' disabled';
        }

        $html = [];
        foreach ($this->data['parameterArray']['fieldConf']['config']['items'] as $itemNumber => $itemLabelAndValue) {
            $label =  $itemLabelAndValue[0];
            $value = $itemLabelAndValue[1];
            $radioId = htmlspecialchars($this->data['parameterArray']['itemFormElID'] . '_' . $itemNumber);
            $radioChecked = (string)$value === (string)$this->data['parameterArray']['itemFormElValue'] ? ' checked="checked"' : '';
            $html[] = '<div class="radio' . $disabled . '">';
            $html[] =    '<label for="' . $radioId . '">';
            $html[] =        '<input type="radio"'
                                . ' name="' . htmlspecialchars($this->data['parameterArray']['itemFormElName']) . '"'
                                . ' id="' . $radioId . '"'
                                . ' value="' . htmlspecialchars($value) . '"'
                                . $radioChecked
                                . $this->data['parameterArray']['onFocus']
                                . $disabled
                                . ' onclick="' . htmlspecialchars(implode('', $this->data['parameterArray']['fieldChangeFunc'])) . '"'
                            . '/>'
                            . htmlspecialchars($label);
            $html[] =    '</label>';
            $html[] = '</div>';
        }

        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }
}
