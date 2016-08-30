<?php
namespace TYPO3\CMS\Extbase\Mvc\Web;

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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * A web specific response implementation
 *
 * @api
 */
class Response extends \TYPO3\CMS\Extbase\Mvc\Response
{
    /**
     * The HTTP headers which will be sent in the response
     *
     * @var array
     */
    protected $headers = [];

    /**
     * Additional header tags
     *
     * @var array
     */
    protected $additionalHeaderData = [];

    /**
     * The HTTP status code
     *
     * @var int
     */
    protected $statusCode;

    /**
     * The HTTP status message
     *
     * @var string
     */
    protected $statusMessage = 'OK';

    /**
     * The Request which generated the Response
     *
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Request
     */
    protected $request;

    /**
     * The standardized and other important HTTP Status messages
     *
     * @var array
     */
    protected $statusMessages = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // RFC 2518
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        507 => 'Insufficient Storage',
        509 => 'Bandwidth Limit Exceeded'
    ];

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * Sets the HTTP status code and (optionally) a customized message.
     *
     * @param int $code The status code
     * @param string $message If specified, this message is sent instead of the standard message
     * @throws \InvalidArgumentException if the specified status code is not valid
     * @return void
     * @api
     */
    public function setStatus($code, $message = null)
    {
        if (!is_int($code)) {
            throw new \InvalidArgumentException('The HTTP status code must be of type integer, ' . gettype($code) . ' given.', 1220526013);
        }
        if ($message === null && !isset($this->statusMessages[$code])) {
            throw new \InvalidArgumentException('No message found for HTTP status code "' . $code . '".', 1220526014);
        }
        $this->statusCode = $code;
        $this->statusMessage = $message === null ? $this->statusMessages[$code] : $message;
    }

    /**
     * Returns status code and status message.
     *
     * @return string The status code and status message, eg. "404 Not Found
     * @api
     */
    public function getStatus()
    {
        return $this->statusCode . ' ' . $this->statusMessage;
    }

    /**
     * Sets the specified HTTP header
     *
     * @param string $name Name of the header, for example "Location", "Content-Description" etc.
     * @param mixed $value The value of the given header
     * @param bool $replaceExistingHeader If a header with the same name should be replaced. Default is TRUE.
     * @throws \InvalidArgumentException
     * @return void
     * @api
     */
    public function setHeader($name, $value, $replaceExistingHeader = true)
    {
        if (strtoupper(substr($name, 0, 4)) === 'HTTP') {
            throw new \InvalidArgumentException('The HTTP status header must be set via setStatus().', 1220541963);
        }
        if ($replaceExistingHeader === true || !isset($this->headers[$name])) {
            $this->headers[$name] = [$value];
        } else {
            $this->headers[$name][] = $value;
        }
    }

    /**
     * Returns the HTTP headers - including the status header - of this web response
     *
     * @return string[] The HTTP headers
     * @api
     */
    public function getHeaders()
    {
        $preparedHeaders = [];
        if ($this->statusCode !== null) {
            $protocolVersion = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0';
            $statusHeader = $protocolVersion . ' ' . $this->statusCode . ' ' . $this->statusMessage;
            $preparedHeaders[] = $statusHeader;
        }
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                $preparedHeaders[] = $name . ': ' . $value;
            }
        }
        return $preparedHeaders;
    }

    /**
     * Sends the HTTP headers.
     *
     * If headers have already been sent, this method fails silently.
     *
     * @return void
     * @api
     */
    public function sendHeaders()
    {
        if (headers_sent() === true) {
            return;
        }
        foreach ($this->getHeaders() as $header) {
            header($header);
        }
    }

    /**
     * Renders and sends the whole web response
     *
     * @return void
     * @api
     */
    public function send()
    {
        $this->sendHeaders();
        if ($this->content !== null) {
            echo $this->getContent();
        }
    }

    /**
     * Adds an additional header data (something like
     * '<script src="myext/Resources/JavaScript/my.js" type="text/javascript"></script>'
     * )
     *
     * @TODO The workround and the $request member should be removed again, once the PageRender does support non-cached USER_INTs
     * @param string $additionalHeaderData The value additonal header
     * @throws \InvalidArgumentException
     * @return void
     * @api
     */
    public function addAdditionalHeaderData($additionalHeaderData)
    {
        if (!is_string($additionalHeaderData)) {
            throw new \InvalidArgumentException('The additiona header data must be of type String, ' . gettype($additionalHeaderData) . ' given.', 1237370877);
        }
        if ($this->request->isCached()) {
            /** @var PageRenderer $pageRenderer */
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addHeaderData($additionalHeaderData);
        } else {
            $this->additionalHeaderData[] = $additionalHeaderData;
        }
    }

    /**
     * Returns the additional header data
     *
     * @return array The additional header data
     * @api
     */
    public function getAdditionalHeaderData()
    {
        return $this->additionalHeaderData;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Web\Request $request
     */
    public function setRequest(\TYPO3\CMS\Extbase\Mvc\Web\Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Mvc\Web\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Sends additional headers and returns the content
     *
     * @return null|string
     */
    public function shutdown()
    {
        if (!empty($this->getAdditionalHeaderData())) {
            $this->getTypoScriptFrontendController()->additionalHeaderData[] = implode(LF, $this->getAdditionalHeaderData());
        }
        $this->sendHeaders();
        return parent::shutdown();
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}
