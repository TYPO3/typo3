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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/**
 * ViewHelper which renders a simple radio button `<input type="radio">`.
 *
 * ```
 *   <f:form.radio name="myRadioButton" value="someValue" />
 *   <f:form.radio property="newsletter" value="1" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-form-radio
 */
final class RadioViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument('checked', 'bool', 'Specifies that the input element should be preselected');
        $this->registerArgument('value', 'string', 'Value of input tag. Required for radio buttons', true);
    }

    public function render(): string
    {
        $checked = $this->arguments['checked'];

        $this->tag->addAttribute('type', 'radio');

        $nameAttribute = $this->getName();
        $valueAttribute = $this->getValueAttribute();

        $propertyValue = null;
        if ($this->hasMappingErrorOccurred()) {
            $propertyValue = $this->getLastSubmittedFormData();
        }
        if ($checked === null && $propertyValue === null) {
            $propertyValue = $this->getPropertyValue();
            $propertyValue = $this->convertToPlainValue($propertyValue);
        }

        if ($propertyValue !== null) {
            // no type-safe comparison by intention
            $checked = $propertyValue == $valueAttribute;
        }

        $this->registerFieldNameForFormTokenGeneration($nameAttribute);
        $this->tag->addAttribute('name', $nameAttribute);
        $this->tag->addAttribute('value', $valueAttribute);
        if ($checked === true) {
            $this->tag->addAttribute('checked', 'checked');
        }

        $this->setErrorClassAttribute();

        return $this->tag->render();
    }
}
