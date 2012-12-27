<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * This view helper generates a <select> dropdown list for the use with a form.
 *
 * = Basic usage =
 *
 * The most straightforward way is to supply an associative array as the "options" parameter.
 * The array key is used as option key, and the value is used as human-readable name.
 *
 * <code title="Basic usage">
 * <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" />
 * </code>
 *
 * = Pre-select a value =
 *
 * To pre-select a value, set "value" to the option key which should be selected.
 * <code title="Default value">
 * <f:form.select name="paymentOptions" options="{payPal: 'PayPal International Services', visa: 'VISA Card'}" value="visa" />
 * </code>
 * Generates a dropdown box like above, except that "VISA Card" is selected.
 *
 * If the select box is a multi-select box (multiple="true"), then "value" can be an array as well.
 *
 * = Usage on domain objects =
 *
 * If you want to output domain objects, you can just pass them as array into the "options" parameter.
 * To define what domain object value should be used as option key, use the "optionValueField" variable. Same goes for optionLabelField.
 * If neither is given, the Identifier (UID/uid) and the __toString() method are tried as fallbacks.
 *
 * If the optionValueField variable is set, the getter named after that value is used to retrieve the option key.
 * If the optionLabelField variable is set, the getter named after that value is used to retrieve the option value.
 *
 * If the prependOptionLabel variable is set, an option item is added in first position, bearing an empty string or -
 * If provided, the value of the prependOptionValue variable as value.
 *
 * <code title="Domain objects">
 * <f:form.select name="users" options="{userArray}" optionValueField="id" optionLabelField="firstName" />
 * </code>
 * In the above example, the userArray is an array of "User" domain objects, with no array key specified.
 *
 * So, in the above example, the method $user->getId() is called to retrieve the key, and $user->getFirstName() to retrieve the displayed value of each entry.
 *
 * The "value" property now expects a domain object, and tests for object equivalence.
 *
 * @api
 */
class SelectViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'select';

	/**
	 * @var mixed
	 */
	protected $selectedValue = NULL;

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('multiple', 'string', 'if set, multiple select field');
		$this->registerTagAttribute('size', 'string', 'Size of input field');
		$this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
		$this->registerArgument('options', 'array', 'Associative array with internal IDs as key, and the values are displayed in the select box', TRUE);
		$this->registerArgument('optionValueField', 'string', 'If specified, will call the appropriate getter on each object to determine the value.');
		$this->registerArgument('optionLabelField', 'string', 'If specified, will call the appropriate getter on each object to determine the label.');
		$this->registerArgument('sortByOptionLabel', 'boolean', 'If true, List will be sorted by label.', FALSE, FALSE);
		$this->registerArgument('selectAllByDefault', 'boolean', 'If specified options are selected if none was set before.', FALSE, FALSE);
		$this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', FALSE, 'f3-form-error');
		$this->registerArgument('prependOptionLabel', 'string', 'If specified, will provide an option at first position with the specified label.');
		$this->registerArgument('prependOptionValue', 'string', 'If specified, will provide an option at first position with the specified value.');
	}

	/**
	 * Render the tag.
	 *
	 * @return string rendered tag.
	 * @api
	 */
	public function render() {
		$name = $this->getName();
		if ($this->hasArgument('multiple')) {
			$name .= '[]';
		}
		$this->tag->addAttribute('name', $name);
		$options = $this->getOptions();
		if (empty($options)) {
			$options = array('' => '');
		}
		$this->tag->setContent($this->renderOptionTags($options));
		$this->setErrorClassAttribute();
		$content = '';
		// register field name for token generation.
		// in case it is a multi-select, we need to register the field name
		// as often as there are elements in the box
		if ($this->hasArgument('multiple') && $this->arguments['multiple'] !== '') {
			$content .= $this->renderHiddenFieldForEmptyValue();
			for ($i = 0; $i < count($options); $i++) {
				$this->registerFieldNameForFormTokenGeneration($name);
			}
		} else {
			$this->registerFieldNameForFormTokenGeneration($name);
		}
		$content .= $this->tag->render();
		return $content;
	}

	/**
	 * Render the option tags.
	 *
	 * @param array $options the options for the form.
	 * @return string rendered tags.
	 */
	protected function renderOptionTags($options) {
		$output = '';
		if ($this->hasArgument('prependOptionLabel')) {
			$value = $this->hasArgument('prependOptionValue') ? $this->arguments['prependOptionValue'] : '';
			$label = $this->arguments['prependOptionLabel'];
			$output .= $this->renderOptionTag($value, $label, FALSE) . chr(10);
		}
		foreach ($options as $value => $label) {
			$isSelected = $this->isSelected($value);
			$output .= $this->renderOptionTag($value, $label, $isSelected) . chr(10);
		}
		return $output;
	}

	/**
	 * Render the option tags.
	 *
	 * @return array an associative array of options, key will be the value of the option tag
	 */
	protected function getOptions() {
		if (!is_array($this->arguments['options']) && !$this->arguments['options'] instanceof \Traversable) {
			return array();
		}
		$options = array();
		$optionsArgument = $this->arguments['options'];
		foreach ($optionsArgument as $key => $value) {
			if (is_object($value)) {
				if ($this->hasArgument('optionValueField')) {
					$key = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, $this->arguments['optionValueField']);
					if (is_object($key)) {
						if (method_exists($key, '__toString')) {
							$key = (string) $key;
						} else {
							throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Identifying value for object of class "' . get_class($value) . '" was an object.', 1247827428);
						}
					}
				// TODO: use $this->persistenceManager->isNewObject() once it is implemented
				} elseif ($this->persistenceManager->getIdentifierByObject($value) !== NULL) {
					$key = $this->persistenceManager->getIdentifierByObject($value);
				} elseif (method_exists($value, '__toString')) {
					$key = (string) $value;
				} else {
					throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('No identifying value for object of class "' . get_class($value) . '" found.', 1247826696);
				}
				if ($this->hasArgument('optionLabelField')) {
					$value = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($value, $this->arguments['optionLabelField']);
					if (is_object($value)) {
						if (method_exists($value, '__toString')) {
							$value = (string) $value;
						} else {
							throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Label value for object of class "' . get_class($value) . '" was an object without a __toString() method.', 1247827553);
						}
					}
				} elseif (method_exists($value, '__toString')) {
					$value = (string) $value;
				// TODO: use $this->persistenceManager->isNewObject() once it is implemented
				} elseif ($this->persistenceManager->getIdentifierByObject($value) !== NULL) {
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
	 * @return boolean TRUE if the value should be marked a s selected; FALSE otherwise
	 */
	protected function isSelected($value) {
		$selectedValue = $this->getSelectedValue();
		if ($value === $selectedValue || (string) $value === $selectedValue) {
			return TRUE;
		}
		if ($this->hasArgument('multiple')) {
			if (is_null($selectedValue) && $this->arguments['selectAllByDefault'] === TRUE) {
				return TRUE;
			} elseif (is_array($selectedValue) && in_array($value, $selectedValue)) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Retrieves the selected value(s)
	 *
	 * @return mixed value string or an array of strings
	 */
	protected function getSelectedValue() {
		$value = $this->getValue();
		if (!is_array($value) && !$value instanceof \Traversable) {
			return $this->getOptionValueScalar($value);
		}
		$selectedValues = array();
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
	protected function getOptionValueScalar($valueElement) {
		if (is_object($valueElement)) {
			if ($this->hasArgument('optionValueField')) {
				return \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getPropertyPath($valueElement, $this->arguments['optionValueField']);
			} else {
				// TODO: use $this->persistenceManager->isNewObject() once it is implemented
				if ($this->persistenceManager->getIdentifierByObject($valueElement) !== NULL) {
					return $this->persistenceManager->getIdentifierByObject($valueElement);
				} else {
					return (string) $valueElement;
				}
			}
		} else {
			return $valueElement;
		}
	}

	/**
	 * Render one option tag
	 *
	 * @param string $value value attribute of the option tag (will be escaped)
	 * @param string $label content of the option tag (will be escaped)
	 * @param boolean $isSelected specifies wheter or not to add selected attribute
	 * @return string the rendered option tag
	 */
	protected function renderOptionTag($value, $label, $isSelected) {
		$output = '<option value="' . htmlspecialchars($value) . '"';
		if ($isSelected) {
			$output .= ' selected="selected"';
		}
		$output .= '>' . htmlspecialchars($label) . '</option>';
		return $output;
	}
}

?>
