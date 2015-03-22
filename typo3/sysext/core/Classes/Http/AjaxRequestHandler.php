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

use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * Class to hold all the information about an AJAX call and send
 * the right headers for the request type
 *
 * @author Benjamin Mack <mack@xnos.org>
 */
class AjaxRequestHandler {

	/**
	 * @var string|NULL
	 */
	protected $ajaxId = NULL;

	/**
	 * @var string|NULL
	 */
	protected $errorMessage = NULL;

	/**
	 * @var bool
	 */
	protected $isError = FALSE;

	/**
	 * @var array
	 */
	protected $content = array();

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
	public function __construct($ajaxId) {
		$this->ajaxId = $ajaxId;
	}

	/**
	 * Returns the ID for the AJAX call
	 *
	 * @return string The AJAX id
	 */
	public function getAjaxID() {
		return $this->ajaxId;
	}

	/**
	 * Overwrites the existing content with the data supplied
	 *
	 * @param array $content The new content
	 * @return mixed The old content as array; if the new content was not an array, FALSE is returned
	 */
	public function setContent($content) {
		$oldcontent = FALSE;
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
	public function addContent($key, $content) {
		$oldcontent = FALSE;
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
	public function getContent($key = '') {
		return $key && array_key_exists($key, $this->content) ? $this->content[$key] : $this->content;
	}

	/**
	 * Sets the content format for the ajax call
	 *
	 * @param string $format Can be one of 'plain' (default), 'xml', 'json', 'javascript', 'jsonbody' or 'jsonhead'
	 * @return void
	 */
	public function setContentFormat($format) {
		if (ArrayUtility::inArray(array('plain', 'xml', 'json', 'jsonhead', 'jsonbody', 'javascript'), $format)) {
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
	public function setJavascriptCallbackWrap($javascriptCallbackWrap) {
		$this->javascriptCallbackWrap = $javascriptCallbackWrap;
	}

	/**
	 * Sets an error message and the error flag
	 *
	 * @param string $errorMsg The error message
	 * @return void
	 */
	public function setError($errorMsg = '') {
		$this->errorMessage = $errorMsg;
		$this->isError = TRUE;
	}

	/**
	 * Checks whether an error occurred during the execution or not
	 *
	 * @return bool Whether this AJAX call had errors
	 */
	public function isError() {
		return $this->isError;
	}

	/**
	 * Renders the AJAX call based on the $contentFormat variable and exits the request
	 *
	 * @return void
	 */
	public function render() {
		if ($this->isError) {
			$this->renderAsError();
			die;
		}
		switch ($this->contentFormat) {
			case 'jsonhead':
			case 'jsonbody':
			case 'json':
				$this->renderAsJSON();
				break;
			case 'javascript':
				$this->renderAsJavascript();
				break;
			case 'xml':
				$this->renderAsXML();
				break;
			default:
				$this->renderAsPlain();
		}
		die;
	}

	/**
	 * Renders the AJAX call in XML error style to handle with JS
	 * the "responseXML" of the transport object will be filled with the error message then
	 *
	 * @return void
	 */
	protected function renderAsError() {
		header(\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_500 . ' (AJAX)');
		header('Content-type: text/xml; charset=utf-8');
		header('X-JSON: false');
		die('<t3err>' . htmlspecialchars($this->errorMessage) . '</t3err>');
	}

	/**
	 * Renders the AJAX call with text/html headers
	 * the content will be available in the "responseText" value of the transport object
	 *
	 * @return void
	 */
	protected function renderAsPlain() {
		header('Content-type: text/html; charset=utf-8');
		header('X-JSON: true');
		echo implode('', $this->content);
	}

	/**
	 * Renders the AJAX call with text/xml headers
	 * the content will be available in the "responseXML" value of the transport object
	 *
	 * @return void
	 */
	protected function renderAsXML() {
		header('Content-type: text/xml; charset=utf-8');
		header('X-JSON: true');
		echo implode('', $this->content);
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
	 * @return void
	 */
	protected function renderAsJSON() {
		$content = json_encode($this->content);
		header('Content-type: application/json; charset=utf-8');
		// Bring content in xhr.responseText except when in "json head only" mode
		if ($this->contentFormat === 'jsonhead') {
			header('X-JSON: ' . $content);
		} else {
			header('X-JSON: true');
			echo $content;
		}
	}

	/**
	 * Renders the AJAX call as inline JSON inside a script tag. This is useful
	 * when an iframe is used as the AJAX transport.
	 *
	 * @return void
	 */
	protected function renderAsJavascript() {
		$content = str_replace('|', json_encode($this->content), $this->javascriptCallbackWrap);
		header('Content-type: text/html; charset=utf-8');
		echo $content;
	}

}
