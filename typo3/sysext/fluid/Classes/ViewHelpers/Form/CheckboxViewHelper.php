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
 * ViewHelper which creates a simple checkbox :html:`<input type="checkbox">`.
 *
 * Examples
 * ========
 *
 * Simple one
 * ----------
 *
 * ::
 *
 *    <f:form.checkbox name="myCheckBox" value="someValue" />
 *
 * Output::
 *
 *    <input type="checkbox" name="myCheckBox" value="someValue" />
 *
 * Preselect
 * ---------
 *
 * ::
 *
 *    <f:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />
 *
 * Output::
 *
 *    <input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
 *
 * Depending on bound ``object`` to surrounding :ref:`f:form <typo3-fluid-form>`.
 *
 * Bind to object property
 * -----------------------
 *
 * ::
 *
 *    <f:form.checkbox property="interests" value="TYPO3" />
 *
 * Output::
 *
 *    <input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
 *
 * Depending on property ``interests``.
 */
class CheckboxViewHelper extends AbstractFormFieldViewHelper
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
        $this->registerTagAttribute(
            'disabled',
            'string',
            'Specifies that the input element should be disabled when the page loads'
        );
        $this->registerArgument(
            'errorClass',
            'string',
            'CSS class to set if there are errors for this ViewHelper',
            false,
            'f3-form-error'
        );
        $this->overrideArgument('value', 'string', 'Value of input tag. Required for checkboxes', true);
        $this->registerUniversalTagAttributes();
        $this->registerArgument('checked', 'bool', 'Specifies that the input element should be preselected');
        $this->registerArgument('multiple', 'bool', 'Specifies whether this checkbox belongs to a multivalue (is part of a checkbox group)', false, false);
    }

    /**
     * Renders the checkbox.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     * @return string
     */
    public function render()
    {
        $checked = $this->arguments['checked'];
        $multiple = $this->arguments['multiple'];

        $this->tag->addAttribute('type', 'checkbox');

        $nameAttribute = $this->getName();
        $valueAttribute = $this->getValueAttribute();
        $propertyValue = null;
        if ($this->hasMappingErrorOccurred()) {
            $propertyValue = $this->getLastSubmittedFormData();
        }
        if ($checked === null && $propertyValue === null) {
            $propertyValue = $this->getPropertyValue();
        }

        if ($propertyValue instanceof \Traversable) {
            $propertyValue = iterator_to_array($propertyValue);
        }
        if (is_array($propertyValue)) {
            $propertyValue = array_map([$this, 'convertToPlainValue'], $propertyValue);
            if ($checked === null) {
                $checked = in_array($valueAttribute, $propertyValue);
            }
            $nameAttribute .= '[]';
        } elseif ($multiple === true) {
            $nameAttribute .= '[]';
        } elseif ($propertyValue !== null) {
            $checked = (boolean)$propertyValue === (boolean)$valueAttribute;
        }

        $this->registerFieldNameForFormTokenGeneration($nameAttribute);
        $this->tag->addAttribute('name', $nameAttribute);
        $this->tag->addAttribute('value', $valueAttribute);
        if ($checked === true) {
            $this->tag->addAttribute('checked', 'checked');
        }

        $this->setErrorClassAttribute();
        $hiddenField = $this->renderHiddenFieldForEmptyValue();
        return $hiddenField . $this->tag->render();
    }
}
