<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Finishers;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Exception\StopActionException;
use TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException;
use TYPO3\CMS\Extbase\Mvc\Web\Request;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * This finisher redirects to another Controller.
 *
 * Scope: frontend
 */
class RedirectFinisher extends AbstractFinisher
{

    /**
     * @var array
     */
    protected $defaultOptions = [
        'pageUid' => 1,
        'additionalParameters' => '',
        'delay' => 0,
        'statusCode' => 303,
    ];

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
     */
    protected $request;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Response
     */
    protected $response;

    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    protected $uriBuilder;

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $this->request = $formRuntime->getRequest();
        $this->response = $formRuntime->getResponse();
        $this->uriBuilder = $this->objectManager->get(UriBuilder::class);
        $this->uriBuilder->setRequest($this->request);

        $pageUid = (int)str_replace('pages_', '', $this->parseOption('pageUid'));
        $additionalParameters = $this->parseOption('additionalParameters');
        $additionalParameters = '&' . ltrim($additionalParameters, '&');
        $delay = (int)$this->parseOption('delay');
        $statusCode = (int)$this->parseOption('statusCode');

        $this->finisherContext->cancel();
        $this->redirect($pageUid, $additionalParameters, $delay, $statusCode);
    }

    /**
     * Redirects the request to another page.
     *
     * Redirect will be sent to the client which then performs another request to the new URI.
     *
     * NOTE: This method only supports web requests and will thrown an exception
     * if used with other request types.
     *
     * @param int $pageUid Target page uid. If NULL, the current page uid is used
     * @param string $additionalParameters
     * @param int $delay (optional) The delay in seconds. Default is no delay.
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     * @throws UnsupportedRequestTypeException If the request is not a web request
     * @see forward()
     */
    protected function redirect(int $pageUid = 1, string $additionalParameters = '', int $delay = 0, int $statusCode = 303)
    {
        if (!$this->request instanceof Request) {
            throw new UnsupportedRequestTypeException('redirect() only supports web requests.', 1471776457);
        }

        $typolinkConfiguration = [
            'parameter' => $pageUid,
            'additionalParams' => $additionalParameters,
        ];
        $redirectUri = $this->getTypoScriptFrontendController()->cObj->typoLink_URL($typolinkConfiguration);
        $this->redirectToUri($redirectUri, $delay, $statusCode);
    }

    /**
     * Redirects the web request to another uri.
     *
     * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
     *
     * @param string $uri A string representation of a URI
     * @param int $delay (optional) The delay in seconds. Default is no delay.
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     * @throws UnsupportedRequestTypeException If the request is not a web request
     * @throws StopActionException
     */
    protected function redirectToUri(string $uri, int $delay = 0, int $statusCode = 303)
    {
        if (!$this->request instanceof Request) {
            throw new UnsupportedRequestTypeException('redirect() only supports web requests.', 1471776458);
        }

        $uri = $this->addBaseUriIfNecessary($uri);
        $escapedUri = htmlentities($uri, ENT_QUOTES, 'utf-8');

        $this->response->setContent('<html><head><meta http-equiv="refresh" content="' . (int)$delay . ';url=' . $escapedUri . '"/></head></html>');
        if ($this->response instanceof \TYPO3\CMS\Extbase\Mvc\Web\Response) {
            $this->response->setStatus($statusCode);
            $this->response->setHeader('Location', (string)$uri);
        }
        throw new StopActionException('redirectToUri', 1477070964);
    }

    /**
     * Adds the base uri if not already in place.
     *
     * @param string $uri The URI
     * @return string
     */
    protected function addBaseUriIfNecessary(string $uri): string
    {
        return GeneralUtility::locationHeaderUrl((string)$uri);
    }
}
