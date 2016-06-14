<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

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

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\TooManyRedirectsException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class provides Check External Links plugin implementation
 */
class ExternalLinktype extends AbstractLinktype
{
    /**
     * Cached list of the URLs, which were already checked for the current processing
     *
     * @var array $urlReports
     */
    protected $urlReports = array();

    /**
     * Cached list of all error parameters of the URLs, which were already checked for the current processing
     *
     * @var array $urlErrorParams
     */
    protected $urlErrorParams = array();

    /**
     * List of headers to be used for matching an URL for the current processing
     *
     * @var array $additionalHeaders
     */
    protected $additionalHeaders = array();

    /**
     * Checks a given URL for validity
     *
     * @param string $url The URL to check
     * @param array $softRefEntry The soft reference entry which builds the context of that URL
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
     * @return bool TRUE on success or FALSE on error
     */
    public function checkLink($url, $softRefEntry, $reference)
    {
        $errorParams = array();
        $isValidUrl = true;
        if (isset($this->urlReports[$url])) {
            if (!$this->urlReports[$url]) {
                if (is_array($this->urlErrorParams[$url])) {
                    $this->setErrorParams($this->urlErrorParams[$url]);
                }
            }
            return $this->urlReports[$url];
        }

        $options = array(
            'cookies' => GeneralUtility::makeInstance(CookieJar::class),
            'allow_redirects' => ['strict' => true]
        );

        /** @var RequestFactory $requestFactory */
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
        try {
            $response = $requestFactory->request($url, 'HEAD', $options);
            // HEAD was not allowed or threw an error, now trying GET
            if ($response->getStatusCode() >= 400) {
                $options['headers']['Range'] = 'bytes = 0 - 4048';
                $response = $requestFactory->request($url, 'GET', $options);
            }
            if ($response->getStatusCode() >= 300) {
                $isValidUrl = false;
                $errorParams['errorType'] = $response->getStatusCode();
                $errorParams['message'] = $response->getReasonPhrase();
            }
        } catch (TooManyRedirectsException $e) {
            $lastRequest = $e->getRequest();
            $response = $e->getResponse();
            $errorParams['errorType'] = 'loop';
            $errorParams['location'] = (string)$lastRequest->getUri();
            $errorParams['errorCode'] = $response->getStatusCode();
        } catch (\Exception $e) {
            $isValidUrl = false;
            $errorParams['errorType'] = 'exception';
            $errorParams['message'] = $e->getMessage();
        }
        if (!$isValidUrl) {
            $this->setErrorParams($errorParams);
        }
        $this->urlReports[$url] = $isValidUrl;
        $this->urlErrorParams[$url] = $errorParams;
        return $isValidUrl;
    }

    /**
     * Generate the localized error message from the error params saved from the parsing
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage($errorParams)
    {
        $lang = $this->getLanguageService();
        $errorType = $errorParams['errorType'];
        switch ($errorType) {
            case 300:
                $response = sprintf($lang->getLL('list.report.externalerror'), $errorType);
                break;
            case 403:
                $response = $lang->getLL('list.report.pageforbidden403');
                break;
            case 404:
                $response = $lang->getLL('list.report.pagenotfound404');
                break;
            case 500:
                $response = $lang->getLL('list.report.internalerror500');
                break;
            case 'loop':
                $response = sprintf($lang->getLL('list.report.redirectloop'), $errorParams['errorCode'], $errorParams['location']);
                break;
            case 'exception':
                $response = sprintf($lang->getLL('list.report.httpexception'), $errorParams['message']);
                break;
            default:
                $response = sprintf($lang->getLL('list.report.otherhttpcode'), $errorType, $errorParams['message']);
        }
        return $response;
    }

    /**
     * Get the external type from the softRefParserObj result
     *
     * @param array $value Reference properties
     * @param string $type Current type
     * @param string $key Validator hook name
     * @return string Fetched type
     */
    public function fetchType($value, $type, $key)
    {
        preg_match_all('/((?:http|https))(?::\\/\\/)(?:[^\\s<>]+)/i', $value['tokenValue'], $urls, PREG_PATTERN_ORDER);
        if (!empty($urls[0][0])) {
            $type = 'external';
        }
        return $type;
    }
}
