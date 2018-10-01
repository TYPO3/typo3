<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form\Select;

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
use TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper;

/**
 * Adds custom `<option>` tags inside an `<f:form.select>`
 */
class OptionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'option';

    /**
     * Initialize additional arguments available for this tag view helper.
     */
    public function initializeArguments()
    {
        $this->registerUniversalTagAttributes();
        $this->registerArgument('selected', 'boolean', 'If set, overrides automatic detection of selected state for this option.');
        $this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.');
        $this->registerArgument('data', 'array', 'Additional data-* attributes. They will each be added with a "data-" prefix.');
        $this->registerTagAttribute('value', 'mixed', 'Value to be inserted in HTML tag - must be convertible to string!');
    }

    /**
     * @return string
     */
    public function render()
    {
        if ($this->arguments['selected'] ?? $this->isValueSelected($this->arguments['value'])) {
            $this->tag->addAttribute('selected', 'selected');
        }
        $childContent = $this->renderChildren();
        $this->tag->setContent($childContent);
        $value = $this->arguments['value'] ?? $childContent;
        $this->tag->addAttribute('value', $value);
        $parentRequestedFormTokenFieldName = $this->viewHelperVariableContainer->get(
            SelectViewHelper::class,
            'registerFieldNameForFormTokenGeneration'
        );
        if ($parentRequestedFormTokenFieldName) {
            // parent (select field) has requested this option must add one more
            // entry in the token generation registry for one additional potential
            // value of the field. Happens when "multiple" is true on parent.
            $this->registerFieldNameForFormTokenGeneration($parentRequestedFormTokenFieldName);
        }
        return $this->tag->render();
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function isValueSelected($value)
    {
        $selectedValue = $this->viewHelperVariableContainer->get(SelectViewHelper::class, 'selectedValue');
        if (is_array($selectedValue)) {
            return in_array($value, $selectedValue);
        }
        if ($selectedValue instanceof \Iterator) {
            return in_array($value, iterator_to_array($selectedValue));
        }
        return $value == $selectedValue;
    }
}
