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
 * Represents a widget request.
 */
class WidgetRequest extends \TYPO3\CMS\Extbase\Mvc\Web\Request
{
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
     * @return void
     */
    public function setWidgetContext(\TYPO3\CMS\Fluid\Core\Widget\WidgetContext $widgetContext)
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
