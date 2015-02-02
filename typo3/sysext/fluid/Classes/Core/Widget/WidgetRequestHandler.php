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
 * Widget request handler, which handles the request if
 * f3-fluid-widget-id is found.
 *
 * This Request Handler gets the WidgetRequestBuilder injected.
 */
class WidgetRequestHandler extends \TYPO3\CMS\Extbase\Mvc\Web\AbstractRequestHandler {

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\AjaxWidgetContextHolder
	 * @inject
	 */
	protected $ajaxWidgetContextHolder;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * @var \TYPO3\CMS\Fluid\Core\Widget\WidgetRequestBuilder
	 * @inject
	 */
	protected $requestBuilder;

	/**
	 * Handles the web request. The response will automatically be sent to the client.
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Web\Response
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		if (isset($this->cObj->data) && is_array($this->cObj->data)) {
			$request->setContentObjectData($this->cObj->data);
		}
		$response = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Response');
		$this->dispatcher->dispatch($request, $response);
		return $response;
	}

	/**
	 * @return boolean TRUE if it is an AJAX widget request
	 */
	public function canHandleRequest() {
		$rawGetArguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET();
		return isset($rawGetArguments['fluid-widget-id']);
	}

	/**
	 * This request handler has a higher priority than the default request handler.
	 *
	 * @return integer
	 */
	public function getPriority() {
		return 200;
	}
}
