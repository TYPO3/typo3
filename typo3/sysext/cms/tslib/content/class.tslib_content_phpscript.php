<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
 *  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * Contains PHP_SCRIPT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class tslib_content_PhpScript extends tslib_content_Abstract {

	/**
	 * Rendering the cObject, PHP_SCRIPT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @deprecated since TYPO3 4.6, will be removed in TYPO3 4.8
	 */
	public function render($conf = array()) {
		$GLOBALS['TSFE']->logDeprecatedTyposcript(
			'PHP_SCRIPT',
			'Usage of PHP_SCRIPT is deprecated since TYPO3 4.6. Use plugins instead.'
		);

		$file = isset($conf['file.'])
			? $this->cObj->stdWrap($conf['file'], $conf['file.'])
			: $conf['file'];

		$incFile = $GLOBALS['TSFE']->tmpl->getFileName($file);
		$content = '';
		if ($incFile && $GLOBALS['TSFE']->checkFileInclude($incFile)) {
				// Added 31-12-00: Make backup...
			$this->cObj->oldData = $this->cObj->data;
			$RESTORE_OLD_DATA = FALSE;
				// Include file..
			include ('./' . $incFile);
				// Added 31-12-00: restore...
			if ($RESTORE_OLD_DATA) {
				$this->cObj->data = $this->cObj->oldData;
			}
		}

		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}

		return $content;
	}

	/**
	 * Allow access to other tslib_content methods.
	 *
	 * Provides backwards compatibility for older included PHP_SCRIPT which simply
	 * call methods like $this->typoLink (e.g. the old "languageMenu.php" sample).
	 *
	 * @deprecated since 4.5, will be removed in 4.7. Use $this->cObj-><method>() instead
	 *
	 * @param string $method The called method
	 * @param array $arguments The original arguments
	 * @return mixed
	 */
	public function __call($method, $arguments) {
		if (method_exists($this->cObj, $method)) {
			$trail = debug_backtrace();
			$location = $trail[1]['file'] . '#' . $trail[1]['line'];
			t3lib_div::deprecationLog(
				sprintf('%s: PHP_SCRIPT called $this->%s. Modify it to call $this->cObj->%s instead. Will be removed in 4.7.',
					$location, $method, $method
				)
			);
			return call_user_func_array(array($this->cObj, $method), $arguments);
		} else {
			trigger_error(sprintf('Call to undefined function: %s::%s().', get_class($this), $name), E_USER_ERROR);
		}
	}

	/**
	 * Allow access to other tslib_content variables.
	 *
	 * Provides backwards compatibility for PHP_SCRIPT which simply
	 * accesses properties like $this->parameters.
	 *
	 * @deprecated since 4.5, will be removed in 4.7. Use $this->cObj-><property> instead.
	 *
	 * @param string $name The name of the property
	 * @return mixed
	 */
	public function __get($name) {
		if (array_key_exists($name, get_object_vars($this->cObj))) {
			$trail = debug_backtrace();
			$location = $trail[1]['file'] . '#' . $trail[1]['line'];
			t3lib_div::deprecationLog(
				sprintf('%s: PHP_SCRIPT accessed $this->%s. Modify it to access $this->cObj->%s instead. Will be removed in 4.7.',
					$location, $name, $name
				)
			);
			return $this->cObj->$name;
		}
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_phpscript.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_content_phpscript.php']);
}

?>