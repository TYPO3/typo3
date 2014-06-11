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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;

/**
 * This is the base class for all widget controllers.
 * Basically, it is an ActionController, and it additionally
 * has $this->widgetConfiguration set to the Configuration of the current Widget.
 *
 * @api
 */
abstract class AbstractWidgetController extends ActionController implements SingletonInterface {

	/**
	 * @var array
	 */
	protected $supportedRequestTypes = array(WidgetRequest::class);

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
	 * @param RequestInterface $request The request object
	 * @param ResponseInterface $response The response, modified by this handler
	 * @return void
	 * @api
	 */
	public function processRequest(RequestInterface $request, ResponseInterface $response) {
		if ($request instanceof WidgetRequest) {
			$this->widgetConfiguration = $request->getWidgetContext()->getWidgetConfiguration();
		}
		parent::processRequest($request, $response);
	}

	/**
	 * Allows the widget template root path to be overridden via the framework configuration,
	 * e.g. plugin.tx_extension.view.widget.<WidgetViewHelperClassName>.templateRootPaths
	 *
	 * @param ViewInterface $view
	 * @return void
	 */
	protected function setViewConfiguration(ViewInterface $view) {
		if ($this->request instanceof WidgetRequest) {
			$extbaseFrameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $this->extensionName);
			$widgetViewHelperClassName = $this->request->getWidgetContext()->getWidgetViewHelperClassName();
			if (isset($extbaseFrameworkConfiguration['view']['widget'][$widgetViewHelperClassName])) {
				$configurationOverridden = $extbaseFrameworkConfiguration;
				$configurationOverridden['view'] = array_replace_recursive($configurationOverridden['view'], $configurationOverridden['view']['widget'][$widgetViewHelperClassName]);
				$this->configurationManager->setConfiguration($configurationOverridden);
				parent::setViewConfiguration($view);
				$this->configurationManager->setConfiguration($extbaseFrameworkConfiguration);
			} else {
				parent::setViewConfiguration($view);
			}
		}
	}

}
