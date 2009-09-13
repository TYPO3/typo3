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
	 * @param mixed $object Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
	 * @param integer $pageType Target page type
	 * @param string $fieldNamePrefix Prefix that will be added to all field names within this form. If not set the prefix will be tx_yourExtension_plugin
	 * @param string $actionUri can be used to overwrite the "action" attribute of the form tag
	 * @return string rendered form
	 */
	public function render($action = NULL, array $arguments = array(), $controller = NULL, $extensionName = NULL, $pluginName = NULL, $pageUid = NULL, $object = NULL, $pageType = 0, $fieldNamePrefix = NULL, $actionUri = NULL) {
		if ($pageUid === NULL) {
			$pageUid = $GLOBALS['TSFE']->id;
		}

		$this->setFormActionUri();

		if (strtolower($this->arguments['method']) === 'get') {
			$this->tag->addAttribute('method', 'get');
		} else {
			$this->tag->addAttribute('method', 'post');
		}

		$this->addFormNameToViewHelperVariableContainer();
		$this->addFormObjectToViewHelperVariableContainer();
		$this->addFieldNamePrefixToViewHelperVariableContainer();

		$content = $this->renderHiddenIdentityField();
		$content .= $this->renderHiddenReferrerFields();
		$content .= $this->renderChildren();
		$this->tag->setContent($content);

		$this->removeFieldNamePrefixFromViewHelperVariableContainer();
		$this->removeFormObjectFromViewHelperVariableContainer();
		$this->removeFormNameFromViewHelperVariableContainer();

		return $this->tag->render();
	}

	/**
	 * Sets the "action" attribute of the form tag
	 *
	 * @return void
	 */
	protected function setFormActionUri() {
		if ($this->arguments->hasArgument('actionUri')) {
			$formActionUri = $this->arguments['actionUri'];
		} else {
			$uriBuilder = $this->controllerContext->getUriBuilder();
			$formActionUri = $uriBuilder
				->reset()
				->setTargetPageUid($this->arguments['pageUid'])
				->setTargetPageType($this->arguments['pageType'])
				->uriFor($this->arguments['action'], $this->arguments['arguments'], $this->arguments['controller'], $this->arguments['extensionName'], $this->arguments['pluginName']);
		}
		$this->tag->addAttribute('action', $formActionUri);
	}

	/**
	 * Renders a hidden form field containing the technical identity of the given object.
	 *
	 * @return string A hidden field containing the Identity (UUID in FLOW3, uid in Extbase) of the given object or NULL if the object is unknown to the persistence framework
	 */
	protected function renderHiddenIdentityField() {
		$object = $this->arguments['object'];
		if (!is_object($object)
			|| !($object instanceof Tx_Extbase_DomainObject_AbstractDomainObject)
			|| ($object->_isNew() && !$object->_isClone())) {
			return '';
		}
		// Intentionally NOT using PersistenceManager::getIdentifierByObject here!!
		// Using that one breaks re-submission of data in forms in case of an error.
		$identifier = $object->getUid();
		if ($identifier === NULL) {
			return chr(10) . '<!-- Object of type ' . get_class($object) . ' is without identity -->' . chr(10);
		}
		return chr(10) . '<input type="hidden" name="'. $this->prefixFieldName($this->arguments['name']) . '[__identity]" value="' . $identifier .'" />' . chr(10);
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
		$actionName = $request->getControllerActionName();

		$result = chr(10);
		$result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[extensionName]') . '" value="' . $extensionName . '" />' . chr(10);
		$result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[controllerName]') . '" value="' . $controllerName . '" />' . chr(10);
		$result .= '<input type="hidden" name="' . $this->prefixFieldName('__referrer[actionName]') . '" value="' . $actionName . '" />' . chr(10);
		return $result;
	}

	/**
	 * Adds the form name to the ViewHelperVariableContainer if the name attribute is specified.
	 *
	 * @return void
	 */
	protected function addFormNameToViewHelperVariableContainer() {
		if ($this->arguments->hasArgument('name')) {
			$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName', $this->arguments['name']);
		}
	}

	/**
	 * Removes the form name from the ViewHelperVariableContainer.
	 *
	 * @return void
	 */
	protected function removeFormNameFromViewHelperVariableContainer() {
		if ($this->arguments->hasArgument('name')) {
			$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_FormViewHelper', 'formName');
		}
	}

	/**
	 * Adds the object that is bound to this form to the ViewHelperVariableContainer if the formObject attribute is specified.
	 *
	 * @return void
	 */
	protected function addFormObjectToViewHelperVariableContainer() {
		if ($this->arguments->hasArgument('object')) {
			$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject', $this->arguments['object']);
		}
	}

	/**
	 * Removes the form object from the ViewHelperVariableContainer.
	 *
	 * @return void
	 */
	protected function removeFormObjectFromViewHelperVariableContainer() {
		if ($this->arguments->hasArgument('object')) {
			$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_FormViewHelper', 'formObject');
		}
	}

	/**
	 * Adds the field name prefix to the ViewHelperVariableContainer
	 *
	 * @return void
	 */
	protected function addFieldNamePrefixToViewHelperVariableContainer() {
		if ($this->arguments->hasArgument('fieldNamePrefix')) {
			$fieldNamePrefix = $this->arguments['fieldNamePrefix'];
		} else {
			$fieldNamePrefix = $this->getDefaultFieldNamePrefix();
		}
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $fieldNamePrefix);
	}

	/**
	 * Removes field name prefix from the ViewHelperVariableContainer
	 *
	 * @return void
	 */
	protected function removeFieldNamePrefixFromViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix');
	}

	/**
	 * Retrieves the default field name prefix for this form
	 *
	 * @return string default field name prefix
	 */
	protected function getDefaultFieldNamePrefix() {
		$request = $this->controllerContext->getRequest();
		if ($this->arguments->hasArgument('extensionName')) {
			$extensionName = $this->arguments['extensionName'];
		} else {
			$extensionName = $request->getControllerExtensionName();
		}
		if ($this->arguments->hasArgument('pluginName')) {
			$pluginName = $this->arguments['pluginName'];
		} else {
			$pluginName = $request->getPluginName();
		}

		return 'tx_' . strtolower($extensionName) . '_' . strtolower($pluginName);
	}
}

?>