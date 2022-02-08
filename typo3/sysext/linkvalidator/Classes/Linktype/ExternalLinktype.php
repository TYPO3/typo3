<?php

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

namespace TYPO3\CMS\Linkvalidator\Linktype;

use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Exception\TooManyRedirectsException;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * This class provides Check External Links plugin implementation
 */
class ExternalLinktype extends AbstractLinktype
{
    // HTTP status code was delivered (and can be found in $errorParams['errno'])
    protected const ERROR_TYPE_HTTP_STATUS_CODE = 'httpStatusCode';
    // An error occurred in lowlevel handler and a cURL error code can be found in $errorParams['errno']
    protected const ERROR_TYPE_LOWLEVEL_LIBCURL_ERRNO = 'libcurlErrno';
    protected const ERROR_TYPE_GENERIC_EXCEPTION = 'exception';
    protected const ERROR_TYPE_UNKNOWN = 'unknown';

    /**
     * Cached list of the URLs, which were already checked for the current processing
     *
     * @var array
     */
    protected $urlReports = [];

    /**
     * Cached list of all error parameters of the URLs, which were already checked for the current processing
     *
     * @var array
     */
    protected $urlErrorParams = [];

    /**
     * List of HTTP request headers to use for checking a URL
     *
     * @var array
     */
    protected $headers = [
        'User-Agent'      => 'TYPO3 linkvalidator',
        'Accept'          => '*/*',
        'Accept-Language' => '*',
        'Accept-Encoding' => '*',
    ];

    /**
     * Preferred method of fetching (HEAD | GET).
     * If HEAD is used, we fallback to GET
     *
     * @var string
     */
    protected $method = 'HEAD';

    /**
     * For GET method, set number of bytes returned.
     *
     * This limits the payload, but may fail for some sites.
     *
     * @var string
     */
    protected $range = '0-4048';

    /**
     *  Total timeout of the request in seconds. Using 0 (which is usually the default) may
     *  cause the request to take indefinitely, which means the scheduler task never ends.
     *
     * @var int
     */
    protected int $timeout = 0;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var array
     */
    protected $errorParams = [];

    public function __construct(RequestFactory $requestFactory = null)
    {
        $this->requestFactory = $requestFactory ?: GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function setAdditionalConfig(array $config): void
    {
        if ($config['headers.'] ?? false) {
            $this->headers = array_merge($this->headers, $config['headers.']);
        }

        if ($config['httpAgentName'] ?? false) {
            $this->headers['User-Agent'] = $config['httpAgentName'];
        }

        if ($config['httpAgentUrl'] ?? false) {
            $this->headers['User-Agent'] .= ' ' . $config['httpAgentUrl'];
        }

        $email = '';
        if ($config['httpAgentEmail'] ?? false) {
            $email = $config['httpAgentEmail'];
        } elseif ($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? false) {
            $email = $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'];
        }
        if ($email) {
            $this->headers['User-Agent'] .= ';' . $email;
        }

        if ($config['method'] ?? false) {
            $this->method = $config['method'];
        }
        if ($config['range'] ?? false) {
            $this->range = $config['range'];
        }
        if (isset($config['timeout'])) {
            $this->timeout = (int)$config['timeout'];
        }
    }

    /**
     * Checks a given URL for validity
     *
     * @param string $origUrl The URL to check
     * @param array $softRefEntry The soft reference entry which builds the context of that URL
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
     * @return bool TRUE on success or FALSE on error
     * @throws \InvalidArgumentException
     */
    public function checkLink($origUrl, $softRefEntry, $reference)
    {
        $isValidUrl = false;
        // use URL from cache, if available
        if (isset($this->urlReports[$origUrl])) {
            $this->setErrorParams($this->urlErrorParams[$origUrl]);
            return $this->urlReports[$origUrl];
        }
        $options = [
            'cookies' => GeneralUtility::makeInstance(CookieJar::class),
            'allow_redirects' => ['strict' => true],
            'headers'         => $this->headers,
        ];
        if ($this->timeout > 0) {
            $options['timeout'] = $this->timeout;
        }
        $url = $this->preprocessUrl($origUrl);
        if (!empty($url)) {
            if ($this->method === 'HEAD') {
                $isValidUrl = $this->requestUrl($url, 'HEAD', $options);
            }
            if (!$isValidUrl) {
                // HEAD was not allowed or threw an error, now trying GET
                if ($this->range) {
                    $options['headers']['Range'] = 'bytes=' . $this->range;
                }
                $isValidUrl = $this->requestUrl($url, 'GET', $options);
            }
        }
        $this->urlReports[$origUrl] = $isValidUrl;
        $this->urlErrorParams[$origUrl] = $this->errorParams;
        return $isValidUrl;
    }

    /**
     * Check URL using the specified request methods
     *
     * @param string $url
     * @param string $method
     * @param array $options
     * @return bool
     */
    protected function requestUrl(string $url, string $method, array $options): bool
    {
        $this->errorParams = [];
        $isValidUrl = false;
        try {
            $response = $this->requestFactory->request($url, $method, $options);
            if ($response->getStatusCode() >= 300) {
                $this->errorParams['errorType'] = $response->getStatusCode();
                $this->errorParams['message'] = $this->getErrorMessage($this->errorParams);
            } else {
                $isValidUrl = true;
            }
            /* Guzzle Exceptions:
             * . \RuntimeException
             * ├── SeekException (implements GuzzleException)
             * └── TransferException (implements GuzzleException)
             * └── RequestException
             * ├── BadResponseException
             * │   ├── ServerException
             * │   └── ClientException
             * ├── ConnectException
             * └── TooManyRedirectsException
             */
        } catch (TooManyRedirectsException $e) {
            $this->errorParams['errorType'] = 'tooManyRedirects';
            $this->errorParams['exception'] = $e->getMessage();
            $this->errorParams['message'] = $this->getErrorMessage($this->errorParams);
        } catch (ClientException | ServerException $e) {
            // ClientException - A GuzzleHttp\Exception\ClientException is thrown for 400 level errors if the http_errors request option is set to true.
            // ServerException - A GuzzleHttp\Exception\ServerException is thrown for 500 level errors if the http_errors request option is set to true.
            if ($e->hasResponse()) {
                $this->errorParams['errorType'] = self::ERROR_TYPE_HTTP_STATUS_CODE;
                $this->errorParams['errno'] = $e->getResponse()->getStatusCode();
            } else {
                $this->errorParams['errorType'] = self::ERROR_TYPE_UNKNOWN;
            }
            $this->errorParams['exception'] = $e->getMessage();
            $this->errorParams['message'] = $this->getErrorMessage($this->errorParams);
        } catch (RequestException | ConnectException $e) {
            // RequestException - In the event of a networking error (connection timeout, DNS errors, etc.), a GuzzleHttp\Exception\RequestException is thrown.
            // Catching this exception will catch any exception that can be thrown while transferring requests.
            // ConnectException - A GuzzleHttp\Exception\ConnectException exception is thrown in the event of a networking error.
            $this->errorParams['errorType'] = self::ERROR_TYPE_LOWLEVEL_LIBCURL_ERRNO;
            $this->errorParams['exception'] = $e->getMessage();
            $handlerContext = $e->getHandlerContext();
            if ($handlerContext['errno'] ?? 0) {
                $this->errorParams['errno'] = (int)($handlerContext['errno']);
            }
            $this->errorParams['message'] = $this->getErrorMessage($this->errorParams);
        } catch (\Exception $e) {
            // Generic catch for anything else that may go wrong
            $this->errorParams['errorType'] = self::ERROR_TYPE_GENERIC_EXCEPTION;
            $this->errorParams['exception'] = $e->getMessage();
            $this->errorParams['message'] = $this->getErrorMessage($this->errorParams);
        }
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
            case self::ERROR_TYPE_HTTP_STATUS_CODE:
                switch ($errorParams['errno'] ?? 0) {
                    case 403:
                        $message = $lang->getLL('list.report.pageforbidden403');
                        break;
                    case 404:
                        $message = $lang->getLL('list.report.pagenotfound404');
                        break;
                    case 500:
                        $message = $lang->getLL('list.report.internalerror500');
                        break;
                    default:
                        // fall back to other error messages
                        $message = $lang->getLL('list.report.error.httpstatuscode.' . $errorParams['errno']);
                        if (!$message) {
                            // fall back to generic error message
                            $message = sprintf($lang->getLL('list.report.externalerror'), $errorType);
                        }
                }
                break;

            case self::ERROR_TYPE_LOWLEVEL_LIBCURL_ERRNO:
                $message = '';
                if ($errorParams['errno'] ?? 0) {
                    // get localized error message
                    $message = $lang->getLL('list.report.error.libcurl.' . $errorParams['errno']);
                }
                if (!$message) {
                    // fallback to  generic error message and show exception
                    $message = $lang->getLL('list.report.networkexception');
                    if (($errorParams['exception'] ?? '') != '') {
                        $message .= ' ('
                            . $errorParams['exception']
                            . ')';
                    }
                }
                break;

            case 'loop':
                $message = sprintf(
                    $lang->getLL('list.report.redirectloop'),
                    $errorParams['exception'],
                    ''
                );
                break;

            case 'tooManyRedirects':
                $message = $lang->getLL('list.report.tooManyRedirects');
                break;

            case 'exception':
                $message = sprintf($lang->getLL('list.report.httpexception'), $errorParams['exception']);
                break;

            default:
                $message = sprintf($lang->getLL('list.report.otherhttpcode'), $errorType, $errorParams['exception']);
        }
        return $message;
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
        preg_match_all('/((?:http|https))(?::\\/\\/)(?:[^\\s<>]+)/i', $value['tokenValue'] ?? '', $urls, PREG_PATTERN_ORDER);
        if (!empty($urls[0][0])) {
            $type = 'external';
        }
        return $type;
    }

    /**
     * Convert domain to punycode to handle domains with non-ASCII characters
     *
     * @param string $url
     * @return string
     */
    protected function preprocessUrl(string $url): string
    {
        $url = html_entity_decode($url);
        $parts = parse_url($url);
        if ($parts['host'] ?? false) {
            try {
                $newDomain = (string)idn_to_ascii($parts['host']);
                if (strcmp($parts['host'], $newDomain) !== 0) {
                    $parts['host'] = $newDomain;
                    $url = HttpUtility::buildUrl($parts);
                }
            } catch (\Exception | \Throwable $e) {
                // ignore error and proceed with link checking
            }
        }
        return $url;
    }
}
