<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Form\Domain\Finishers;

use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        'statusCode' => 303,
        'fragment' => '',
    ];

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $pageUid = $this->parseOption('pageUid');
        $pageUid = (int)str_replace('pages_', '', (string)$pageUid);
        $additionalParameters = $this->parseOption('additionalParameters');
        $additionalParameters = is_string($additionalParameters) ? $additionalParameters : '';
        $additionalParameters = '&' . ltrim($additionalParameters, '&');
        $statusCode = (int)$this->parseOption('statusCode');
        $fragment = (string)$this->parseOption('fragment');

        $this->finisherContext->cancel();
        $this->redirect($pageUid, $additionalParameters, $fragment, $statusCode);
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
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     * @see forward()
     */
    protected function redirect(int $pageUid, string $additionalParameters, string $fragment, int $statusCode)
    {
        $typolinkConfiguration = [
            'parameter' => $pageUid,
            'additionalParams' => $additionalParameters,
            'section' => $fragment,
        ];
        $redirectUri = $this->getTypoScriptFrontendController()->cObj->createUrl($typolinkConfiguration);
        $this->redirectToUri($redirectUri, $statusCode);
    }

    /**
     * Redirects the web request to another uri.
     *
     * NOTE: This method only supports web requests and will thrown an exception if used with other request types.
     *
     * @param string $uri A string representation of a URI
     * @param int $statusCode (optional) The HTTP status code for the redirect. Default is "303 See Other
     * @throws PropagateResponseException
     */
    protected function redirectToUri(string $uri, int $statusCode = 303)
    {
        $uri = $this->addBaseUriIfNecessary($uri);
        $response = new RedirectResponse($uri, $statusCode);
        // End processing and dispatching by throwing a PropagateResponseException with our response.
        // @todo: Should be changed to *return* a response instead, but this requires the ContentObjectRender
        // @todo: to deal with responses instead of strings, if the form is used in a fluid template rendered by the
        // @todo: FluidTemplateContentObject and the extbase bootstrap isn't used.
        throw new PropagateResponseException($response, 1477070964);
    }

    /**
     * Adds the base uri if not already in place.
     *
     * @param string $uri The URI
     */
    protected function addBaseUriIfNecessary(string $uri): string
    {
        return GeneralUtility::locationHeaderUrl($uri);
    }
}
