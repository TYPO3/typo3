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
 * Builds the WidgetRequest if an AJAX widget is called.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_Core_Widget_WidgetRequestBuilder extends Tx_Extbase_MVC_Web_RequestBuilder {

	/**
	 * @var Tx_Fluid_Core_Widget_AjaxWidgetContextHolder
	 */
	private $ajaxWidgetContextHolder;

	/**
	 * @param Tx_Fluid_Core_Widget_AjaxWidgetContextHolder $ajaxWidgetContextHolder
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectAjaxWidgetContextHolder(Tx_Fluid_Core_Widget_AjaxWidgetContextHolder $ajaxWidgetContextHolder) {
		$this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
	}

	/**
	 * Builds a widget request object from the raw HTTP information
	 *
	 * @return Tx_Fluid_Core_Widget_WidgetRequest The widget request as an object
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function build() {
		$request = $this->objectManager->create('Tx_Fluid_Core_Widget_WidgetRequest');
		$request->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		$request->setMethod((isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : NULL);
		if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
			$request->setArguments(t3lib_div::_POST());
		} else {
			$request->setArguments(t3lib_div::_GET());
		}

		$rawGetArguments = t3lib_div::_GET();
			// TODO: rename to @action, to be consistent with normal naming?
		if (isset($rawGetArguments['action'])) {
			$request->setControllerActionName($rawGetArguments['action']);
		}

		$widgetContext = $this->ajaxWidgetContextHolder->get($rawGetArguments['fluid-widget-id']);
		$request->setWidgetContext($widgetContext);
		return $request;
	}
}

?>