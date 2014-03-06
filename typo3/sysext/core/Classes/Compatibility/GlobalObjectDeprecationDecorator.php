<?php
namespace TYPO3\CMS\Core\Compatibility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CacheManagerGlobal will be instanciated and
 * passed to $GLOBALS['typo3CacheManager'] to not break
 * extension code
 *
 */
class GlobalObjectDeprecationDecorator {

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var string
	 */
	protected $deprecationMessage;

	/**
	 * @param string $className
	 * @param string $deprecationMessage
	 */
	public function __construct($className, $deprecationMessage = NULL) {
		$this->className = $className;
		$this->deprecationMessage = $deprecationMessage ?: 'Usage of $GLOBALS[\'typo3CacheManager\'] and $GLOBALS[\'typo3CacheFactory\'] are deprecated since 6.2 will be removed in two versions. Use \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\'' . $this->className . '\') or dependency injection to access the singletons.';
	}

	/**
	 * Calls decorated object and issues a deprecation message
	 *
	 * @param string $methodName
	 * @param array $arguments
	 * @return mixed
	 * @deprecated
	 */
	public function __call($methodName, $arguments) {
		GeneralUtility::deprecationLog($this->deprecationMessage);
		$decoratedObject = GeneralUtility::makeInstance($this->className);
		return call_user_func_array(array($decoratedObject, $methodName), $arguments);
	}
}