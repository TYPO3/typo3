<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2004-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains the class "t3lib_ajax" containing functions for doing XMLHTTP requests
 * to the TYPO3 backend and as well for generating replys. This technology is also known as ajax.
 * Call ALL methods without making an object!
 *
 * IMPORTANT NOTICE: The API the class provides is still NOT STABLE and SUBJECT TO CHANGE!
 * It is planned to integrate an external AJAX library, so the API will most likely change again.
 *
 * TYPO3 XMLHTTP class (new in TYPO3 4.0.0)
 * This class contains two main parts:
 * (1) generation of JavaScript code which creates an XMLHTTP object in a cross-browser manner
 * (2) generation of XML data as a reply
 *
 * @author Sebastian Kurfürst <sebastian@garbage-group.de>
 * @deprecated since 6.0, the class will be removed from core with 6.2
 */
class t3lib_ajax {

	/**
	 * Default constructor writes deprecation log.
	 */
	public function __construct() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('Class t3lib_ajax is deprecated and unused since TYPO3 6.0. ' . 'It will be removed with version 6.2.');
	}

	/**
	 * Gets the JavaScript code needed to handle an XMLHTTP request in the frontend.
	 * All JS functions have to call ajax_doRequest(url) to make a request to the server.
	 * USE:
	 * See examples of using this function in template.php -> getContextMenuCode and alt_clickmenu.php -> printContent
	 *
	 * @param string $handler Function JS function handling the XML data from the server. That function gets the returned XML data as parameter.
	 * @param string $fallback JS fallback function which is called with the URL of the request in case ajax is not available.
	 * @param boolean $debug If set to 1, the returned XML data is outputted as text in an alert window - useful for debugging, PHP errors are shown there, ...
	 * @return string JavaScript code needed to make and handle an XMLHTTP request
	 * @deprecated since 6.0, class will be removed with 6.2
	 */
	public function getJScode($handlerFunction, $fallback = '', $debug = 0) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		// Init the XMLHTTP request object
		$code = '
		function ajax_initObject() {
			var A;
			try	{
				A=new ActiveXObject("Msxml2.XMLHTTP");
			} catch (e) {
				try	{
					A=new ActiveXObject("Microsoft.XMLHTTP");
				} catch (oc) {
					A=null;
				}
			}
			if (!A && typeof XMLHttpRequest != "undefined") {
				A = new XMLHttpRequest();
			}
			return A;
		}';
		// In case AJAX is not available, fallback function
		if ($fallback) {
			$fallback .= '(url)';
		} else {
			$fallback = 'return';
		}
		$code .= '
		function ajax_doRequest(url) {
			var x;

			x = ajax_initObject();
			if (!x) {
				' . $fallback . ';
			}
			x.open("GET", url, true);

			x.onreadystatechange = function() {
				if (x.readyState != 4) {
					return;
				}
				' . ($debug ? 'alert(x.responseText)' : '') . '
				var xmldoc = x.responseXML;
				var t3ajax = xmldoc.getElementsByTagName("t3ajax")[0];
				' . $handlerFunction . '(t3ajax);
			}
			x.send("");

			delete x;
		}';
		return $code;
	}

	/**
	 * Function outputting XML data for TYPO3 ajax. The function directly outputs headers and content to the browser.
	 *
	 * @param string $innerXML XML data which will be sent to the browser
	 * @return void
	 * @deprecated since 6.0, class will be removed with 6.2
	 */
	public function outputXMLreply($innerXML) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		// AJAX needs some XML data
		header('Content-Type: text/xml');
		$xml = '<?xml version="1.0"?>
<t3ajax>' . $innerXML . '</t3ajax>';
		echo $xml;
	}

}


?>