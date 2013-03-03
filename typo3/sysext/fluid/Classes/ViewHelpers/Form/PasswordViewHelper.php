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
 * View Helper which creates a simple Password Text Box (<input type="password">).
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:form.password name="myPassword" />
 * </code>
 * <output>
 * <input type="password" name="myPassword" value="default value" />
 * </output>
 *
 * @api
 */
class PasswordViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormFieldViewHelper {

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
		$this->registerTagAttribute('maxlength', 'int', 'The maxlength attribute of the input field (will not be validated)');
		$this->registerTagAttribute('readonly', 'string', 'The readonly attribute of the input field');
		$this->registerTagAttribute('size', 'int', 'The size of the input field');
		$this->registerArgument('errorClass', 'string', 'CSS class to set if there are errors for this view helper', FALSE, 'f3-form-error');
		$this->registerUniversalTagAttributes();
	}

	/**
	 * Renders the password input field.
	 *
	 * @return string
	 * @api
	 */
	public function render() {
		$name = $this->getName();
		$this->registerFieldNameForFormTokenGeneration($name);

		$this->tag->addAttribute('type', 'password');
		$this->tag->addAttribute('name', $name);
		$this->tag->addAttribute('value', $this->getValue());

		$this->setErrorClassAttribute();

		return $this->tag->render();
	}
}

?>