<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Nicolas Forgerit <nicolas.forgerit@gmail.com>
 *  	All rights reserved
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

class t3lib_webservice_app implements ArrayAccess  {
	
	/**
	 * This array holds all the registered modules, e.g.
	 * request, response, ...
	 */
	protected $registeredModules = array();
	
	/**
	 * This array holds the app's actions which are defined in
	 * the called extension's ext_api.php file.
	 * 
	 */
	protected $registeredActions = array();
	
	/**
	 * This is a regex pattern describing route prefixes
	 */
	const ROUTE_PREFIX = '\/api\/[_a-z0-9]+';
	
	/**
	 * This methods provide the DSL to register URI-HTTP-method combinations
	 *
	 * @param $key The function name (i.e. the registered HTTP method like GET, POST, ...) 
	 * @param $params The functions params which consist of the URI and the corr. lambda function
	 * @return void
	 */
	public function __call($key,$params) { 
		assert(count($params) === 2);
		
		$this->registeredActions[] = array(
			'method' => strtoupper($key), 
			'function' => $params[1],
			'uri' => self::ROUTE_PREFIX.$this->regexpify($params[0]),
		);
	}
	
	/**
	 * This is the entry point which runs the webservice application after all
	 * the functions have been registered.
	 */
	public function run() {
		$executed = false;
		foreach ($this->registeredActions as $action) {
			if (preg_match('~'.$action['uri'].'\/?$~', $this['request']->getRequestUri()->getPath(), $matches)
				&& $this['request']->getMethod() === $action['method']
			) {	
				array_shift($matches); 				
				call_user_func_array($action['function'], $matches);
				$executed = true;
				break;
			}
		}
		
		if (!$executed) {
			die("no corresponding action found :(");
		}
	}

	/**
	 * Currently, this mostly just kills the colons from the controller routes and
	 * replaces the route's :variables with a regexp word matcher string.
	 *
	 * @param string $string The regexpified string
	 * @return string The altered string 
	 */
	protected function regexpify($string) {
		$REGEX_PATTERN = '~^(.*)\/\:([-_a-zA-Z0-9]+)~';
		
			// need to use iteration since PCRE doesn't seem to support unlimited count of backreferences
		// TODO: prove that this always breaks
		while(preg_match($REGEX_PATTERN, $string, $matches)) {
			assert(count($matches) === 3);
			// $string = preg_replace('~(.*)\:([-_a-zA-Z0-9]+)(.*)~', '$1(?P<$2>[-_a-zA-Z0-9]+)$3', $string);
			$string = preg_replace($REGEX_PATTERN, '$1/([-_a-zA-Z0-9]+)', $string);
		}

		return $string;
	}
	
	// Implementing Array interface for Module registration
	public function offsetExists($offset) {
		return isset($this->registeredModules[$offset]);
	}
	
	public function offsetGet($offset) {
		return $this->registeredModules[$offset];
	}
	
	public function offsetSet($offset, $value) {
		$this->registeredModules[$offset] = $value;
	}
	
	public function offsetUnset($offset) {
		unset($this->registeredModules[$offset]);
	}
}
