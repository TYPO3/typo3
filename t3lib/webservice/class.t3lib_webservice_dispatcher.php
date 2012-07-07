<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *		2012 Nicolas Forgerit <nicolas.forgerit@gmail.com>
 *  	All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Takes care of the general Webservice API dispatching
 *
 * @api
 */
class t3lib_webservice_dispatcher {

	/**
	 * @var t3lib_webservice_Router
	 */
	protected $router;

	/**
	 * @var t3lib_webservice_RequestBuilder
	 */
	protected $requestBuilder;
	
	/**
	 * @var t3lib_webservice_viewBuilder
	 */
	protected $viewBuilder;

	/**
	 * The class' constructor
	 */
	public function __construct() {
		$this->router = t3lib_div::makeInstance('t3lib_webservice_Router');
		$this->requestBuilder = t3lib_div::makeInstance('t3lib_webservice_RequestBuilder');
	}

	/**
	 * This is the main method which should be called from outside.
	 * 
	 * @param string $requestString
	 * @throws In validArgumentException
	 * @return void
	 */
	public function dispatch($requestString) {		
		$resolvedRoute = $this->router->resolveRoute($requestString);

		if ($resolvedRoute !== NULL && isset($resolvedRoute['extensionName'])) {
			$app = t3lib_div::makeInstance('t3lib_webservice_app');
			$app['request'] = $this->requestBuilder->build();
			$app['response'] = t3lib_div::makeInstance('t3lib_webservice_response');
			
			// TODO: currently just hard-coded. for supporting global/system extensions, this needs to be done differently
			// TODO: before including, check if to-be-included extension is activated (in sense of TYPO3)
			try {
				if (! file_exists(PATH_site.'/typo3conf/ext/'.$resolvedRoute['extensionName'].'/ext_api.php')) {
					throw new Exception("Requested extension does not have an ext_api.php file.");
				}	
				require_once PATH_site.'/typo3conf/ext/'.$resolvedRoute['extensionName'].'/ext_api.php';
			} catch (Exception $e) {
				die("could not find requested extension. sorry -_- \n".$e->getMessage());
			}
			
			$app->run();
			

			$this->output($app['response']);
		} else {
			echo "damn. could not resolve request :(";
		}
	}

	/**
	 * @param t3lib_webservice_Response $response
	 * @return void
	 */
	protected function output(t3lib_webservice_Response $response) {
		$response->sendHeaders();
		$response->send();
	}
}
?>
