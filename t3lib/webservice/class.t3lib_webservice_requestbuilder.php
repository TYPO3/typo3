<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This is a factory for building request objects
 *
 * @package TYPO3
 * @subpackage Webservice
 * @scope prototype
 * @entity
 * @api
 */
class t3lib_webservice_requestbuilder {

	/**
	 * The build method for a web request
	 *
	 * @param array $resolvedArguments
	 * @return t3lib_webservice_Request
	 */
	public  function build(array $resolvedArguments = array()) {
		/** @var t3lib_webservice_Request $request */
		$request = t3lib_div::makeInstance('t3lib_webservice_Request', $resolvedArguments);

		/** @var t3lib_webservice_Uri $requestUri */
		$requestUri = t3lib_div::makeInstance('t3lib_webservice_Uri' ,str_replace('/index.php' , '', t3lib_div::getIndpEnv('TYPO3_REQUEST_URL')));
		$request->setRequestUri($requestUri);

		/** @var t3lib_webservice_Uri $baseUri */
		$baseUri = clone $requestUri;
		$baseUri->setQuery(NULL);
		$baseUri->setFragment(NULL);
		$baseUri->setPath(t3lib_div::getIndpEnv('TYPO3_SITE_PATH'));
		$request->setBaseUri($baseUri);

		$request->setMethod(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL);

		$this->setArgumentsFromRawRequestData($request);

		$request->setAcceptHeaders($this->getAcceptHeaders());

		$request->setBody(file_get_contents('php://input'));

		return $request;
	}


	/**
	 * Parses the accept headers and returns an array with resolved priorities
	 *
	 * @param string $header
	 * @return array
	 */
	protected function getAcceptHeaders($header = NULL) {
		$toret = array();
		$header = $header ? $header : (array_key_exists('HTTP_ACCEPT', $_SERVER) ? $_SERVER['HTTP_ACCEPT'] : NULL);
		if ($header) {
			$types = explode(',', $header);
			$types = array_map('trim', $types);
			foreach ($types as $one_type) {
				$one_type = explode(';', $one_type);
				$type = array_shift($one_type);
				if ($type) {
					list($precedence, $tokens) = $this->getAcceptHeaderOptions($one_type);
					list($main_type, $sub_type) = array_map('trim', explode('/', $type));
					$toret[] = array(
						'main_type' => $main_type,
						'sub_type' => $sub_type,
						'precedence' => (float) $precedence,
						'tokens' => $tokens
					);
				}
			}
//			usort($toret, array('Parser', 'compare_media_ranges'));
		}
		return $toret;
	}

	/**
	 * @param mixed $typeOptions
	 * @return array
	 */
	protected function getAcceptHeaderOptions($typeOptions) {
		$precedence = 1;
		$tokens = array();
		if (is_string($typeOptions)) {
			$typeOptions = explode(';', $typeOptions);
		}
		$typeOptions = array_map('trim', $typeOptions);
		foreach ($typeOptions as $option) {
			$option = explode('=', $option);
			$option = array_map('trim', $option);
			if ($option[0] == 'q') {
				$precedence = $option[1];
			} else {
				$tokens[$option[0]] = $option[1];
			}
		}
		$tokens = count ($tokens) ? $tokens : false;
		return array($precedence, $tokens);
	}

	/**
	 * Takes the raw request data and - depending on the request method
	 * maps them into the request object. Afterwards all mapped arguments
	 * can be retrieved by the getArgument(s) method, no matter if they
	 * have been GET, POST or PUT arguments before.
	 *
	 * @param t3lib_webservice_Request $request The web request which will contain the arguments
	 * @return void
	 */
	protected function setArgumentsFromRawRequestData(t3lib_webservice_Request $request) {
		$arguments = t3lib_div::_GET();
		if ($request->getMethod() === 'POST') {
			$postArguments = t3lib_div::_POST();
			$arguments = t3lib_div::array_merge_recursive_overrule($arguments, $postArguments);

//			$uploadArguments = $this->environment->getUploadedFiles();
//			$arguments = \TYPO3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($arguments, $uploadArguments);
		}
		$request->setArguments($arguments);
	}

}

?>