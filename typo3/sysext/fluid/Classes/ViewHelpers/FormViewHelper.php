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
class Tx_Fluid_ViewHelpers_FormViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {

	/**
	 * @var string
	 */
	protected $tagName = 'form';

	/**
	 * @var Tx_Fluid_Persistence_ManagerInterface
	 */
	protected $persistenceManager;

	/**
	 * Injects the Persistence Manager
	 *
	 * @param Tx_Fluid_Persistence_ManagerInterface $persistenceManager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPersistenceManager(Tx_Fluid_Persistence_ManagerInterface $persistenceManager) {
		$this->persistenceManager = $persistenceManager;
	}

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
	 * @param string $actionName Target action
	 * @param array $arguments Arguments
	 * @param string $controllerName Target controller
	 * @param string $prefixedExtensionKey Target Extension Key
	 * @param integer $pageUid Target page uid
	 * @param array $options typolink options
	 * @param mixed $object Object to use for the form. Use in conjunction with the "property" attribute on the sub tags
	 * @return string rendered form
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($actionName = NULL, array $arguments = array(), $controllerName = NULL, $prefixedExtensionKey = NULL, $pageUid = NULL, array $options = array(), $object = NULL) {
		if ($pageUid === NULL) {
			$pageUid = $GLOBALS['TSFE']->id;
		}
		$uriHelper = t3lib_div::makeInstance('Tx_Extbase_MVC_View_Helper_URIHelper'); // singleton
		$formActionUrl = $uriHelper->URIFor($pageUid, $actionName, $arguments, $controllerName, $extensionName, NULL, $options);
		$this->tag->addAttribute('action', $formActionUrl);
		
		if (strtolower($this->arguments['method']) === 'get') {
			$this->tag->addAttribute('method', 'get');
		} else {
			$this->tag->addAttribute('method', 'post');
		}

		if ($this->arguments['name']) {
			$this->variableContainer->add('__formName', $this->arguments['name']);
		}
		$hiddenIdentityFields = '';
		if (!empty($object)) {
			$this->variableContainer->add('__formObject', $this->arguments['object']);
			$hiddenIdentityFields = $this->renderHiddenIdentityField($this->arguments['object']);
		}

		$content = $hiddenIdentityFields;
		$content .= $this->renderChildren();
		$this->tag->setContent($content, FALSE);

		if (!empty($object)) {
			$this->variableContainer->remove('__formObject');
		}
		if ($this->arguments['name']) {
			$this->variableContainer->remove('__formName');
		}

		return $this->tag->render();
	}

	/**
	 * Renders a hidden form field containing the technical identity of the given object.
	 *
	 * @param object $object The object to create an identity field for
	 * @return string A hidden field containing the UUID of the given object or NULL if the object is unknown to the persistence framework
	 * @author Robert Lemke <robert@typo3.org>
	 * @see Tx_Fluid_MVC_Controller_Argument::setValue()
	 */
	protected function renderHiddenIdentityField($object) {
		$uid = $object->getUid();
		return ($uid === NULL) ? '<!-- Object of type ' . get_class($object) . ' is without identity -->' : '<input type="hidden" name="'. $this->arguments['name'] . '[uid]" value="' . $uid .'" />';
	}
}

?>


