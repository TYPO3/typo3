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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form\Select;

use TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper;
use TYPO3\CMS\Fluid\ViewHelpers\Form\SelectViewHelper;

/**
 * ViewHelper for adding custom `<option>` tags inside a `<f:form.select>`.
 *
 * ```
 *   <f:form.select name="mySelect">
 *     <f:form.select.option value="1">Option one</f:form.select.option>
 *     <f:form.select.optgroup>
 *       <f:form.select.option value="3">Grouped option one</f:form.select.option>
 *       <f:form.select.option value="4">Grouped option two</f:form.select.option>
 *     </f:form.select.optgroup>
 *   </f:form.select>>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select-option
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-select
 */
final class OptionViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'option';

    public function initializeArguments(): void
    {
        $this->registerArgument('selected', 'boolean', 'If set, overrides automatic detection of selected state for this option.');
        $this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.');
        $this->registerArgument('data', 'array', 'Additional data-* attributes. They will each be added with a "data-" prefix.');
        $this->registerArgument('value', 'mixed', 'Value to be inserted in HTML tag - must be convertible to string!');
    }

    public function render(): string
    {
        $childContent = $this->renderChildren();
        $this->tag->setContent((string)$childContent);
        $value = $this->arguments['value'] ?? $childContent;
        if ($this->arguments['selected'] ?? $this->isValueSelected((string)$value)) {
            $this->tag->addAttribute('selected', 'selected');
        }
        $this->tag->addAttribute('value', $value);
        $parentRequestedFormTokenFieldName = $this->renderingContext->getViewHelperVariableContainer()->get(
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

    protected function isValueSelected(string $value): bool
    {
        $selectedValue = $this->renderingContext->getViewHelperVariableContainer()->get(SelectViewHelper::class, 'selectedValue');
        if (is_array($selectedValue)) {
            return in_array($value, array_map(strval(...), $selectedValue), true);
        }
        if ($selectedValue instanceof \Iterator) {
            return in_array($value, array_map(strval(...), iterator_to_array($selectedValue)), true);
        }
        return $value === (string)$selectedValue;
    }
}
