<?php
namespace TYPO3\CMS\Core\Http;

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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to hold all the information about an AJAX call and send
 * the right headers for the request type
 */
class AjaxRequestHandler
{
    /**
     * @var string|NULL
     */
    protected $ajaxId = null;

    /**
     * @var string|NULL
     */
    protected $errorMessage = null;

    /**
     * @var bool
     */
    protected $isError = false;

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @var string
     */
    protected $contentFormat = 'plain';

    /**
     * @var string
     */
    protected $javascriptCallbackWrap = '
		<script type="text/javascript">
			/*<![CDATA[*/
			response = |;
			/*]]>*/
		</script>
	';

    /**
     * Sets the ID for the AJAX call
     *
     * @param string $ajaxId The AJAX id
     */
    public function __construct($ajaxId)
    {
        $this->ajaxId = $ajaxId;
    }

    /**
     * Returns the ID for the AJAX call
     *
     * @return string The AJAX id
     */
    public function getAjaxID()
    {
        return $this->ajaxId;
    }

    /**
     * Overwrites the existing content with the data supplied
     *
     * @param array $content The new content
     * @return mixed The old content as array; if the new content was not an array, FALSE is returned
     */
    public function setContent($content)
    {
        $oldcontent = false;
        if (is_array($content)) {
            $oldcontent = $this->content;
            $this->content = $content;
        }
        return $oldcontent;
    }

    /**
     * Adds new content
     *
     * @param string $key The new content key where the content should be added in the content array
     * @param string $content The new content to add
     * @return mixed The old content; if the old content didn't exist before, FALSE is returned
     */
    public function addContent($key, $content)
    {
        $oldcontent = false;
        if (array_key_exists($key, $this->content)) {
            $oldcontent = $this->content[$key];
        }
        if (!isset($content) || empty($content)) {
            unset($this->content[$key]);
        } elseif (!isset($key) || empty($key)) {
            $this->content[] = $content;
        } else {
            $this->content[$key] = $content;
        }
        return $oldcontent;
    }

    /**
     * Returns the content for the ajax call
     *
     * @return mixed The content for a specific key or the whole content
     */
    public function getContent($key = '')
    {
        return $key && array_key_exists($key, $this->content) ? $this->content[$key] : $this->content;
    }

    /**
     * Sets the content format for the ajax call
     *
     * @param string $format Can be one of 'plain' (default), 'xml', 'json', 'javascript', 'jsonbody' or 'jsonhead'
     * @return void
     */
    public function setContentFormat($format)
    {
        if (ArrayUtility::inArray(['plain', 'xml', 'json', 'jsonhead', 'jsonbody', 'javascript'], $format)) {
            $this->contentFormat = $format;
        }
    }

    /**
     * Specifies the wrap to be used if contentFormat is "javascript".
     * The wrap used by default stores the results in a variable "response" and
     * adds <script>-Tags around it.
     *
     * @param string $javascriptCallbackWrap The javascript callback wrap to be used
     * @return void
     */
    public function setJavascriptCallbackWrap($javascriptCallbackWrap)
    {
        $this->javascriptCallbackWrap = $javascriptCallbackWrap;
    }

    /**
     * Sets an error message and the error flag
     *
     * @param string $errorMsg The error message
     * @return void
     */
    public function setError($errorMsg = '')
    {
        $this->errorMessage = $errorMsg;
        $this->isError = true;
    }

    /**
     * Checks whether an error occurred during the execution or not
     *
     * @return bool Whether this AJAX call had errors
     */
    public function isError()
    {
        return $this->isError;
    }

    /**
     * Renders the AJAX call based on the $contentFormat variable and exits the request
     *
     * @return ResponseInterface|NULL
     */
    public function render()
    {
        if ($this->isError) {
            return $this->renderAsError();
        }
        switch ($this->contentFormat) {
            case 'jsonhead':
            case 'jsonbody':
            case 'json':
                return $this->renderAsJSON();
                break;
            case 'javascript':
                return $this->renderAsJavascript();
                break;
            case 'xml':
                return $this->renderAsXML();
                break;
            default:
                return $this->renderAsPlain();
        }
    }

    /**
     * Renders the AJAX call in XML error style to handle with JS
     * the "responseXML" of the transport object will be filled with the error message then
     *
     * @return ResponseInterface
     */
    protected function renderAsError()
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response = $response
            ->withStatus(500, ' (AJAX)')
            ->withHeader('Content-type', 'text/xml; charset=utf-8')
            ->withHeader('X-JSON', 'false');

        $response->getBody()->write('<t3err>' . htmlspecialchars($this->errorMessage) . '</t3err>');
        return $response;
    }

    /**
     * Renders the AJAX call with text/html headers
     * the content will be available in the "responseText" value of the transport object
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    protected function renderAsPlain()
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response = $response
            ->withHeader('Content-type', 'text/html; charset=utf-8')
            ->withHeader('X-JSON', 'true');

        $response->getBody()->write(implode('', $this->content));
        return $response;
    }

    /**
     * Renders the AJAX call with text/xml headers
     * the content will be available in the "responseXML" value of the transport object
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    protected function renderAsXML()
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response = $response
            ->withHeader('Content-type', 'text/xml; charset=utf-8')
            ->withHeader('X-JSON', 'true');

        $response->getBody()->write(implode('', $this->content));
        return $response;
    }

    /**
     * Renders the AJAX call with JSON evaluated headers
     * note that you need to have requestHeaders: {Accept: 'application/json'},
     * in your AJAX options of your AJAX request object in JS
     *
     * the content will be available
     * - in the second parameter of the onSuccess / onComplete callback
     * - and in the xhr.responseText as a string (except when contentFormat = 'jsonhead')
     * you can evaluate this in JS with xhr.responseText.evalJSON();
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    protected function renderAsJSON()
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        $response = $response->withHeader('Content-type', 'application/json; charset=utf-8');

        $content = json_encode($this->content);
        // Bring content in xhr.responseText except when in "json head only" mode
        if ($this->contentFormat === 'jsonhead') {
            $response = $response->withHeader('X-JSON', $content);
        } else {
            $response = $response->withHeader('X-JSON', 'true');
            $response->getBody()->write($content);
        }
        return $response;
    }

    /**
     * Renders the AJAX call as inline JSON inside a script tag. This is useful
     * when an iframe is used as the AJAX transport.
     *
     * @return ResponseInterface
     * @throws \InvalidArgumentException
     */
    protected function renderAsJavascript()
    {
        $response = GeneralUtility::makeInstance(Response::class);
        $response = $response->withHeader('Content-type', 'text/html; charset=utf-8');
        $response->getBody()->write(str_replace('|', json_encode($this->content), $this->javascriptCallbackWrap));
        return $response;
    }
}
