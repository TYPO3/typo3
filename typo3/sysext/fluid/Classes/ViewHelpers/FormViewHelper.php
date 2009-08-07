<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: FormViewHelper.php 2177 2009-04-22 22:52:02Z bwaidelich $
 */

/**
 * Form view helper. Generates a <form> Tag.
 *
 * = Basic usage =
 *
 * Use <f:form> to output an HTML <form> tag which is targeted at the specified action, in the current controller and package.
 * It will submit the form data via a POST request. If you want to change this, use method="get" as an argument.
 * <code title="Example">
 * <f:form action="...">...</f:form>
 * </code>
 *
 * = A complex form with a specified encoding type =
 *
 * <code title="Form with enctype set">
 * <f:form action=".." controller="..." package="..." enctype="multipart/form-data">...</f:form>
 * </code>
 *
 * = A Form which should render a domain object =
 *
 * <code title="Binding a domain object to a form">
 * <f:form action="..." name="customer" object="{customer}">
 *   <f:form.hidden property="id" />
 *   <f:form.textbox property="name" />
 * </f:form>
 * </code>
 * This automatically inserts the value of {customer.name} inside the textbox and adjusts the name of the textbox accordingly.
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: FormViewHelper.php 2177 2009-04-22 22:52:02Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_FormViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'form';

	/**
	 * Initialize arguments.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		$this->registerTagAttribute('enctype', 'string', 'MIME type with which the form is submitted');
		$this->registerTagAttribute('method', 'string', 'Transfer type (GET or POST)');
		$this->registerTagAttribute('name', 'string', 'Name of form');
		$this->registerTagAttribute('onreset', 'string', 'JavaScript: On reset of the form');
		$this->registerTagAttribute('onsubmit', 'string', 'JavaScript: On submit of the form');

		$this->registerUniversalTagAttributes();
	}

	/**
	 * Render the form.
	 *
	 * @param string $action Target action
	 * @param array $arguments Arguments
	 * @param string $controller Target controller
	 * @param string $extensionName Target Extension Name (without "tx_" prefix and no underscores). If NULL the current extension name is used
	 * @param string $pluginName Target plugin. If empty, the current plugin name is used
	 * @param integer $pageUid Target page uid
	 * @param array $options typolink options
	 * @param mixed $object Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
	 * @param integer $pageType Target page type
	 * @return string rendered form
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $pageUid = NULL, array $options = array(), $object = NULL, $pageType = 0) {
		if ($pageUid === NULL) {
			$pageUid = $GLOBALS['TSFE']->id;
		}
		$URIBuilder = $this->controllerContext->getURIBuilder();
		$formActionUrl = $URIBuilder->URIFor($pageUid, $action, $arguments, $controller, $extensionName, $pluginName, $options, $pageType);
		$this->tag->addAttribute('action', $formActionUrl);

		if (strtolower($this->arguments['method']) === 'get') {
			$this->tag->addAttribute('method', 'get');
		} else {
			$this->tag->addAttribute('method', 'post');
		}

		if ($this->arguments['name']) {
			$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName', $this->arguments['name']);
		}
		$hiddenIdentityFields = '';
		if (!empty($object)) {
			$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject', $object);
			$hiddenIdentityFields = $this->renderHiddenIdentityField($object);
		}

		$content = $hiddenIdentityFields;
		$content .= $this->renderHiddenReferrerFields();
		$content .= $this->renderChildren();
		$this->tag->setContent($content);

		if (!empty($object)) {
			$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject');
		}
		if ($this->arguments['name']) {
			$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName');
		}

		return $this->tag->render();
	}

	/**
	 * Renders a hidden form field containing the technical identity of the given object.
	 *
	 * @param object $object The object to create an identity field for
	 * @return string A hidden field containing the Identity (UUID in FLOW3, uid in Extbase) of the given object or NULL if the object is unknown to the persistence framework
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function renderHiddenIdentityField($object) {
		if (!is_object($object)
			|| !($object instanceof Tx_Extbase_DomainObject_AbstractDomainObject)
			|| ($object->_isNew() && !$object->_isClone())) {
			return '';
		}
		// Intentionally NOT using PersistenceManager::getIdentifierByObject here!!
		// Using that one breaks re-submission of data in forms in case of an error.
		$identifier = $object->getUid();
		return ($identifier === NULL) ? '<!-- Object of type ' . get_class($object) . ' is without identity -->' : '<input type="hidden" name="'. $this->arguments['name'] . '[uid]" value="' . $identifier .'" />';
	}

	/**
	 * Renders hidden form fields for referrer information about
	 * the current controller and action.
	 *
	 * @return string Hidden fields with referrer information
	 * @todo filter out referrer information that is equal to the target (e.g. same packageKey)
	 */
	protected function renderHiddenReferrerFields() {
		$request = $this->controllerContext->getRequest();
		$extensionName = $request->getControllerExtensionName();
		$controllerName = $request->getControllerName();
		$controllerActionName = $request->getControllerActionName();
		$result = '';
		foreach (array('__referrer[extensionName]' => $extensionName, '__referrer[controllerName]' => $controllerName, '__referrer[actionName]' => $controllerActionName) as $fieldName => $fieldValue) {
			$result .= PHP_EOL . '<input type="hidden" name="' . $fieldName . '" value="' . $fieldValue . '" />';
		}
		return $result;
	}
}

?>