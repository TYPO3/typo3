<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Sebastian Kurfuerst <sebastian@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Ext Direct Router
 *
 * @author	Sebastian Kurfuerst <sebastian@typo3.org>
 * @author	Stefan Galinski <stefan.galinski@gmail.com>
 * @package	TYPO3
 */
class t3lib_extjs_ExtDirectRouter {
	/**
	 * Dispatches the incoming calls to methods about the ExtDirect API.
	 *
	 * @param aray $ajaxParams ajax parameters
	 * @param TYPO3AJAX $ajaxObj typo3ajax instance
	 * @return void
	 */
	public function route($ajaxParams, TYPO3AJAX $ajaxObj) {
		try {
			$isForm = FALSE;
			$isUpload = FALSE;
			$rawPostData = file_get_contents('php://input');
			$postParameters = t3lib_div::_POST();
			$namespace = t3lib_div::_GET('namespace');

			if (!empty($postParameters['extAction'])) {
				$isForm = TRUE;
				$isUpload = $postParameters['extUpload'] === 'true';

				$request->action = $postParameters['extAction'];
				$request->method = $postParameters['extMethod'];
				$request->tid = $postParameters['extTID'];
				$request->data = array($_POST + $_FILES);
			} elseif (!empty($rawPostData)) {
				$request = json_decode($rawPostData);
			} else {
				throw new t3lib_error_Exception('ExtDirect: Missing Parameters!');
			}

			$response = NULL;
			if (is_array($request)) {
				$response = array();
				foreach ($request as $singleRequest) {
					$response[] = $this->processRpc($singleRequest, $namespace);
				}
			} else {
				$response = $this->processRpc($request, $namespace);
			}

			if ($isForm && $isUpload) {
				$ajaxObj->setContentFormat('plain');
				$response = json_encode($response);
				$response = preg_replace('/&quot;/', '\\&quot;', $response);

				$response = array(
					'<html><body><textarea>' .
					$response .
					'</textarea></body></html>'
				);
			} else {
				$ajaxObj->setContentFormat('jsonbody');
			}
		} catch (t3lib_error_Exception $exception) {
			$response = array(
				'type' => 'exception',
				'message' => $exception->getMessage(),
				'where' => $exception->getTraceAsString()
			);
		}

		$ajaxObj->setContent($response);
	}


	/**
	 * Processes an incoming extDirect call by executing the defined method. The configuration
	 * array "$GLOBALS['TYPO3_CONF_VARS']['BE']['ExtDirect']" is taken to find the class/method
	 * information.
	 *
	 * @param object $singleRequest request object from extJS
	 * @param string $namespace namespace like TYPO3.Backend
	 * @return mixed return value of the called method
	 */
	protected function processRpc($singleRequest, $namespace) {
		try {
			$endpointName = $namespace . '.' . $singleRequest->action;

			// theoretically this can never happen, because of an javascript error on
			// the client side due the missing namespace/endpoint
			if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName])) {
				throw new t3lib_error_Exception('ExtDirect: Call to undefined endpoint: ' . $endpointName);
			}

			$response = array(
				'type' => 'rpc',
				'tid' => $singleRequest->tid,
				'action' => $singleRequest->action,
				'method' => $singleRequest->method
			);

			$endpointObject = t3lib_div::getUserObj(
				$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName],
				FALSE
			);

			$response['result'] = call_user_func_array(
				array($endpointObject, $singleRequest->method),
				is_array($singleRequest->data) ? $singleRequest->data : array()
			);

		} catch (t3lib_error_Exception $exception) {
			throw $exception;
		}

		return $response;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extjs_extdirectrouter.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_extjs_extdirectrouter.php']);
}

?>
