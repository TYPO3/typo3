<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Benjamin Mack <mack@xnos.org>
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
 * class to hold all the information about an AJAX call and send
 * the right headers for the request type
 *
 * @author	Benjamin Mack <mack@xnos.org>
 * @package	TYPO3
 * @subpackage	core
 */
class TYPO3AJAX {
	protected $ajaxId        = null;
	protected $errorMessage  = null;
	protected $isError       = false;
	protected $content       = array();
	protected $contentFormat = 'plain';
	protected $charset       = 'utf-8';
	protected $requestCharset = 'utf-8';
	protected $javascriptCallbackWrap = '
		<script type="text/javascript">
			/*<![CDATA[*/
			response = |;
			/*]]>*/
		</script>
	';

	/**
	 * sets the charset and the ID for the AJAX call
	 * due some charset limitations in Javascript (prototype uses encodeURIcomponent, which converts
	 * all data to utf-8), we need to detect if the encoding of the request differs from the
	 * backend encoding (e.g. forceCharset), and then convert all incoming data (_GET and _POST)
	 * in the expected backend encoding.
	 *
	 * @param	string		the AJAX id
	 * @return	void
	 */
	public function __construct($ajaxId) {

		if ($GLOBALS['LANG']->charSet != $this->charset) {
			$this->charset = $GLOBALS['LANG']->charSet;
		}

			// get charset from current AJAX request (which is expected to be utf-8)
		preg_match('/;\s*charset\s*=\s*([a-zA-Z0-9_-]*)/i', $_SERVER['CONTENT_TYPE'], $contenttype);
		$charset = $GLOBALS['LANG']->csConvObj->parse_charset($contenttype[1]);
		if ($charset && $charset != $this->requestCharset) {
			$this->requestCharset = $charset;
		}

				// if the AJAX request does not have the same encoding like the backend
				// we need to convert the POST and GET parameters in the right charset
		if ($this->charset != $this->requestCharset) {
			$GLOBALS['LANG']->csConvObj->convArray($_POST, $this->requestCharset, $this->charset);
			$GLOBALS['LANG']->csConvObj->convArray($_GET,  $this->requestCharset, $this->charset);
		}

		$this->ajaxId = $ajaxId;
	}


	/**
	 * returns the ID for the AJAX call
	 *
	 * @return	string		the AJAX id
	 */
	public function getAjaxID() {
		return $this->ajaxId;
	}


	/**
	 * overwrites the existing content with the first parameter
	 *
	 * @param	array		the new content
	 * @return	mixed		the old content as array; if the new content was not an array, false is returned
	 */
	public function setContent($content) {
		$oldcontent = false;
		if (is_array($content)) {
			$oldcontent = $this->content;
			$this->content = $content;
		}
		return $oldcontent;
	}


	/**
	 * adds new content
	 *
	 * @param	string		the new content key where the content should be added in the content array
	 * @param	string		the new content to add
	 * @return	mixed		the old content; if the old content didn't exist before, false is returned
	 */
	public function addContent($key, $content) {
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
	 * returns the content for the ajax call
	 *
	 * @return	mixed		the content for a specific key or the whole content
	 */
	public function getContent($key = '') {
		return ($key && array_key_exists($key, $this->content) ? $this->content[$key] : $this->content);
	}


	/**
	 * sets the content format for the ajax call
	 *
	 * @param	string		can be one of 'plain' (default), 'xml', 'json', 'javascript', 'jsonbody' or 'jsonhead'
	 * @return	void
	 */
	public function setContentFormat($format) {
		if (t3lib_div::inArray(array('plain', 'xml', 'json', 'jsonhead', 'jsonbody', 'javascript'), $format)) {
			$this->contentFormat = $format;
		}
	}

	/**
	 * Specifies the wrap to be used if contentFormat is "javascript".
	 * The wrap used by default stores the results in a variable "response" and
	 * adds <script>-Tags around it.
	 *
	 * @param string $javascriptCallbackWrap the javascript callback wrap to be used
	 * @return void
	 */
	public function setJavascriptCallbackWrap($javascriptCallbackWrap) {
		$this->javascriptCallbackWrap = $javascriptCallbackWrap;
	}

	/**
	 * sets an error message and the error flag
	 *
	 * @param	string		the error message
	 * @return	void
	 */
	public function setError($errorMsg = '') {
		$this->errorMessage = $errorMsg;
		$this->isError = true;
	}


	/**
	 * checks whether an error occured during the execution or not
	 *
	 * @return	boolean		whether this AJAX call had errors
	 */
	public function isError() {
		return $this->isError;
	}


	/**
	 * renders the AJAX call based on the $contentFormat variable and exits the request
	 *
	 * @return	void
	 */
	public function render() {
		if ($this->isError) {
			$this->renderAsError();
			exit;
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
		exit;
	}


	/**
	 * renders the AJAX call in XML error style to handle with JS
	 * the "responseXML" of the transport object will be filled with the error message then
	 *
	 * @return	void
	 */
	protected function renderAsError() {
		header(t3lib_utility_Http::HTTP_STATUS_500 . ' (AJAX)');
		header('Content-type: text/xml; charset='.$this->charset);
		header('X-JSON: false');
		die('<t3err>'.htmlspecialchars($this->errorMessage).'</t3err>');
	}


	/**
	 * renders the AJAX call with text/html headers
	 * the content will be available in the "responseText" value of the transport object
	 *
	 * @return	void
	 */
	protected function renderAsPlain() {
		header('Content-type: text/html; charset='.$this->charset);
		header('X-JSON: true');
		echo implode('', $this->content);
	}


	/**
	 * renders the AJAX call with text/xml headers
	 * the content will be available in the "responseXML" value of the transport object
	 *
	 * @return	void
	 */
	protected function renderAsXML() {
		header('Content-type: text/xml; charset='.$this->charset);
		header('X-JSON: true');
		echo implode('', $this->content);
	}


	/**
	 * renders the AJAX call with JSON evaluated headers
	 * note that you need to have requestHeaders: {Accept: 'application/json'},
	 * in your AJAX options of your AJAX request object in JS
	 *
	 * the content will be available
	 *    - in the second parameter of the onSuccess / onComplete callback (except when contentFormat = 'jsonbody')
	 *    - and in the xhr.responseText as a string (except when contentFormat = 'jsonhead')
	 *         you can evaluate this in JS with xhr.responseText.evalJSON();
	 *
	 * @return	void
	 */
	protected function renderAsJSON() {
			// if the backend does not run in UTF-8 then we need to convert it to unicode as
			// the json_encode method will return empty otherwise
		if ($this->charset != $this->requestCharset) {
			$GLOBALS['LANG']->csConvObj->convArray($this->content, $this->charset, $this->requestCharset);
		}

		$content = json_encode($this->content);

		header('Content-type: application/json; charset='.$this->requestCharset);
		header('X-JSON: '.($this->contentFormat != 'jsonbody' ? $content : true));

			// bring content in xhr.responseText except when in "json head only" mode
		if ($this->contentFormat != 'jsonhead') {
			echo $content;
		}
	}

	/**
	 * Renders the AJAX call as inline JSON inside a script tag. This is useful
	 * when an iframe is used as the AJAX transport.
	 *
	 * @return	 void
	 */
	protected function renderAsJavascript() {
			// if the backend does not run in UTF-8 then we need to convert it to unicode as
			// the json_encode method will return empty otherwise
		if ($this->charset != $this->requestCharset) {
			$GLOBALS['LANG']->csConvObj->convArray($this->content, $this->charset, $this->requestCharset);
		}

		$content = str_replace('|', json_encode($this->content), $this->javascriptCallbackWrap);

		header('Content-type: text/html; charset=' . $this->requestCharset);
		echo $content;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.typo3ajax.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.typo3ajax.php']);
}

?>