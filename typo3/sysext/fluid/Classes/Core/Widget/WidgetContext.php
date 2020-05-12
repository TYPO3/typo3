<?php
namespace TYPO3\CMS\Fluid\Core\Widget;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * The WidgetContext stores all information a widget needs to know about the
 * environment.
 *
 * The WidgetContext can be fetched from the current WidgetRequest, and is thus
 * available throughout the whole sub-request of the widget. It is used internally
 * by various ViewHelpers (like <f:widget.link>, <f:widget.uri>, <f:widget.renderChildren>),
 * to get knowledge over the current widget's configuration.
 *
 * @internal It is a purely internal class which should not be used outside of Fluid.
 */
class WidgetContext implements \JsonSerializable
{
    protected const JSON_SERIALIZABLE_PROPERTIES = [
        'widgetIdentifier',
        'ajaxWidgetIdentifier',
        'widgetConfiguration',
        'controllerObjectName',
        'parentPluginNamespace',
        'parentVendorName',
        'parentExtensionName',
        'parentPluginName',
        'widgetViewHelperClassName',
    ];

    /**
     * Uniquely identifies a Widget Instance on a certain page.
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
     * controller as $this->widgetConfiguration. Array items must be
     * null, scalar, array or implement \JsonSerializable interface.
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
     * @var \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode
     */
    protected $viewHelperChildNodes;

    /**
     * The rendering context of the ViewHelperChildNodes.
     * Only available inside non-AJAX requests.
     * @todo rename to something more meaningful.
     *
     * @var \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
     */
    protected $viewHelperChildNodeRenderingContext;

    /**
     * @var string
     */
    protected $parentPluginNamespace;

    /**
     * @var string
     */
    protected $parentVendorName;

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

    public static function fromArray(array $data): WidgetContext
    {
        $target = new WidgetContext();
        foreach ($data as $propertyName => $propertyValue) {
            if (!in_array($propertyName, self::JSON_SERIALIZABLE_PROPERTIES, true)) {
                continue;
            }
            if (!property_exists($target, $propertyName)) {
                continue;
            }
            $target->{$propertyName} = $propertyValue;
        }
        return $target;
    }

    public function jsonSerialize()
    {
        $properties = array_fill_keys(self::JSON_SERIALIZABLE_PROPERTIES, true);
        $data = array_intersect_key(get_object_vars($this), $properties);

        if (is_array($data['widgetConfiguration'] ?? null)) {
            $data['widgetConfiguration'] = array_filter(
                $data['widgetConfiguration'],
                function ($value): bool {
                    if ($value === null || is_scalar($value) || is_array($value)
                        || is_object($value) && $value instanceof \JsonSerializable
                    ) {
                        return true;
                    }
                    throw new Exception(
                        sprintf(
                            'Widget configuration items either must be null, scalar, array or JSON serializable, got "%s"',
                            is_object($value) ? get_class($value) : gettype($value)
                        ),
                        1588178538
                    );
                }
            );
        } else {
            $data['widgetConfiguration'] = null;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getWidgetIdentifier()
    {
        return $this->widgetIdentifier;
    }

    /**
     * @param string $widgetIdentifier
     */
    public function setWidgetIdentifier($widgetIdentifier)
    {
        $this->widgetIdentifier = $widgetIdentifier;
    }

    /**
     * @return string
     */
    public function getAjaxWidgetIdentifier()
    {
        return $this->ajaxWidgetIdentifier;
    }

    /**
     * @param string $ajaxWidgetIdentifier
     */
    public function setAjaxWidgetIdentifier($ajaxWidgetIdentifier)
    {
        $this->ajaxWidgetIdentifier = $ajaxWidgetIdentifier;
    }

    /**
     * Sets the URI namespace of the plugin that contains the widget
     *
     * @param string $parentPluginNamespace
     */
    public function setParentPluginNamespace($parentPluginNamespace)
    {
        $this->parentPluginNamespace = $parentPluginNamespace;
    }

    /**
     * Returns the URI namespace of the plugin that contains the widget
     *
     * @return string
     */
    public function getParentPluginNamespace()
    {
        return $this->parentPluginNamespace;
    }

    /**
     * Sets the Extension name of the plugin that contains the widget
     *
     * @param string $parentExtensionName
     */
    public function setParentExtensionName($parentExtensionName)
    {
        $this->parentExtensionName = $parentExtensionName;
    }

    /**
     * Returns the Extension name of the plugin that contains the widget
     *
     * @return string
     */
    public function getParentExtensionName()
    {
        return $this->parentExtensionName;
    }

    /**
     * Sets the Vendor name of the plugin that contains the widget
     *
     * @param string $parentVendorName
     */
    public function setParentVendorName($parentVendorName)
    {
        $this->parentVendorName = $parentVendorName;
    }

    /**
     * Returns the Vendor name of the plugin that contains the widget
     *
     * @return string
     */
    public function getParentVendorName()
    {
        return $this->parentVendorName;
    }

    /**
     * Sets the name of the plugin that contains the widget
     *
     * @param string $parentPluginName
     */
    public function setParentPluginName($parentPluginName)
    {
        $this->parentPluginName = $parentPluginName;
    }

    /**
     * Returns the name of the plugin that contains the widget
     *
     * @return string
     */
    public function getParentPluginName()
    {
        return $this->parentPluginName;
    }

    /**
     * Sets the fully qualified class name of the view helper this context belongs to
     *
     * @param string $widgetViewHelperClassName
     */
    public function setWidgetViewHelperClassName($widgetViewHelperClassName)
    {
        $this->widgetViewHelperClassName = $widgetViewHelperClassName;
    }

    /**
     * Returns the fully qualified class name of the view helper this context belongs to
     *
     * @return string
     */
    public function getWidgetViewHelperClassName()
    {
        return $this->widgetViewHelperClassName;
    }

    /**
     * @return array
     */
    public function getWidgetConfiguration()
    {
        return $this->widgetConfiguration;
    }

    /**
     * @param array $widgetConfiguration
     */
    public function setWidgetConfiguration($widgetConfiguration)
    {
        $this->widgetConfiguration = $widgetConfiguration;
    }

    /**
     * @return string
     */
    public function getControllerObjectName()
    {
        return $this->controllerObjectName;
    }

    /**
     * @param string $controllerObjectName
     */
    public function setControllerObjectName($controllerObjectName)
    {
        $this->controllerObjectName = $controllerObjectName;
    }

    /**
     * @param \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode $viewHelperChildNodes
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $viewHelperChildNodeRenderingContext
     */
    public function setViewHelperChildNodes(\TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode $viewHelperChildNodes, \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $viewHelperChildNodeRenderingContext)
    {
        $this->viewHelperChildNodes = $viewHelperChildNodes;
        $this->viewHelperChildNodeRenderingContext = $viewHelperChildNodeRenderingContext;
    }

    /**
     * @return \TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\RootNode
     */
    public function getViewHelperChildNodes()
    {
        return $this->viewHelperChildNodes;
    }

    /**
     * @return \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface
     */
    public function getViewHelperChildNodeRenderingContext()
    {
        return $this->viewHelperChildNodeRenderingContext;
    }
}
