<?php
namespace TYPO3\CMS\Core\ExtDirect;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Sebastian Kurfürst <sebastian@typo3.org>
 *  (c) 2010-2013 Stefan Galinski <stefan.galinski@gmail.com>
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
 * @author Sebastian Kurfürst <sebastian@typo3.org>
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
class ExtDirectRouter {

	/**
	 * Dispatches the incoming calls to methods about the ExtDirect API.
	 *
	 * @param aray $ajaxParams Ajax parameters
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj typo3ajax instance
	 * @return void
	 */
	public function route($ajaxParams, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		$GLOBALS['error'] = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectDebug');
		$isForm = FALSE;
		$isUpload = FALSE;
		$rawPostData = file_get_contents('php://input');
		$postParameters = \TYPO3\CMS\Core\Utility\GeneralUtility::_POST();
		$namespace = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('namespace');
		$response = array();
		$request = NULL;
		$isValidRequest = TRUE;
		if (!empty($postParameters['extAction'])) {
			$isForm = TRUE;
			$isUpload = $postParameters['extUpload'] === 'true';
			$request = new \stdClass();
			$request->action = $postParameters['extAction'];
			$request->method = $postParameters['extMethod'];
			$request->tid = $postParameters['extTID'];
			unset($_POST['securityToken']);
			$request->data = array($_POST + $_FILES);
			$request->data[] = $postParameters['securityToken'];
		} elseif (!empty($rawPostData)) {
			$request = json_decode($rawPostData);
		} else {
			$response[] = array(
				'type' => 'exception',
				'message' => 'Something went wrong with an ExtDirect call!',
				'code' => 'router'
			);
			$isValidRequest = FALSE;
		}
		if (!is_array($request)) {
			$request = array($request);
		}
		if ($isValidRequest) {
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
					$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
					$validToken = $formprotection->validateToken($token, 'extDirect');
				}
				try {
					if (!$validToken) {
						throw new \TYPO3\CMS\Core\FormProtection\Exception('ExtDirect: Invalid Security Token!');
					}
					$response[$index]['type'] = 'rpc';
					$response[$index]['result'] = $this->processRpc($singleRequest, $namespace);
					$response[$index]['debug'] = $GLOBALS['error']->toString();
				} catch (\Exception $exception) {
					$response[$index]['type'] = 'exception';
					$response[$index]['message'] = $exception->getMessage();
					$response[$index]['code'] = 'router';
				}
			}
		}
		if ($isForm && $isUpload) {
			$ajaxObj->setContentFormat('plain');
			$response = json_encode($response);
			$response = preg_replace('/&quot;/', '\\&quot;', $response);
			$response = array(
				'<html><body><textarea>' . $response . '</textarea></body></html>'
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
	 * @return mixed return value of the called method
	 * @throws UnexpectedValueException if the remote method couldn't be found
	 */
	protected function processRpc($singleRequest, $namespace) {
		$endpointName = $namespace . '.' . $singleRequest->action;
		if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName])) {
			throw new \UnexpectedValueException('ExtDirect: Call to undefined endpoint: ' . $endpointName, 1294586450);
		}
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName])) {
			if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName]['callbackClass'])) {
				throw new \UnexpectedValueException('ExtDirect: Call to undefined endpoint: ' . $endpointName, 1294586450);
			}
			$callbackClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName]['callbackClass'];
			$configuration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName];
			if (!is_null($configuration['moduleName']) && !is_null($configuration['accessLevel'])) {
				$GLOBALS['BE_USER']->modAccess(array(
					'name' => $configuration['moduleName'],
					'access' => $configuration['accessLevel']
				), TRUE);
			}
		}
		$endpointObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($callbackClass, FALSE);
		return call_user_func_array(array($endpointObject, $singleRequest->method), is_array($singleRequest->data) ? $singleRequest->data : array());
	}

}


?>