<?php

/*
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
 * Represents a widget request.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_Widget_WidgetRequest extends Tx_Extbase_MVC_Web_Request {

	/**
	 * @var Tx_Fluid_Core_Widget_WidgetContext
	 */
	protected $widgetContext;

	/**
	 * @return Tx_Fluid_Core_Widget_WidgetContext
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getWidgetContext() {
		return $this->widgetContext;
	}

	/**
	 * @param Tx_Fluid_Core_Widget_WidgetContext $widgetContext
	 * @return void
	 */
	public function setWidgetContext(Tx_Fluid_Core_Widget_WidgetContext $widgetContext) {
		$this->widgetContext = $widgetContext;
		$this->setControllerObjectName($widgetContext->getControllerObjectName());
	}

	/**
	 * Returns the unique URI namespace for this widget in the format pluginNamespace[widgetIdentifier]
	 *
	 * @return string
	 */
	public function getArgumentPrefix() {
		return $this->widgetContext->getParentPluginNamespace() . '[' . $this->widgetContext->getWidgetIdentifier() . ']';
	}
}
?>