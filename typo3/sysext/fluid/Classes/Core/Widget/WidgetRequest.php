<?php

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

namespace TYPO3\CMS\Fluid\Core\Widget;

use TYPO3\CMS\Extbase\Mvc\Request;

/**
 * Represents a widget request.
 * @internal It is a purely internal class which should not be used outside of Fluid.
 */
class WidgetRequest extends Request
{
    /**
     * @var string The requested representation format
     */
    protected $format = 'html';

    /**
     * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    protected $widgetContext;

    /**
     * @return \TYPO3\CMS\Fluid\Core\Widget\WidgetContext
     */
    public function getWidgetContext()
    {
        return $this->widgetContext;
    }

    /**
     * @param \TYPO3\CMS\Fluid\Core\Widget\WidgetContext $widgetContext
     */
    public function setWidgetContext(WidgetContext $widgetContext)
    {
        $this->widgetContext = $widgetContext;
        $this->setControllerObjectName($widgetContext->getControllerObjectName());
    }

    /**
     * Returns the unique URI namespace for this widget in the format pluginNamespace[widgetIdentifier]
     *
     * @return string
     */
    public function getArgumentPrefix()
    {
        return $this->widgetContext->getParentPluginNamespace() . '[' . $this->widgetContext->getWidgetIdentifier() . ']';
    }
}
