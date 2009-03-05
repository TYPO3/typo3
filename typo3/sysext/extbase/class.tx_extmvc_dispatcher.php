<?php

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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_Request.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Web/TX_EXTMVC_Web_Request.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/TX_EXTMVC_Response.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Web/TX_EXTMVC_Web_Response.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_AbstractController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_ActionController.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_Arguments.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_Argument.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/View/TX_EXTMVC_View_AbstractView.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/TX_EXTMVC_Persistence_Session.php');
require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/Mapper/TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper.php');

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
	 */
	public function __construct() {
		$this->arguments = new ArrayObject;
		spl_autoload_register(array($this, 'autoLoadClasses'));
	}

	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param String $content The content
	 * @param array|NULL $configuration The TS configuration array
	 * @return String $content The processed content
	 */
	public function dispatch($content, $configuration) {		
		// TODO Add an AJAX dispatcher
		// debug($configuration);
		$request = t3lib_div::makeInstance('TX_EXTMVC_Web_Request');
		$request->setControllerExtensionKey(strtolower($configuration['extension']));
		$request->setControllerName($configuration['controller']);
		$request->setControllerActionName($configuration['action']);
		$arguments = t3lib_div::makeInstance('TX_EXTMVC_Controller_Arguments');
		foreach (t3lib_div::GParrayMerged('tx_' . strtolower($configuration['extension'])) as $key => $value) {
			$argument = new TX_EXTMVC_Controller_Argument($key, 'Raw');
			$argument->setValue($value);
			$arguments->addArgument($argument);
		}
		$request->setArguments($arguments);
		$response = t3lib_div::makeInstance('TX_EXTMVC_Web_Response');

		$controllerObjectName = $request->getControllerObjectName();
		$controller = t3lib_div::makeInstance($controllerObjectName);
		
		if (!$controller instanceof TX_EXTMVC_Controller_AbstractController) throw new TX_EXTMVC_Exception_InvalidController('Invalid controller "' . $controllerObjectName . '". The controller must be a valid request handling controller.', 1202921619);
		// TODO Configuration management
		// $settings = $this->configurationManager->getSettings($extensionKey);
		// $controller->injectSettings($this->configurationManager->getSettings($request->getControllerExtensionKey()));
		$session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		try {
			$controller->processRequest($request, $response);			
		} catch (TX_EXTMVC_Exception_StopAction $ignoredException) {			
		} catch (TX_EXTMVC_Exception_StopUncachedAction $ignoredException) {
			$this->cObj->convertToUserIntObject();
		}
		
		$session->commit();
		$session->clear();
		
		$GLOBALS['TSFE']->additionalHeaderData[$request->getControllerExtensionKey()] = implode("\n", $response->getAdditionalHeaderTags());
		
		return $response->getContent();
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * an extension.
	 *
	 * @param string $className: Name of the class/interface to load
	 * @return void
	 */
	protected function autoLoadClasses($className) {
		$classNameParts = explode('_', $className);
		if ($classNameParts[0] === 'ux') {
			array_shift($classNameParts);
		}
		if (count($classNameParts) > 2 && $classNameParts[0] === 'TX') {
			$classFilePathAndName = t3lib_extMgm::extPath(strtolower($classNameParts[1])) . 'Classes/';
			$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
			$classFilePathAndName .= implode('_', $classNameParts) . '.php';
		}
		if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) require_once($classFilePathAndName);			
	}

}
?>