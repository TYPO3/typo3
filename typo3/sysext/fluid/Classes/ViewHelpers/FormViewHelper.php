<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: FormViewHelper.php 2102 2009-03-27 17:28:57Z robert $
 */

/**
 * Form view helper. Generates a <form> Tag.
 *
 * Example
 *
 * (1) Basic usage
 *
 * <f3:form action="...">...</f3:form>
 * Outputs an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 * It will submit the form data via a GET request. If you want to change this, use method="post" as an argument.
 *
 *
 * (2) A complex form with a specified encoding type (needed for file uploads)
 *
 * <f3:form action=".." controller="..." package="..." method="post" enctype="multipart/form-data">...</f3:form>
 *
 *
 * (3) A complex form which should render a domain object.
 *
 * <f3:form action="..." name="customer" object="{customer}">
 *   <f3:form.hidden property="id" />
 *   <f3:form.textbox property="name" />
 * </f3:form>
 * This automatically inserts the value of {customer.name} inside the textbox and adjusts the name of the textbox accordingly.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: FormViewHelper.php 2102 2009-03-27 17:28:57Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_FormViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {

	/**
	 * @var	Tx_Extbase_MVC_Web_URIHelper
	 */
	protected $URIHelper;

	public function __construct(array $arguments = array()) {
		$this->URIHelper = t3lib_div::makeInstance('Tx_Extbase_MVC_View_Helper_URIHelper');
	}

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		$this->registerTagAttribute('name', 'string', 'Name of blog');
		$this->registerTagAttribute('enctype', 'string', 'MIME type with which the form is submitted');
		$this->registerTagAttribute('method', 'string', 'Transfer type (GET or POST)');
		$this->registerTagAttribute('onreset', 'string', 'JavaScript: On reset of the form');
		$this->registerTagAttribute('onsubmit', 'string', 'JavaScript: On submit of the form');

		$this->registerUniversalTagAttributes();
	}

	/**
	 * Render the form.
	 *
	 * @param object $object Object to bind the form to.
	 * @param string $page Target page
	 * @param string $action Target action
	 * @param string $controller Target controller
	 * @param string $extension Target Extension name
	 * @param string $anchor Anchor
	 * @param array $arguments Arguments
	 * @return string FORM-Tag.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render($object = NULL, $page = NULL, $action = NULL, $controller = NULL, $extension = NULL, $anchor = NULL, $arguments = array()) {
		$request = $this->variableContainer->get('view')->getRequest();
		$this->URIHelper = t3lib_div::makeInstance('Tx_Extbase_MVC_View_Helper_URIHelper');
		
		$method = ( $this->arguments['method'] ? $this->arguments['method'] : 'POST' );

		$formActionUrl = $this->URIHelper->URIFor($action, $arguments, $controller, $page, $extension, array('section' => $anchor, 'useCacherHash' => 0));

		$hiddenIdentityFields = '';
		if ($object !== NULL) {
			$this->variableContainer->add('__formObject', $object);
			$hiddenIdentityFields = $this->generateHiddenIdentityFields($object);
		}

		$this->variableContainer->add('__formName', 'tx_' . strtolower($request->getControllerExtensionName()) . '_' . strtolower($request->getPluginKey()));

		$out = '<form method="' . $method . '" action="' . $formActionUrl . '" ' . $this->renderTagAttributes() . '>';
		$out .= $hiddenIdentityFields;
		$out .= $this->renderChildren();
		$out .= '</form>';

		if ($object) {
			$this->variableContainer->remove('__formObject');
		}

		$this->variableContainer->remove('__formName');

		return $out;
	}

	/**
	 *			// <![CDATA[<f:form.hidden name="updatedBlog[__identity][name]" value="{blog.name}" />]]>
	 *
	 */
	protected function generateHiddenIdentityFields($object) {
//		if ($this->persistenceManager->getBackend()->isNewObject($object)) return '';
/*
		$classSchema = $this->persistenceManager->getClassSchema($object);
		foreach (array_keys($classSchema->getIdentityProperties()) as $propertyName) {
			$propertyValue = Tx_Fluid_Reflection_ObjectAccess::getProperty($object, $propertyName);
		}
*/
	}
}

?>
