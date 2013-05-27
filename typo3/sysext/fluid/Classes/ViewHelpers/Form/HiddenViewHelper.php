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
 * Renders an <input type="hidden" ...> tag.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.hidden name="myHiddenValue" value="42" />
 * </code>
 * <output>
 * <input type="hidden" name="myHiddenValue" value="42" />
 * </output>
 *
 * You can also use the "property" attribute if you have bound an object to the form.
 * See <f:form> for more documentation.
 *
 * @api
 */
class Tx_Fluid_ViewHelpers_Form_HiddenViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormFieldViewHelper {

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
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders the hidden field.
	 *
	 * @return string
	 * @api
	 */
	public function render() {
		$name = $this->getName();
		$this->registerFieldNameForFormTokenGeneration($name);

		$this->tag->addAttribute('type', 'hidden');
		$this->tag->addAttribute('name', $name);
		$this->tag->addAttribute('value', $this->getValue());

		return $this->tag->render();
	}
}


?>
