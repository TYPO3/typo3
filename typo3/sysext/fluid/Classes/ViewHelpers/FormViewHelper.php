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
 * @version $Id: FormViewHelper.php 2049 2010-03-16 10:51:45Z sebastian $
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
 * @version $Id: FormViewHelper.php 2049 2010-03-16 10:51:45Z sebastian $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_FormViewHelper extends Tx_Fluid_ViewHelpers_Form_AbstractFormViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'form';

	/**
	 * @var Tx_Extbase_Security_Channel_RequestHashService
	 */
	protected $requestHashService;

	/**
	 * We need the arguments of the formActionUri on requesthash calculation
	 * therefore we will store them in here right after calling uriBuilder
	 *
	 * @var array
	 */
	protected $formActionUriArguments;

	/**
	 * Inject a request hash service
	 *
	 * @param Tx_Extbase_Security_Channel_RequestHashService $requestHashService The request hash service
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectRequestHashService(Tx_Extbase_Security_Channel_RequestHashService $requestHashService) {
		$this->requestHashService = $requestHashService;
	}

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
		$this->setFormActionUri();

		if (strtolower($this->arguments['method']) === 'get') {
			$this->tag->addAttribute('method', 'get');
		} else {
			$this->tag->addAttribute('method', 'post');
		}

		$this->addFormNameToViewHelperVariableContainer();
		$this->addFormObjectToViewHelperVariableContainer();
		$this->addFieldNamePrefixToViewHelperVariableContainer();
		$this->addFormFieldNamesToViewHelperVariableContainer();

		$formContent = $this->renderChildren();

		$content = $this->renderHiddenIdentityField($this->arguments['object'], $this->arguments['name']);
		$content .= $this->renderAdditionalIdentityFields();
		$content .= $this->renderHiddenReferrerFields();
		$content .= $this->renderRequestHashField(); // Render hmac after everything else has been rendered
		$content .= $formContent;

		$this->tag->setContent($content);

		$this->removeFieldNamePrefixFromViewHelperVariableContainer();
		$this->removeFormObjectFromViewHelperVariableContainer();
		$this->removeFormNameFromViewHelperVariableContainer();
		$this->removeFormFieldNamesFromViewHelperVariableContainer();
		$this->removeCheckboxFieldNamesFromViewHelperVariableContainer();

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
			$this->formActionUriArguments = $uriBuilder->getArguments();
		}
		$this->tag->addAttribute('action', $formActionUri);
	}

	/**
	 * Render additional identity fields which were registered by form elements.
	 * This happens if a form field is defined like property="bla.blubb" - then we might need an identity property for the sub-object "bla".
	 *
	 * @return string HTML-string for the additional identity properties
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function renderAdditionalIdentityFields() {
		if ($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties')) {
			$additionalIdentityProperties = $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties');
			$output = '';
			foreach ($additionalIdentityProperties as $identity) {
				$output .= chr(10) . $identity;
			}
			return $output;
		}
		return '';
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
			$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties', array());
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
			$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_FormViewHelper', 'additionalIdentityProperties');
		}
	}

	/**
	 * Adds the field name prefix to the ViewHelperVariableContainer
	 *
	 * @return void
	 */
	protected function addFieldNamePrefixToViewHelperVariableContainer() {
		$fieldNamePrefix = $this->getFieldNamePrefix();
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'fieldNamePrefix', $fieldNamePrefix);
	}

	/**
	 * Get the field name prefix
	 *
	 * @return string
	 */
	protected function getFieldNamePrefix() {
		if ($this->arguments->hasArgument('fieldNamePrefix')) {
			return $this->arguments['fieldNamePrefix'];
		} else {
			return $this->getDefaultFieldNamePrefix();
		}
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
	 * Adds a container for form field names to the ViewHelperVariableContainer
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function addFormFieldNamesToViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->add('Tx_Fluid_ViewHelpers_FormViewHelper', 'formFieldNames', array());
	}

	/**
	 * Removes the container for form field names from the ViewHelperVariableContainer
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function removeFormFieldNamesFromViewHelperVariableContainer() {
		$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_FormViewHelper', 'formFieldNames');
	}

	/**
	 * Render the request hash field
	 *
	 * @return string the hmac field
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function renderRequestHashField() {
		$formFieldNames = $this->viewHelperVariableContainer->get('Tx_Fluid_ViewHelpers_FormViewHelper', 'formFieldNames');
		$this->postProcessUriArgumentsForRequesthash($this->formActionUriArguments, $formFieldNames);
		$requestHash = $this->requestHashService->generateRequestHash($formFieldNames, $this->getFieldNamePrefix());
		// in v4, we need to prefix __hmac as well to make it show up in the request object.
		return '<input type="hidden" name="' . $this->prefixFieldName('__hmac') . '" value="' . htmlspecialchars($requestHash) . '" />';
	}

	/**
	 * Add the URI arguments after postprocessing to the request hash as well.
	 */
	protected function postProcessUriArgumentsForRequestHash($arguments, &$results, $currentPrefix = '', $level = 0) {
		if (!count($arguments)) return;
		foreach ($arguments as $argumentName => $argumentValue) {
			if (is_array($argumentValue)) {
				$prefix = ($level==0 ? $argumentName : $currentPrefix . '[' . $argumentName . ']');
				$this->postProcessUriArgumentsForRequestHash($argumentValue, $results, $prefix, $level+1);
			} else {
				$results[] = ($level==0 ? $argumentName : $currentPrefix . '[' . $argumentName . ']');
			}
		}
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
	
	/**
	 * Remove Checkbox field names from ViewHelper variable container, to start from scratch when a new form starts.
	 */
	protected function removeCheckboxFieldNamesFromViewHelperVariableContainer() {
		if ($this->viewHelperVariableContainer->exists('Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper', 'checkboxFieldNames')) {
			$this->viewHelperVariableContainer->remove('Tx_Fluid_ViewHelpers_Form_CheckboxViewHelper', 'checkboxFieldNames');
		}
	}
}

?>