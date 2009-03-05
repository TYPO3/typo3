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

/**
 * A URI/Link Helper
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_View_Helper_URIHelper extends TX_EXTMVC_View_Helper_AbstractHelper {

	/**
	 * @var TX_EXTMVC_Web_Routing_RouterInterface
	 */
	protected $router;

	/**
	 * Injects the Router
	 * 
	 * @param TX_EXTMVC_Web_Routing_RouterInterface $router
	 * @return void
	 */
	public function injectRouter(TX_EXTMVC_Web_Routing_RouterInterface $router) {
		$this->router = $router;
	}

	/**
	 * Creates a link by making use of the Routers reverse routing mechanism.
	 * 
	 * @param string $label Inner HTML of the generated link. Label is htmlspecialchared by default
	 * @param string $actionName Name of the action to be called
	 * @param array $arguments Additional arguments
	 * @param string $controllerName Name of the target controller. If not set, current controller is used
	 * @param string $extensionKey Name of the target extension. If not set, current extension is used
	 * @param string $subextensionKey Name of the target subextension. If not set, current subextension is used
	 * @param array $options Further options
	 * @return string the HTML code for the generated link
	 * @see UIRFor()
	 */
	public function linkTo($label, $actionName, $arguments = array(), $controllerName = NULL, $extensionKey = NULL, $subextensionKey = NULL, $options = array()) {
		$link = '<a href="' . $this->URIFor($actionName, $arguments, $controllerName, $extensionKey, $subextensionKey, $options) . '">' . htmlspecialchars($label) . '</a>';
		return $link;
	}

	/**
	 * Creates an URI by making use of the Routers reverse routing mechanism.
	 * 
	 * @param string $actionName Name of the action to be called
	 * @param array $arguments Additional arguments
	 * @param string $controllerName Name of the target controller. If not set, current controller is used
	 * @param string $extensionKey Name of the target extension. If not set, current extension is used
	 * @param string $subextensionKey Name of the target subextension. If not set, current subextension is used
	 * @param array $options Further options
	 * @return string the HTML code for the generated link
	 */
	public function URIFor($actionName, $arguments = array(), $controllerName = NULL, $extensionKey = NULL, $subextensionKey = NULL, $options = array()) {
		$routeValues = $arguments;
		$routeValues['@action'] = $actionName;
		$routeValues['@controller'] = ($controllerName === NULL) ? $this->request->getControllerName() : $controllerName;
		$routeValues['@extension'] = ($extensionKey === NULL) ? $this->request->getControllerExtensionKey() : $extensionKey;
		$currentSubextensionKey = $this->request->getControllerSubextensionKey();
		if ($subextensionKey === NULL && strlen($currentSubextensionKey)) {
			$routeValues['@subextension'] = $currentSubextensionKey;
		} else if (strlen($subextensionKey)) {
			$routeValues['@subextension'] = $subextensionKey;
		}

		$URIString = $this->router->resolve($routeValues);
		return $URIString;
	}
}

?>
