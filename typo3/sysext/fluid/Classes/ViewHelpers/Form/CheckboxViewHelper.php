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
 * View Helper which creates a simple checkbox (<input type="checkbox">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.checkbox name="myCheckBox" value="someValue" />
 * </code>
 * <output>
 * <input type="checkbox" name="myCheckBox" value="someValue" />
 * </output>
 *
 * <code title="Preselect">
 * <f:form.checkbox name="myCheckBox" value="someValue" checked="{object.value} == 5" />
 * </code>
 * <output>
 * <input type="checkbox" name="myCheckBox" value="someValue" checked="checked" />
 * (depending on $object)
 * </output>
 *
 * <code title="Bind to object property">
 * <f:form.checkbox property="interests" value="TYPO3" />
 * </code>
 * <output>
 * <input type="checkbox" name="user[interests][]" value="TYPO3" checked="checked" />
 * (depending on property "interests")
 * </output>
 *
 * @api
 */
class CheckboxViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'input';

	/**
	 * Initialize the arguments.
	 *
	 * @return void
	 * @api
	 */
	public function initializeArguments() {
		parent::initializeArguments();
		$this->registerTagAttribute('disabled', 'string', 'Specifies that the input element should be disabled when the page loads');
		$this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', FALSE, 'f3-form-error');
		$this->overrideArgument('value', 'string', 'Value of input tag. Required for checkboxes', TRUE);
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders the checkbox.
	 *
	 * @param boolean $checked Specifies that the input element should be preselected
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 * @return string
	 * @api
	 */
	public function render($checked = NULL) {
		$this->tag->addAttribute('type', 'checkbox');

		$nameAttribute = $this->getName();
		$valueAttribute = $this->getValue();
		if ($this->isObjectAccessorMode()) {
			if ($this->hasMappingErrorOccurred()) {
				$propertyValue = $this->getLastSubmittedFormData();
			} else {
				$propertyValue = $this->getPropertyValue();
			}

			if ($propertyValue instanceof \Traversable) {
				$propertyValue = iterator_to_array($propertyValue);
			}
			if (is_array($propertyValue)) {
				if ($checked === NULL) {
					$checked = in_array($valueAttribute, $propertyValue);
				}
				$nameAttribute .= '[]';
			} elseif (($multiple = FALSE) === TRUE) {
				// @todo: implement correct as in Flow.Fluid
				$nameAttribute .= '[]';
			} elseif ($checked === NULL && $propertyValue !== NULL) {
				$checked = (boolean) $propertyValue === (boolean) $valueAttribute;
			}
		}
		$this->registerFieldNameForFormTokenGeneration($nameAttribute);
		$this->tag->addAttribute('name', $nameAttribute);
		$this->tag->addAttribute('value', $valueAttribute);
		if ($checked) {
			$this->tag->addAttribute('checked', 'checked');
		}
		$this->setErrorClassAttribute();
		$hiddenField = $this->renderHiddenFieldForEmptyValue();
		return $hiddenField . $this->tag->render();
	}
}
