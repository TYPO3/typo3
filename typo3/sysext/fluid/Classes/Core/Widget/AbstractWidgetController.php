<?php

/*
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
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
 * This is the base class for all widget controllers.
 * Basically, it is an ActionController, and it additionally
 * has $this->widgetConfiguration set to the Configuration of the current Widget.
 *
 * @api
 */
abstract class Tx_Fluid_Core_Widget_AbstractWidgetController extends Tx_Extbase_MVC_Controller_ActionController implements t3lib_Singleton {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array('Tx_Fluid_Core_Widget_WidgetRequest');

	/**
	 * Configuration for this widget.
	 *
	 * @var array
	 * @api
	 */
	protected $widgetConfiguration;

	/**
	 * Handles a request. The result output is returned by altering the given response.
	 *
	 * @param Tx_Extbase_MVC_RequestInterface $request The request object
	 * @param Tx_Extbase_MVC_ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @api
	 */
	public function processRequest(Tx_Extbase_MVC_RequestInterface $request, Tx_Extbase_MVC_ResponseInterface $response) {
		$this->widgetConfiguration = $request->getWidgetContext()->getWidgetConfiguration();
		parent::processRequest($request, $response);
	}

	/**
	 * Allows the widget template root path to be overriden via the framework configuration,
	 * e.g. plugin.tx_extension.view.widget.<WidgetViewHelperClassName>.templateRootPath
	 *
	 * @param Tx_Extbase_MVC_View_ViewInterface $view
	 * @return void
	 */
	protected function setViewConfiguration(Tx_Extbase_MVC_View_ViewInterface $view) {
		$extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		$widgetViewHelperClassName = $this->request->getWidgetContext()->getWidgetViewHelperClassName();

		if (isset($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName]['templateRootPath'])
			&& strlen($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName]['templateRootPath']) > 0
			&& method_exists($view, 'setTemplateRootPath')) {
			$view->setTemplateRootPath(t3lib_div::getFileAbsFileName($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName]['templateRootPath']));
		}
	}
}

?>