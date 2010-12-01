<?php
declare(ENCODING = 'utf-8') ;

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
abstract class Tx_Fluid_Core_Widget_AbstractWidgetViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper implements Tx_Fluid_Core_ViewHelper_Facets_ChildNodeAccessInterface {

	/**
	 * The Controller associated to this widget.
	 * This needs to be filled by the individual subclass by an @inject
	 * annotation.
	 *
	 * @var Tx_Fluid_Core_Widget_AbstractWidgetController
	 * @api
	 */
	protected $controller;

	/**
	 * If set to TRUE, it is an AJAX widget.
	 *
	 * @var boolean
	 * @api
	 */
	protected $ajaxWidget = FALSE;

	/**
	 * @var Tx_Fluid_Core_Widget_AjaxWidgetContextHolder
	 */
	private $ajaxWidgetContextHolder;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	private $objectManager;

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetContext
	 */
	private $widgetContext;

	/**
	 * @param Tx_Fluid_Core_Widget_AjaxWidgetContextHolder $ajaxWidgetContextHolder
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectAjaxWidgetContextHolder(Tx_Fluid_Core_Widget_AjaxWidgetContextHolder $ajaxWidgetContextHolder) {
		$this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
	}

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->widgetContext = $this->objectManager->create('Tx_Fluid_Core_Widget_WidgetContext');
	}

	/**
	 * Initialize the arguments of the ViewHelper, and call the render() method of the ViewHelper.
	 *
	 * @param array $renderMethodParameters the parameters of the render() method.
	 * @return string the rendered ViewHelper.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArgumentsAndRender(array $renderMethodParameters) {
		$this->validateArguments();
		$this->initialize();
		$this->initializeWidgetContext();

		return $this->callRenderMethod($renderMethodParameters);
	}

	/**
	 * Initialize the Widget Context, before the Render method is called.
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	private function initializeWidgetContext() {
		$this->widgetContext->setWidgetConfiguration($this->getWidgetConfiguration());
		$this->initializeWidgetIdentifier();

		$controllerObjectName = ($this->controller instanceof Tx_Fluid_AOP_ProxyInterface) ? $this->controller->FLOW3_AOP_Proxy_getProxyTargetClassName() : get_class($this->controller);
		$this->widgetContext->setControllerObjectName($controllerObjectName);

		$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		$pluginName = $this->controllerContext->getRequest()->getPluginName();
		$this->widgetContext->setParentExtensionName($extensionName);
		$this->widgetContext->setParentPluginName($pluginName);
		$pluginNamespace = Tx_Extbase_Utility_Extension::getPluginNamespace($extensionName, $pluginName);
		$this->widgetContext->setParentPluginNamespace($pluginNamespace);

		$this->widgetContext->setWidgetViewHelperClassName(get_class($this));
		if ($this->ajaxWidget === TRUE) {
			$this->ajaxWidgetContextHolder->store($this->widgetContext);
		}
	}

	/**
	 * Stores the syntax tree child nodes in the Widget Context, so they can be
	 * rendered with <f:widget.renderChildren> lateron.
	 *
	 * @param array $childNodes The SyntaxTree Child nodes of this ViewHelper.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setChildNodes(array $childNodes) {
		$rootNode = $this->objectManager->create('Tx_Fluid_Core_Parser_SyntaxTree_RootNode');
		foreach ($childNodes as $childNode) {
			$rootNode->addChildNode($childNode);
		}
		$this->widgetContext->setViewHelperChildNodes($rootNode, $this->getRenderingContext());
	}

	/**
	 * Generate the configuration for this widget. Override to adjust.
	 *
	 * @return array
	 * @api
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function getWidgetConfiguration() {
		return $this->arguments;
	}

	/**
	 * Initiate a sub request to $this->controller. Make sure to fill $this->controller
	 * via Dependency Injection.
	 *
	 * @return Tx_Extbase_MVC_Response the response of this request.
	 * @api
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function initiateSubRequest() {
		if (!($this->controller instanceof Tx_Fluid_Core_Widget_AbstractWidgetController)) {
			if (isset($this->controller)) {
				throw new Tx_Fluid_Core_Widget_Exception_MissingControllerException('initiateSubRequest() can not be called if there is no valid controller extending Tx_Fluid_Core_Widget_AbstractWidgetController. Got "' . get_class($this->controller) . '" in class "' . get_class($this) . '".', 1289422564);
			}
			throw new Tx_Fluid_Core_Widget_Exception_MissingControllerException('initiateSubRequest() can not be called if there is no controller inside $this->controller. Make sure to add a corresponding injectController method to your WidgetViewHelper class "' . get_class($this) . '".', 1284401632);
		}

		$subRequest = $this->objectManager->create('Tx_Fluid_Core_Widget_WidgetRequest');
		$subRequest->setWidgetContext($this->widgetContext);
		$this->passArgumentsToSubRequest($subRequest);

		$subResponse = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');
		$this->controller->processRequest($subRequest, $subResponse);
		return $subResponse;
	}

	/**
	 * Pass the arguments of the widget to the subrequest.
	 *
	 * @param Tx_Fluid_Core_Widget_WidgetRequest $subRequest
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	private function passArgumentsToSubRequest(Tx_Fluid_Core_Widget_WidgetRequest $subRequest) {
		$arguments = $this->controllerContext->getRequest()->getArguments();
		$widgetIdentifier = $this->widgetContext->getWidgetIdentifier();
		if (isset($arguments[$widgetIdentifier])) {
			if (isset($arguments[$widgetIdentifier]['action'])) {
				$subRequest->setControllerActionName($arguments[$widgetIdentifier]['action']);
				unset($arguments[$widgetIdentifier]['action']);
			}
			$subRequest->setArguments($arguments[$widgetIdentifier]);
		}
	}

	/**
	 * The widget identifier is unique on the current page, and is used
	 * in the URI as a namespace for the widget's arguments.
	 *
	 * @return string the widget identifier for this widget
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @return void
	 * @todo clean up, and make it somehow more routing compatible.
	 */
	private function initializeWidgetIdentifier() {
		if (!$this->viewHelperVariableContainer->exists('Tx_Fluid_Core_Widget_AbstractWidgetViewHelper', 'nextWidgetNumber')) {
			$widgetCounter = 0;
		} else {
			$widgetCounter = $this->viewHelperVariableContainer->get('Tx_Fluid_Core_Widget_AbstractWidgetViewHelper', 'nextWidgetNumber');
		}
		$widgetIdentifier = '__widget_' . $widgetCounter;
		$this->viewHelperVariableContainer->addOrUpdate('Tx_Fluid_Core_Widget_AbstractWidgetViewHelper', 'nextWidgetNumber', $widgetCounter + 1);

		$this->widgetContext->setWidgetIdentifier($widgetIdentifier);
	}
}

?>