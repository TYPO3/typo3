<?php
namespace TYPO3\CMS\Core\Compatibility;

/**
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