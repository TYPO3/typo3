<?php
namespace TYPO3\CMS\Form\ViewHelpers;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Form\Domain\Model\Element;

/**
 * Aggregator for the select options
 */
class AggregateSelectOptionsViewHelper extends AbstractViewHelper
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $selectedValues = [];

    /**
     * @param Element $model
     * @param bool $returnSelectedValues
     * @return array
     */
    public function render(Element $model, $returnSelectedValues = false)
    {
        foreach ($model->getChildElements() as $element) {
            $this->createElement($element);
        }

        if ($returnSelectedValues === true) {
            return $this->selectedValues;
        }

        return $this->options;
    }

    /**
     * @param Element $model
     * @param array $optGroupData
     * @return void
     */
    protected function createElement(Element $model, array $optGroupData = [])
    {
        $this->checkElementForOptgroup($model, $optGroupData);
    }

    /**
     * @param Element $model
     * @param array $optGroupData
     * @return void
     */
    protected function checkElementForOptgroup(Element $model, array $optGroupData = [])
    {
        if ($model->getElementType() === 'OPTGROUP') {
            $optGroupData = [
                'label' => $model->getAdditionalArgument('label'),
                'disabled' => $model->getAdditionalArgument('disabled')
            ];
            $this->getChildElements($model, $optGroupData);
        } else {
            $optionData = [
                'value' => $model->getAdditionalArgument('value') ?: $model->getElementCounter(),
                'label' => $model->getAdditionalArgument('text'),
                'selected' => $model->getAdditionalArgument('selected'),
            ];

            if (!empty($optionData['selected'])) {
                $this->selectedValues[] = $optionData['value'];
            }

            if (count($optGroupData)) {
                $optGroupLabel = $optGroupData['label'];
                $this->options[$optGroupLabel]['disabled'] = $optGroupData['disabled'];
                $this->options[$optGroupLabel]['options'][] = $optionData;
            } else {
                $this->options[] = $optionData;
            }
        }
    }

    /**
     * @param Element $model
     * @param array $optGroupData
     * @return void
     */
    protected function getChildElements(Element $model, array $optGroupData = [])
    {
        foreach ($model->getChildElements() as $element) {
            $this->createElement($element, $optGroupData);
        }
    }
}
