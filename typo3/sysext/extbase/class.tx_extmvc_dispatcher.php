<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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

// TODO these statements become obsolete with the new autoloader -> remove them

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Web/TX_EXTMVC_Web_RequestBuilder.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_Request.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Web/TX_EXTMVC_Web_Request.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_Response.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Web/TX_EXTMVC_Web_Response.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_AbstractController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_RequestHandlingController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_ActionController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/View/TX_EXTMVC_View_AbstractView.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_Session.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/Mapper/TX_EXTMVC_Persistence_Mapper_TcaMapper.php');

// FIXME
require_once(t3lib_extMgm::extPath('blogexample') . 'Classes/Controller/TX_Blogexample_Controller_PostsController.php');
require_once(t3lib_extMgm::extPath('blogexample') . 'Classes/View/TX_Blogexample_View_PostsIndex.php');
require_once(t3lib_extMgm::extPath('blogexample') . 'Classes/View/TX_Blogexample_View_PostsShow.php');

/**
 * Creates a request an dispatches it to the controller which was specified by TS Setup, Flexform,
 * or Extension Configuration (ExtConf), and returns the content to the v4 framework.
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Dispatcher {

	/**
	 * @var TX_EXTMVC_Configuration_Manager A reference to the configuration manager
	 */
	protected $configurationManager;

	/**
	 * @var TX_EXTMVC_Web_RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var ArrayObject The raw GET parameters
	 */
	protected $getParameters;

	/**
	 * @var ArrayObject The raw POST parameters
	 */
	protected $postParameters;

	/**
	 * Constructs this dispatcher
	 *
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function __construct() {
		$this->arguments = new ArrayObject;
	}

	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param String $content The content
	 * @param array|NULL $configuration The TS configuration array
	 * @return String $content The processed content
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>	
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function dispatch($content, $configuration) {
		// debug($configuration);
		// TODO instantiate the configurationManager
		
		$requestBuilder = t3lib_div::makeInstance('TX_EXTMVC_Web_RequestBuilder');
		$request = $requestBuilder->build();
		$request->setControllerExtensionKey($configuration['extension']);
		$request->setControllerName($configuration['controller']);
		$request->setControllerActionName($configuration['action']);
		$request->setControllerActionName($configuration['action']);

		$response = t3lib_div::makeInstance('TX_EXTMVC_Web_Response');

		$controllerObjectName = $request->getControllerObjectName();
		$controller = t3lib_div::makeInstance($controllerObjectName);
		
		if (!$controller instanceof TX_EXTMVC_Controller_RequestHandlingController) throw new TX_EXTMVC_Exception_InvalidController('Invalid controller "' . $controllerObjectName . '". The controller must be a valid request handling controller.', 1202921619);
		// $getParameters = t3lib_div::_GET();
		// $postParameters = t3lib_div::_POST();
		// $settings = $this->configurationManager->getSettings($extensionKey);
		// $controller->injectSettings($this->configurationManager->getSettings($request->getControllerExtensionKey()));
		$session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		$dataMapper = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Mapper_TcaMapper');
		$controller->processRequest($request, $response);
		$session->commit();
		$session->clear();
		
		return $response->getContent();
	}

}
?>