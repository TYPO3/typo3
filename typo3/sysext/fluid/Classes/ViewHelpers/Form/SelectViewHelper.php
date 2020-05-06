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
 * This ViewHelper generates a :html:`<select>` dropdown list for the use with a form.
 *
 * Basic usage
 * ===========
 *
 * The most straightforward way is to supply an associative array as the ``options`` parameter.
 * The array key is used as option key, and the value is used as human-readable name.
 *
 * Basic usage::
 *
 *    <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" />
 *
 * Pre select a value
 * ------------------
 *
 * To pre select a value, set ``value`` to the option key which should be selected.
 * Default value::
 *
 *    <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" value="visa" />
 *
 * Generates a dropdown box like above, except that "VISA Card" is selected.
 *
 * If the select box is a multi-select box :html:`multiple="1"`, then "value" can be an array as well.
 *
 * Custom options and option group rendering
 * -----------------------------------------
 *
 * Child nodes can be used to create a completely custom set of
 * :html:`<option>` and :html:`<optgroup>` tags in a way compatible with the
 * HMAC generation.
 * To do so, leave out the ``options`` argument and use child ViewHelpers:
 *
 * Custom options and optgroup::
 *
 *    <f:form.select name="myproperty">
 *       <f:form.select.option value="1">Option one</f:form.select.option>
 *       <f:form.select.option value="2">Option two</f:form.select.option>
 *       <f:form.select.optgroup>
 *          <f:form.select.option value="3">Grouped option one</f:form.select.option>
 *          <f:form.select.option value="4">Grouped option twi</f:form.select.option>
 *       </f:form.select.optgroup>
 *    </f:form.select>
 *
 * .. note::
 *    Do not use vanilla :html:`<option>` or :html:`<optgroup>` tags!
 *    They will invalidate the HMAC generation!
 *
 * Usage on domain objects
 * -----------------------
 *
 * If you want to output domain objects, you can just pass them as array into the ``options`` parameter.
 * To define what domain object value should be used as option key, use the ``optionValueField`` variable. Same goes for ``optionLabelField``.
 * If neither is given, the Identifier (UID/uid) and the :php:`__toString()` method are tried as fallbacks.
 *
 * If the ``optionValueField`` variable is set, the getter named after that value is used to retrieve the option key.
 * If the ``optionLabelField`` variable is set, the getter named after that value is used to retrieve the option value.
 *
 * If the ``prependOptionLabel`` variable is set, an option item is added in first position, bearing an empty string or -
 * if provided, the value of the ``prependOptionValue`` variable as value.
 *
 * Domain objects::
 *
 *    <f:form.select name="users" options="{userArray}" optionValueField="id" optionLabelField="firstName" />
 *
 * In the above example, the ``userArray`` is an array of "User" domain objects, with no array key specified.
 *
 * So, in the above example, the method :php:`$user->getId()` is called to
 * retrieve the key, and :php:`$user->getFirstName()` to retrieve the displayed
 * value of each entry.
 *
 * The ``value`` property now expects a domain object, and tests for object equivalence.
 */
class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'select';

    /**
     * @var mixed
     */
    protected $selectedValue;

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerTagAttribute('size', 'string', 'Size of input field');
        $this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
        $this->registerArgument('options', 'array', 'Associative array with internal IDs as key, and the values are displayed in the select box. Can be combined with or replaced by child f:form.select.* nodes.');
        $this->registerArgument('optionsAfterContent', 'boolean', 'If true, places auto-generated option tags after those rendered in the tag content. If false, automatic options come first.', false, false);
        $this->registerArgument('optionValueField', 'string', 'If specified, will call the appropriate getter on each object to determine the value.');
        $this->registerArgument('optionLabelField', 'string', 'If specified, will call the appropriate getter on each object to determine the label.');
        $this->registerArgument('sortByOptionLabel', 'boolean', 'If true, List will be sorted by label.', false, false);
        $this->registerArgument('selectAllByDefault', 'boolean', 'If specified options are selected if none was set before.', false, false);
        $this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this ViewHelper', false, 'f3-form-error');
        $this->registerArgument('prependOptionLabel', 'string', 'If specified, will provide an option at first position with the specified label.');
        $this->registerArgument('prependOptionValue', 'string', 'If specified, will provide an option at first position with the specified value.');
        $this->registerArgument('multiple', 'boolean', 'If set multiple options may be selected.', false, false);
        $this->registerArgument('required', 'boolean', 'If set no empty value is allowed.', false, false);
    }

    /**
     * Render the tag.
     *
     * @return string rendered tag.
     */
    public function render()
    {
        if (isset($this->arguments['required']) && $this->arguments['required']) {
            $this->tag->addAttribute('required', 'required');
        }
        $name = $this->getName();
        if (isset($this->arguments['multiple']) && $this->arguments['multiple']) {
            $this->tag->addAttribute('multiple', 'multiple');
            $name .= '[]';
        }
        $this->tag->addAttribute('name', $name);
        $options = $this->getOptions();

        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();

        $this->addAdditionalIdentityPropertiesIfNeeded();
        $this->setErrorClassAttribute();
        $content = '';

        // register field name for token generation.
        $this->registerFieldNameForFormTokenGeneration($name);
        // in case it is a multi-select, we need to register the field name
        // as often as there are elements in the box
        if (isset($this->arguments['multiple']) && $this->arguments['multiple']) {
            $content .= $this->renderHiddenFieldForEmptyValue();
            // Register the field name additional times as required by the total number of
            // options. Since we already registered it once above, we start the counter at 1
            // instead of 0.
            $optionsCount = count($options);
            for ($i = 1; $i < $optionsCount; $i++) {
                $this->registerFieldNameForFormTokenGeneration($name);
            }
            // save the parent field name so that any child f:form.select.option
            // tag will know to call registerFieldNameForFormTokenGeneration
            // this is the reason why "self::class" is used instead of static::class (no LSB)
            $viewHelperVariableContainer->addOrUpdate(
                self::class,
                'registerFieldNameForFormTokenGeneration',
                $name
            );
        }

        $viewHelperVariableContainer->addOrUpdate(self::class, 'selectedValue', $this->getSelectedValue());
        $prependContent = $this->renderPrependOptionTag();
        $tagContent = $this->renderOptionTags($options);
        $childContent = $this->renderChildren();
        $viewHelperVariableContainer->remove(self::class, 'selectedValue');
        $viewHelperVariableContainer->remove(self::class, 'registerFieldNameForFormTokenGeneration');
        if (isset($this->arguments['optionsAfterContent']) && $this->arguments['optionsAfterContent']) {
            $tagContent = $childContent . $tagContent;
        } else {
            $tagContent .= $childContent;
        }
        $tagContent = $prependContent . $tagContent;

        $this->tag->forceClosingTag(true);
        $this->tag->setContent($tagContent);
        $content .= $this->tag->render();
        return $content;
    }

    /**
     * Render prepended option tag
     *
     * @return string rendered prepended empty option
     */
    protected function renderPrependOptionTag()
    {
        $output = '';
        if ($this->hasArgument('prependOptionLabel')) {
            $value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
            $label = $this->arguments['prependOptionLabel'];
            $output .= $this->renderOptionTag($value, $label, false) . LF;
        }
        return $output;
    }

    /**
     * Render the option tags.
     *
     * @param array $options the options for the form.
     * @return string rendered tags.
     */
    protected function renderOptionTags($options)
    {
        $output = '';
        foreach ($options as $value => $label) {
            $isSelected = $this->isSelected($value);
            $output .= $this->renderOptionTag($value, $label, $isSelected) . LF;
        }
        return $output;
    }

    /**
     * Render the option tags.
     *
     * @return array an associative array of options, key will be the value of the option tag
     */
    protected function getOptions()
    {
        if (!is_array($this->arguments['options']) && !$this->arguments['options'] instanceof \Traversable) {
            return [];
        }
        $options = [];
        $optionsArgument = $this->arguments['options'];
        foreach ($optionsArgument as $key => $value) {
            if (is_object($value) || is_array($value)) {
                if ($this->hasArgument('optionValueField')) {
                    $key = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, $this->arguments['optionValueField']);
                    if (is_object($key)) {
                        if (method_exists($key, '__toString')) {
                            $key = (string)$key;
                        } else {
                            throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('Identifying value for object of class "' . get_class($value) . '" was an object.', 1247827428);
                        }
                    }
                } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                    // @todo use $this->persistenceManager->isNewObject() once it is implemented
                    $key = $this->persistenceManager->getIdentifierByObject($value);
                } elseif (method_exists($value, '__toString')) {
                    $key = (string)$value;
                } else {
                    throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('No identifying value for object of class "' . get_class($value) . '" found.', 1247826696);
                }
                if ($this->hasArgument('optionLabelField')) {
                    $value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, $this->arguments['optionLabelField']);
                    if (is_object($value)) {
                        if (method_exists($value, '__toString')) {
                            $value = (string)$value;
                        } else {
                            throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception('Label value for object of class "' . get_class($value) . '" was an object without a __toString() method.', 1247827553);
                        }
                    }
                } elseif (method_exists($value, '__toString')) {
                    $value = (string)$value;
                } elseif ($this->persistenceManager->getIdentifierByObject($value) !== null) {
                    // @todo use $this->persistenceManager->isNewObject() once it is implemented
                    $value = $this->persistenceManager->getIdentifierByObject($value);
                }
            }
            $options[$key] = $value;
        }
        if ($this->arguments['sortByOptionLabel']) {
            asort($options, SORT_LOCALE_STRING);
        }
        return $options;
    }

    /**
     * Render the option tags.
     *
     * @param mixed $value Value to check for
     * @return bool TRUE if the value should be marked a s selected; FALSE otherwise
     */
    protected function isSelected($value)
    {
        $selectedValue = $this->getSelectedValue();
        if ($value === $selectedValue || (string)$value === $selectedValue) {
            return true;
        }
        if ($this->hasArgument('multiple')) {
            if ($selectedValue === null && $this->arguments['selectAllByDefault'] === true) {
                return true;
            }
            if (is_array($selectedValue) && in_array($value, $selectedValue)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieves the selected value(s)
     *
     * @return mixed value string or an array of strings
     */
    protected function getSelectedValue()
    {
        $this->setRespectSubmittedDataValue(true);
        $value = $this->getValueAttribute();
        if (!is_array($value) && !$value instanceof \Traversable) {
            return $this->getOptionValueScalar($value);
        }
        $selectedValues = [];
        foreach ($value as $selectedValueElement) {
            $selectedValues[] = $this->getOptionValueScalar($selectedValueElement);
        }
        return $selectedValues;
    }

    /**
     * Get the option value for an object
     *
     * @param mixed $valueElement
     * @return string
     */
    protected function getOptionValueScalar($valueElement)
    {
        if (is_object($valueElement)) {
            if ($this->hasArgument('optionValueField')) {
                return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($valueElement, $this->arguments['optionValueField']);
            }
            // @todo use $this->persistenceManager->isNewObject() once it is implemented
            if ($this->persistenceManager->getIdentifierByObject($valueElement) !== null) {
                return $this->persistenceManager->getIdentifierByObject($valueElement);
            }
            return (string)$valueElement;
        }
        return $valueElement;
    }

    /**
     * Render one option tag
     *
     * @param string $value value attribute of the option tag (will be escaped)
     * @param string $label content of the option tag (will be escaped)
     * @param bool $isSelected specifies wheter or not to add selected attribute
     * @return string the rendered option tag
     */
    protected function renderOptionTag($value, $label, $isSelected)
    {
        $output = '<option value="' . htmlspecialchars($value) . '"';
        if ($isSelected) {
            $output .= ' selected="selected"';
        }
        $output .= '>' . htmlspecialchars($label) . '</option>';
        return $output;
    }
}
