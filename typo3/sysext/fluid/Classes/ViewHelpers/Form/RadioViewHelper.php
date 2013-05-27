<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


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
 *
 * @api
 */
class Tx_Fluid_ViewHelpers_Form_RadioViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormFieldViewHelper {

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
		$this->overrideArgument('value', 'string', 'Value of input tag. Required for radio buttons', TRUE);
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders the checkbox.
	 *
	 * @param boolean $checked Specifies that the input element should be preselected
	 *
	 * @return string
	 * @api
	 */
	public function render($checked = NULL) {
		$this->tag->addAttribute('type', 'radio');

		$nameAttribute = $this->getName();
		$valueAttribute = $this->getValue();
		if ($checked === NULL && $this->isObjectAccessorMode()) {
			$propertyValue = $this->getPropertyValue();
			// no type-safe comparisation by intention
			$checked = $propertyValue == $valueAttribute;
		}

		$this->registerFieldNameForFormTokenGeneration($nameAttribute);
		$this->tag->addAttribute('name', $nameAttribute);
		$this->tag->addAttribute('value', $valueAttribute);
		if ($checked) {
			$this->tag->addAttribute('checked', 'checked');
		}

		$this->setErrorClassAttribute();

		return $this->tag->render();
	}
}

?>