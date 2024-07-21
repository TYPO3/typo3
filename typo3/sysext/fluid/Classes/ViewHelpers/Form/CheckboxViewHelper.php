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
 * ViewHelper which creates a simple checkbox :html:`<input type="checkbox">`.
 *
 * It **must** only be used in :ref:`Extbase <t3coreapi:extbase>` context within the
 * :ref:`f:form <t3viewhelper:typo3-fluid-form>` tag. It **cannot** be used
 * within a standard :ref:`FLUIDTEMPLATE <t3tsref:cobj-template>` or a non-Extbase
 * plugin.
 *
 * ..  versionchanged:: 12.0
 *     The `<f:form>` ViewHelper where initially build to be only used in Extbase.
 *     Nevertheless until TYPO3 v12.0 it was possible to use them in a non-Extbase
 *     context, for example a :ref:`FLUIDTEMPLATE <t3tsref:cobj-template>`.
 *
 *     With :ref:`Breaking: #98377 - Fluid StandaloneView does not create an
 *     Extbase Request anymore <changelog:breaking-98377-1663607123>` using them
 *     in a non-Extbase context now throws an error.
 *
 *     See :ref:`Migration <f-form-checkbox-migration>` for details.
 *
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
 *    <f:form.checkbox property="interests" value="TYPO3" multiple="1" />
 *
 * Output::
 *
 *    <input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
 *
 * Depending on property ``interests``.
 * 
 * ..  _f-form-checkbox-migration:
 *
 * Migration: Remove f:form.checkbox ViewHelper in non-Extbase context
 * ===================================================================
 *
 * Starting with TYPO3 v12.0 it is not possible to use the f:form.checkbox
 * ViewHelper in a non-Extbase context anymore. These can be safely replaced
 * by standard HTML `<input>` tags.
 *
 * ..  code-block:: diff
 *     :caption: Migration of a preselected ViewHelper in a non-Extbase context
 *
 *     - <f:form.checkbox name="myCheckBox" value="someValue"
 *     -   checked="{object.value} == 5" />
 *     + <input type="checkbox" name="myCheckBox" id="myCheckBox" value="someValue"
 *     +   checked="{f:if(condition:'{object.value} == 5', then:'checked')}" />
 */
final class CheckboxViewHelper extends AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'input';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument(
            'errorClass',
            'string',
            'CSS class to set if there are errors for this ViewHelper',
            false,
            'f3-form-error'
        );
        $this->overrideArgument('value', 'string', 'Value of input tag. Required for checkboxes', true);
        $this->registerArgument('checked', 'bool', 'Specifies that the input element should be preselected');
        $this->registerArgument('multiple', 'bool', 'Specifies whether this checkbox belongs to a multivalue (is part of a checkbox group)', false, false);
    }

    public function render(): string
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
            $propertyValue = array_map($this->convertToPlainValue(...), $propertyValue);
            if ($checked === null) {
                $checked = in_array($valueAttribute, $propertyValue);
            }
            $nameAttribute .= '[]';
        } elseif ($multiple === true) {
            $nameAttribute .= '[]';
        } elseif ($propertyValue !== null) {
            $checked = (bool)$propertyValue === (bool)$valueAttribute;
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
