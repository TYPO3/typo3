<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

/*
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
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
 * The WidgetContext stores all information a widget needs to know about the
 * environment.
 *
 * The WidgetContext can be fetched from the current WidgetRequest, and is thus
 * available throughout the whole sub-request of the widget. It is used internally
 * by various ViewHelpers (like <f:widget.link>, <f:widget.uri>, <f:widget.renderChildren>),
 * to get knowledge over the current widget's configuration.
 *
 * It is a purely internal class which should not be used outside of Fluid.
 */
class WidgetContext {

	/**
	 * Uniquely idenfies a Widget Instance on a certain page.
	 *
	 * @var string
	 */
	protected $widgetIdentifier;

	/**
	 * Per-User unique identifier of the widget, if it is an AJAX widget.
	 *
	 * @var string
	 */
	protected $ajaxWidgetIdentifier;

	/**
	 * User-supplied widget configuration, available inside the widget
	 * controller as $this->widgetConfiguration.
	 *
	 * @var array
	 */
	protected $widgetConfiguration;

	/**
	 * The fully qualified object name of the Controller which this widget uses.
	 *
	 * @var string
	 */
	protected $controllerObjectName;

	/**
	 * The child nodes of the Widget ViewHelper.
	 * Only available inside non-AJAX requests.
	 *
	 * @var \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode
	 * @transient
	 */
	protected $viewHelperChildNodes;

	/**
	 * The rendering context of the ViewHelperChildNodes.
	 * Only available inside non-AJAX requests.
	 * TODO: rename to something more meaningful.
	 *
	 * @var \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface
	 * @transient
	 */
	protected $viewHelperChildNodeRenderingContext;

	/**
	 * @var string
	 */
	protected $parentPluginNamespace;

	/**
	 * @var string
	 */
	protected $parentExtensionName;

	/**
	 * @var string
	 */
	protected $parentPluginName;

	/**
	 * @var string
	 */
	protected $widgetViewHelperClassName;

	/**
	 * @return string
	 */
	public function getWidgetIdentifier() {
		return $this->widgetIdentifier;
	}

	/**
	 * @param string $widgetIdentifier
	 * @return void
	 */
	public function setWidgetIdentifier($widgetIdentifier) {
		$this->widgetIdentifier = $widgetIdentifier;
	}

	/**
	 * @return string
	 */
	public function getAjaxWidgetIdentifier() {
		return $this->ajaxWidgetIdentifier;
	}

	/**
	 * @param string $ajaxWidgetIdentifier
	 * @return void
	 */
	public function setAjaxWidgetIdentifier($ajaxWidgetIdentifier) {
		$this->ajaxWidgetIdentifier = $ajaxWidgetIdentifier;
	}

	/**
	 * Sets the URI namespace of the plugin that contains the widget
	 *
	 * @param string $parentPluginNamespace
	 * @return void
	 */
	public function setParentPluginNamespace($parentPluginNamespace) {
		$this->parentPluginNamespace = $parentPluginNamespace;
	}

	/**
	 * Returns the URI namespace of the plugin that contains the widget
	 *
	 * @return string
	 */
	public function getParentPluginNamespace() {
		return $this->parentPluginNamespace;
	}

	/**
	 * Sets the Extension name of the plugin that contains the widget
	 *
	 * @param string $parentExtensionName
	 * @return void
	 */
	public function setParentExtensionName($parentExtensionName) {
		$this->parentExtensionName = $parentExtensionName;
	}

	/**
	 * Returns the Extension name of the plugin that contains the widget
	 *
	 * @return string
	 */
	public function getParentExtensionName() {
		return $this->parentExtensionName;
	}

	/**
	 * Sets the name of the plugin that contains the widget
	 *
	 * @param string $parentPluginName
	 * @return void
	 */
	public function setParentPluginName($parentPluginName) {
		$this->parentPluginName = $parentPluginName;
	}

	/**
	 * Returns the name of the plugin that contains the widget
	 *
	 * @return string
	 */
	public function getParentPluginName() {
		return $this->parentPluginName;
	}

	/**
	 * Sets the fully qualified class name of the view helper this context belongs to
	 *
	 * @param string $widgetViewHelperClassName
	 * @return void
	 */
	public function setWidgetViewHelperClassName($widgetViewHelperClassName) {
		$this->widgetViewHelperClassName = $widgetViewHelperClassName;
	}

	/**
	 * Returns the fully qualified class name of the view helper this context belongs to
	 *
	 * @return string
	 */
	public function getWidgetViewHelperClassName() {
		return $this->widgetViewHelperClassName;
	}

	/**
	 * @return array
	 */
	public function getWidgetConfiguration() {
		return $this->widgetConfiguration;
	}

	/**
	 * @param array $widgetConfiguration
	 * @return void
	 */
	public function setWidgetConfiguration($widgetConfiguration) {
		$this->widgetConfiguration = $widgetConfiguration;
	}

	/**
	 * @return string
	 */
	public function getControllerObjectName() {
		return $this->controllerObjectName;
	}

	/**
	 * @param string $controllerObjectName
	 * @return void
	 */
	public function setControllerObjectName($controllerObjectName) {
		$this->controllerObjectName = $controllerObjectName;
	}

	/**
	 * @param \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode $viewHelperChildNodes
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $viewHelperChildNodeRenderingContext
	 * @return void
	 */
	public function setViewHelperChildNodes(\TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode $viewHelperChildNodes, \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $viewHelperChildNodeRenderingContext) {
		$this->viewHelperChildNodes = $viewHelperChildNodes;
		$this->viewHelperChildNodeRenderingContext = $viewHelperChildNodeRenderingContext;
	}

	/**
	 * @return \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\RootNode
	 */
	public function getViewHelperChildNodes() {
		return $this->viewHelperChildNodes;
	}

	/**
	 * @return \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface
	 */
	public function getViewHelperChildNodeRenderingContext() {
		return $this->viewHelperChildNodeRenderingContext;
	}

	/**
	 * @return array
	 */
	public function __sleep() {
		return array('widgetIdentifier', 'ajaxWidgetIdentifier', 'widgetConfiguration', 'controllerObjectName', 'parentPluginNamespace', 'parentExtensionName', 'parentPluginName', 'widgetViewHelperClassName');
	}
}

?>