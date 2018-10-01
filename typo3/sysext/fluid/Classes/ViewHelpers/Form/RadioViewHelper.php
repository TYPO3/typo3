<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

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
 * View Helper which creates a simple radio button (<input type="radio">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.radio name="myRadioButton" value="someValue" />
 * </code>
 * <output>
 * <input type="radio" name="myRadioButton" value="someValue" />
 * </output>
 *
 * <code title="Preselect">
 * <f:form.radio name="myRadioButton" value="someValue" checked="{object.value} == 5" />
 * </code>
 * <output>
 * <input type="radio" name="myRadioButton" value="someValue" checked="checked" />
 * (depending on $object)
 * </output>
 *
 * <code title="Bind to object property">
 * <f:form.radio property="newsletter" value="1" /> yes
 * <f:form.radio property="newsletter" value="0" /> no
 * </code>
 * <output>
 * <input type="radio" name="user[newsletter]" value="1" checked="checked" /> yes
 * <input type="radio" name="user[newsletter]" value="0" /> no
 * (depending on property "newsletter")
 * </output>
 */
class RadioViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    /**
     * Initialize the arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument(
            'errorClass',
            'string',
            'CSS class to set if there are errors for this view helper',
            false,
            'f3-form-error'
        );
        $this->registerArgument('checked', 'bool', 'Specifies that the input element should be preselected');
        $this->overrideArgument('value', 'string', 'Value of input tag. Required for radio buttons', true);
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute(
            'disabled',
            'string',
            'Specifies that the input element should be disabled when the page loads'
        );
    }

    /**
     * Renders the checkbox.
     *
     * @return string
     */
    public function render()
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
