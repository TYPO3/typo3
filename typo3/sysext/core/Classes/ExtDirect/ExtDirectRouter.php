<?php
namespace TYPO3\CMS\Core\ExtDirect;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Ext Direct Router
 */
class ExtDirectRouter
{
    /**
     * Dispatches the incoming calls to methods about the ExtDirect API.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function routeAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['error'] = GeneralUtility::makeInstance(\TYPO3\CMS\Core\ExtDirect\ExtDirectDebug::class);
        $isForm = false;
        $isUpload = false;
        $rawPostData = file_get_contents('php://input');
        $postParameters = $request->getParsedBody();
        $namespace = isset($request->getParsedBody()['namespace']) ? $request->getParsedBody()['namespace'] : $request->getQueryParams()['namespace'];
        $extResponse = [];
        $extRequest = null;
        $isValidRequest = true;
        if (!empty($postParameters['extAction'])) {
            $isForm = true;
            $isUpload = $postParameters['extUpload'] === 'true';
            $extRequest = new \stdClass();
            $extRequest->action = $postParameters['extAction'];
            $extRequest->method = $postParameters['extMethod'];
            $extRequest->tid = $postParameters['extTID'];
            unset($_POST['securityToken']);
            $extRequest->data = [$_POST + $_FILES];
            $extRequest->data[] = $postParameters['securityToken'];
        } elseif (!empty($rawPostData)) {
            $extRequest = json_decode($rawPostData);
        } else {
            $extResponse[] = [
                'type' => 'exception',
                'message' => 'Something went wrong with an ExtDirect call!',
                'code' => 'router'
            ];
            $isValidRequest = false;
        }
        if (!is_array($extRequest)) {
            $extRequest = [$extRequest];
        }
        if ($isValidRequest) {
            $validToken = false;
            $firstCall = true;
            foreach ($extRequest as $index => $singleRequest) {
                $extResponse[$index] = [
                    'tid' => $singleRequest->tid,
                    'action' => $singleRequest->action,
                    'method' => $singleRequest->method
                ];
                $token = is_array($singleRequest->data) ? array_pop($singleRequest->data) : null;
                if ($firstCall) {
                    $firstCall = false;
                    $formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
                    $validToken = $formprotection->validateToken($token, 'extDirect');
                }
                try {
                    if (!$validToken) {
                        throw new \TYPO3\CMS\Core\FormProtection\Exception('ExtDirect: Invalid Security Token!');
                    }
                    $extResponse[$index]['type'] = 'rpc';
                    $extResponse[$index]['result'] = $this->processRpc($singleRequest, $namespace);
                    $extResponse[$index]['debug'] = $GLOBALS['error']->toString();
                } catch (\Exception $exception) {
                    $extResponse[$index]['type'] = 'exception';
                    $extResponse[$index]['message'] = $exception->getMessage();
                    $extResponse[$index]['code'] = 'router';
                }
            }
        }
        if ($isForm && $isUpload) {
            $extResponse = json_encode($extResponse);
            $extResponse = preg_replace('/&quot;/', '\\&quot;', $extResponse);
            $extResponse = [
                '<html><body><textarea>' . $extResponse . '</textarea></body></html>'
            ];
        } else {
            $extResponse = json_encode($extResponse);
        }
        $response->getBody()->write($extResponse);
        return $response;
    }

    /**
     * Processes an incoming extDirect call by executing the defined method. The configuration
     * array "$GLOBALS['TYPO3_CONF_VARS']['BE']['ExtDirect']" is taken to find the class/method
     * information.
     *
     * @param \stdClass $singleRequest request object from extJS
     * @param string $namespace namespace like TYPO3.Backend
     * @return mixed return value of the called method
     * @throws \UnexpectedValueException if the remote method couldn't be found
     */
    protected function processRpc($singleRequest, $namespace)
    {
        $endpointName = $namespace . '.' . $singleRequest->action;
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName])) {
            throw new \UnexpectedValueException('ExtDirect: Call to undefined endpoint: ' . $endpointName, 1294586450);
        }
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName])) {
            if (!isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName]['callbackClass'])) {
                throw new \UnexpectedValueException('ExtDirect: Call to undefined endpoint: ' . $endpointName, 1294586451);
            }
            $callbackClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName]['callbackClass'];
            $configuration = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ExtDirect'][$endpointName];
            if (!is_null($configuration['moduleName']) && !is_null($configuration['accessLevel'])) {
                $GLOBALS['BE_USER']->modAccess([
                    'name' => $configuration['moduleName'],
                    'access' => $configuration['accessLevel']
                ], true);
            }
        }
        $endpointObject = GeneralUtility::getUserObj($callbackClass);
        return call_user_func_array([$endpointObject, $singleRequest->method], is_array($singleRequest->data) ? $singleRequest->data : []);
    }
}
