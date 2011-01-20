<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Sebastian Kurfürst <sebastian@typo3.org>
 *  (c) 2010-2011 Stefan Galinski <stefan.galinski@gmail.com>
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
 * @author	Sebastian Kurfürst <sebastian@typo3.org>
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
		$GLOBALS['error'] = t3lib_div::makeInstance('t3lib_extjs_ExtDirectDebug');

		$isForm = FALSE;
		$isUpload = FALSE;
		$rawPostData = file_get_contents('php://input');
		$postParameters = t3lib_div::_POST();
		$namespace = t3lib_div::_GET('namespace');
		$response = array();
		$request = NULL;

		if (!empty($postParameters['extAction'])) {
			$isForm = TRUE;
			$isUpload = $postParameters['extUpload'] === 'true';

			$request = new stdClass;
			$request->action = $postParameters['extAction'];
			$request->method = $postParameters['extMethod'];
			$request->tid = $postParameters['extTID'];
			$request->data = array($_POST + $_FILES);
		} elseif (!empty($rawPostData)) {
			$request = json_decode($rawPostData);
		} else {
			$response[] = array(
				'type' => 'exception',
				'message' => 'Something went wrong with an ExtDirect call!'
			);
		}

		if (!is_array($request)) {
			$request = array($request);
		}

		$validToken = FALSE;
		$firstCall = TRUE;
		foreach ($request as $index => $singleRequest) {
			$response[$index] = array(
				'tid' => $singleRequest->tid,
				'action' => $singleRequest->action,
				'method' => $singleRequest->method
			);

			$token = array_pop($singleRequest->data);
			if ($firstCall) {
				$firstCall = FALSE;
				$formprotection = t3lib_formprotection_Factory::get('t3lib_formprotection_BackendFormProtection');
				$validToken = $formprotection->validateToken($token, 'extDirect');
			}

			try {
				if (!$validToken) {
					throw new t3lib_formprotection_InvalidTokenException('ExtDirect: Invalid Security Token!');
				}

				$response[$index]['type'] = 'rpc';
				$response[$index]['result'] = $this->processRpc($singleRequest, $namespace);
				$response[$index]['debug'] = $GLOBALS['error']->toString();

			} catch (Exception $exception) {
				$response[$index]['type'] = 'exception';
				$response[$index]['message'] = $exception->getMessage();
				$response[$index]['where'] = $exception->getTraceAsString();
			}
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

		$ajaxObj->setContent($response);
	}


	/**
	 * Processes an incoming extDirect call by executing the defined method. The configuration
	 * array "$GLOBALS['TYPO3_CONF_VARS']['BE']['ExtDirect']" is taken to find the class/method
	 * information.
	 *
	 * @param object $singleRequest request object from extJS
	 * @param string $namespace namespace like TYPO3.Backend
	 * @throws UnexpectedValueException if the remote method couldn't be found
	 * @return mixed return value of the called method
	 */
	protected function processRpc($singleRequest, $namespace) {
		$endpointName = $namespace . '.' . $singleRequest->action;

			// theoretically this can never happen, because of an javascript error on
			// the client side due the missing namespace/endpoint
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName])) {
			throw new UnexpectedValueException('ExtDirect: Call to undefined endpoint: ' . $endpointName);
		}

		$endpointObject = t3lib_div::getUserObj(
			$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName],
			FALSE
		);

		return call_user_func_array(
			array($endpointObject, $singleRequest->method),
			is_array($singleRequest->data) ? $singleRequest->data : array()
		);
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/extjs/class.t3lib_extjs_extdirectrouter.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/extjs/class.t3lib_extjs_extdirectrouter.php']);
}

?>