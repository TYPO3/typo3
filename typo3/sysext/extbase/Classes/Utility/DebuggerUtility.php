<?php
namespace TYPO3\CMS\Extbase\Utility;

/*                                                                        *
 * This script belongs to the Extbase framework                           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * This class is a backport of the corresponding class of TYPO3 Flow.
 * All credits go to the TYPO3 Flow team.
 */
/**
 * A debugging utility class
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class DebuggerUtility {

	const PLAINTEXT_INDENT = '   ';
	const HTML_INDENT = '&nbsp;&nbsp;&nbsp;';

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage
	 */
	static protected $renderedObjects;

	/**
	 * Hardcoded list of Extbase class names (regex) which should not be displayed during debugging
	 *
	 * @var array
	 */
	static protected $blacklistedClassNames = array(
		'PHPUnit_Framework_MockObject_InvocationMocker',
		'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\IdentityMap',
		'TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService',
		'TYPO3\\CMS\\Extbase\\Object\\ObjectManager',
		'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Mapper\\DataMapper',
		'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager',
		'TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Qom\\QueryObjectModelFactory',
		'TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'
	);

	/**
	 * Hardcoded list of property names (regex) which should not be displayed during debugging
	 *
	 * @var array
	 */
	static protected $blacklistedPropertyNames = array('warning');

	/**
	 * Is set to TRUE once the CSS file is included in the current page to prevent double inclusions of the CSS file.
	 *
	 * @var boolean
	 */
	static protected $stylesheetEchoed = FALSE;

	/**
	 * Defines the max recursion depth of the dump, set to 8 due to common memory limits
	 *
	 * @var int
	 */
	static protected $maxDepth = 8;

	/**
	 * Clear the state of the debugger
	 *
	 * @return void
	 */
	static protected function clearState() {
		self::$renderedObjects = new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
	}

	/**
	 * Renders a dump of the given value
	 *
	 * @param mixed $value
	 * @param integer $level
	 * @param boolean $plainText
	 * @param boolean $ansiColors
	 * @return string
	 */
	static protected function renderDump($value, $level, $plainText, $ansiColors) {
		$dump = '';
		if (is_string($value)) {
			$croppedValue = strlen($value) > 2000 ? substr($value, 0, 2000) . '...' : $value;
			if ($plainText) {
				$dump = self::ansiEscapeWrap(('"' . implode((PHP_EOL . str_repeat(self::PLAINTEXT_INDENT, ($level + 1))), str_split($croppedValue, 76)) . '"'), '33', $ansiColors) . ' (' . strlen($value) . ' chars)';
			} else {
				$dump = sprintf('\'<span class="debug-string">%s</span>\' (%s chars)', implode('<br />' . str_repeat(self::HTML_INDENT, ($level + 1)), str_split(htmlspecialchars($croppedValue), 76)), strlen($value));
			}
		} elseif (is_numeric($value)) {
			$dump = sprintf('%s (%s)', self::ansiEscapeWrap($value, '35', $ansiColors), gettype($value));
		} elseif (is_bool($value)) {
			$dump = $value ? self::ansiEscapeWrap('TRUE', '32', $ansiColors) : self::ansiEscapeWrap('FALSE', '32', $ansiColors);
		} elseif (is_null($value) || is_resource($value)) {
			$dump = gettype($value);
		} elseif (is_array($value)) {
			$dump = self::renderArray($value, $level + 1, $plainText, $ansiColors);
		} elseif (is_object($value)) {
			$dump = self::renderObject($value, $level + 1, $plainText, $ansiColors);
		}
		return $dump;
	}

	/**
	 * Renders a dump of the given array
	 *
	 * @param array|\Traversable $array
	 * @param integer $level
	 * @param boolean $plainText
	 * @param boolean $ansiColors
	 * @return string
	 */
	static protected function renderArray($array, $level, $plainText = FALSE, $ansiColors = FALSE) {
		$content = '';
		$count = count($array);

		if ($plainText) {
			$header = self::ansiEscapeWrap('array', '36', $ansiColors);
		} else {
			$header = '<span class="debug-type">array</span>';
		}
		$header .= $count > 0 ? '(' . $count . ' item' . ($count > 1 ? 's' : '') . ')' : '(empty)';
		if ($level >= self::$maxDepth) {
			if ($plainText) {
				$header .= ' ' . self::ansiEscapeWrap('max depth', '47;30', $ansiColors);
			} else {
				$header .= '<span class="debug-filtered">max depth</span>';
			}
		} else {
			$content = self::renderCollection($array, $level, $plainText, $ansiColors);
			if (!$plainText) {
				$header = ($level > 1 && $count > 0 ? '<input type="checkbox" /><span class="debug-header" >' : '<span>') . $header . '</span >';
			}
		}
		if ($level > 1 && $count > 0 && !$plainText) {
			$dump = '<span class="debug-tree">' . $header . '<span class="debug-content">' . $content . '</span></span>';
		} else {
			$dump = $header . $content;
		}
		return $dump;
	}

	/**
	 * Renders a dump of the given object
	 *
	 * @param object $object
	 * @param integer $level
	 * @param boolean $plainText
	 * @param boolean $ansiColors
	 * @return string
	 */
	static protected function renderObject($object, $level, $plainText = FALSE, $ansiColors = FALSE) {
		if ($object instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
			$object = $object->_loadRealInstance();
		}
		$header = self::renderHeader($object, $level, $plainText, $ansiColors);
		if ($level < self::$maxDepth && !self::isBlacklisted($object) && !(self::isAlreadyRendered($object) && $plainText !== TRUE)) {
			$content = self::renderContent($object, $level, $plainText, $ansiColors);
		} else {
			$content = '';
		}
		if ($plainText) {
			return $header . $content;
		} else {
			return '<span class="debug-tree">' . $header . '<span class="debug-content">' . $content . '</span></span>';
		}
	}

	/**
	 * Checks if a given object or property should be excluded/filtered
	 *
	 * @param object $value An ReflectionProperty or other Object
	 * @return bool TRUE if the given object should be filtered
	 */
	static protected function isBlacklisted($value) {
		$result = FALSE;
		if ($value instanceof \ReflectionProperty) {
			$result = (strpos(implode('|', self::$blacklistedPropertyNames), $value->getName()) > 0);
		} elseif (is_object($value)) {
			$result = (strpos(implode('|', self::$blacklistedClassNames), get_class($value)) > 0);
		}
		return $result;
	}

	/**
	 * Checks if a given object was already rendered.
	 *
	 * @param object $object
	 * @return bool TRUE if the given object was already rendered
	 */
	static protected function isAlreadyRendered($object) {
		return self::$renderedObjects->contains($object);
	}

	/**
	 * Renders the header of a given object/collection. It is usually the class name along with some flags.
	 *
	 * @param object $object
	 * @param integer $level
	 * @param boolean $plainText
	 * @param boolean $ansiColors
	 * @return string The rendered header with tags
	 */
	static protected function renderHeader($object, $level, $plainText, $ansiColors) {
		$dump = '';
		$persistenceType = '';
		$className = get_class($object);
		$classReflection = new \ReflectionClass($className);
		if ($plainText) {
			$dump .= self::ansiEscapeWrap($className, '36', $ansiColors);
		} else {
			$dump .= '<span class="debug-type">' . $className . '</span>';
		}
		if ($object instanceof \TYPO3\CMS\Core\SingletonInterface) {
			$scope = 'singleton';
		} else {
			$scope = 'prototype';
		}
		if ($plainText) {
			$dump .= ' ' . self::ansiEscapeWrap($scope, '44;37', $ansiColors);
		} else {
			$dump .= $scope ? '<span class="debug-scope">' . $scope . '</span>' : '';
		}
		if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject) {
			if ($object->_isDirty()) {
				$persistenceType = 'modified';
			} elseif ($object->_isNew()) {
				$persistenceType = 'transient';
			} else {
				$persistenceType = 'persistent';
			}
		}
		if ($object instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage && $object->_isDirty()) {
			$persistenceType = 'modified';
		}
		if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
			$domainObjectType = 'entity';
		} elseif ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject) {
			$domainObjectType = 'valueobject';
		} else {
			$domainObjectType = 'object';
		}
		if ($plainText) {
			$dump .= ' ' . self::ansiEscapeWrap(($persistenceType . ' ' . $domainObjectType), '42;30', $ansiColors);
		} else {
			$dump .= '<span class="debug-ptype">' . ($persistenceType ? $persistenceType . ' ' : '') . $domainObjectType . '</span>';
		}
		if (strpos(implode('|', self::$blacklistedClassNames), get_class($object)) > 0) {
			if ($plainText) {
				$dump .= ' ' . self::ansiEscapeWrap('filtered', '47;30', $ansiColors);
			} else {
				$dump .= '<span class="debug-filtered">filtered</span>';
			}
		} elseif (self::$renderedObjects->contains($object) && !$plainText) {
			$dump = '<a href="javascript:;" onclick="document.location.hash=\'#' . spl_object_hash($object) . '\';" class="debug-seeabove">' . $dump . '<span class="debug-filtered">see above</span></a>';
		} elseif ($level >= self::$maxDepth && !$object instanceof \DateTime) {
			if ($plainText) {
				$dump .= ' ' . self::ansiEscapeWrap('max depth', '47;30', $ansiColors);
			} else {
				$dump .= '<span class="debug-filtered">max depth</span>';
			}
		} elseif ($level > 1 && !$object instanceof \DateTime && !$plainText) {
			if (($object instanceof \Countable && count($object) === 0) || (count($classReflection->getProperties()) === 0)) {
				$dump = '<span>' . $dump . '</span>';
			} else {
				$dump = '<input type="checkbox" id="' . spl_object_hash($object) . '" /><span class="debug-header">' . $dump . '</span>';
			}
		}
		if ($object instanceof \Countable) {
			$dump .= count($object) > 0 ? ' (' . count($object) . ' items)' : ' (empty)';
		}
		if ($object instanceof \DateTime) {
			$dump .= ' (' . $object->format(\DateTime::RFC3339) . ', ' . $object->getTimestamp() . ')';
		}
		if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface && !$object->_isNew()) {
			$dump .= ' (uid=' . $object->getUid() . ', pid=' . $object->getPid() . ')';
		}
		return $dump;
	}

	/**
	 * @param object $object
	 * @param integer $level
	 * @param boolean $plainText
	 * @param boolean $ansiColors
	 * @return string The rendered body content of the Object(Storage)
	 */
	static protected function renderContent($object, $level, $plainText, $ansiColors) {
		$dump = '';
		if ($object instanceof \TYPO3\CMS\Extbase\Persistence\ObjectStorage || $object instanceof \Iterator || $object instanceof \ArrayObject) {
			$dump .= self::renderCollection($object, $level, $plainText, $ansiColors);
		} else {
			self::$renderedObjects->attach($object);
			if (!$plainText) {
				$dump .= '<a name="' . spl_object_hash($object) . '" id="' . spl_object_hash($object) . '"></a>';
			}
			$classReflection = new \ReflectionClass(get_class($object));
			$properties = $classReflection->getProperties();
			foreach ($properties as $property) {
				if (self::isBlacklisted($property)) {
					continue;
				}
				$dump .= PHP_EOL . str_repeat(self::PLAINTEXT_INDENT, $level) . ($plainText ? '' : '<span class="debug-property">') . self::ansiEscapeWrap($property->getName(), '37', $ansiColors) . ($plainText ? '' : '</span>') . ' => ';
				$property->setAccessible(TRUE);
				$dump .= self::renderDump($property->getValue($object), $level, $plainText, $ansiColors);
				if ($object instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject && !$object->_isNew() && $object->_isDirty($property->getName())) {
					if ($plainText) {
						$dump .= ' ' . self::ansiEscapeWrap('modified', '43;30', $ansiColors);
					} else {
						$dump .= '<span class="debug-dirty">modified</span>';
					}
				}
			}
		}
		return $dump;
	}

	/**
	 * @param mixed $collection
	 * @param integer $level
	 * @param boolean $plainText
	 * @param boolean $ansiColors
	 * @return string
	 */
	static protected function renderCollection($collection, $level, $plainText, $ansiColors) {
		$dump = '';
		foreach ($collection as $key => $value) {
			$dump .= PHP_EOL . str_repeat(self::PLAINTEXT_INDENT, $level) . ($plainText ? '' : '<span class="debug-property">') . self::ansiEscapeWrap($key, '37', $ansiColors) . ($plainText ? '' : '</span>') . ' => ';
			$dump .= self::renderDump($value, $level, $plainText, $ansiColors);
		}
		if ($collection instanceof \Iterator) {
			$collection->rewind();
		}
		return $dump;
	}

	/**
	 * Wrap a string with the ANSI escape sequence for colorful output
	 *
	 * @param string $string The string to wrap
	 * @param string $ansiColors The ansi color sequence (e.g. "1;37")
	 * @param boolean $enable If FALSE, the raw string will be returned
	 * @return string The wrapped or raw string
	 */
	static protected function ansiEscapeWrap($string, $ansiColors, $enable = TRUE) {
		if ($enable) {
			return '[' . $ansiColors . 'm' . $string . '[0m';
		} else {
			return $string;
		}
	}

	/**
	 * A var_dump function optimized for Extbase's object structures
	 *
	 * @param mixed $variable The value to dump
	 * @param string $title optional custom title for the debug output
	 * @param integer $maxDepth Sets the max recursion depth of the dump. De- or increase the number according to your needs and memory limit.
	 * @param boolean $plainText If TRUE, the dump is in plain text, if FALSE the debug output is in HTML format.
	 * @param boolean $ansiColors If TRUE (default), ANSI color codes is added to the output, if FALSE the debug output not colored.
	 * @param boolean $return if TRUE, the dump is returned for custom post-processing (e.g. embed in custom HTML). If FALSE (default), the dump is directly displayed.
	 * @param array $blacklistedClassNames An array of class names (RegEx) to be filtered. Default is an array of some common class names.
	 * @param array $blacklistedPropertyNames An array of property names and/or array keys (RegEx) to be filtered. Default is an array of some common property names.
	 * @return string if $return is TRUE, the dump is returned. By default, the dump is directly displayed, and nothing is returned.
	 * @api
	 */
	static public function var_dump($variable, $title = NULL, $maxDepth = 8, $plainText = FALSE, $ansiColors = TRUE, $return = FALSE, $blacklistedClassNames = NULL, $blacklistedPropertyNames = NULL) {
		self::$maxDepth = $maxDepth;
		if ($title === NULL) {
			$title = 'Extbase Variable Dump';
		}
		$ansiColors = $plainText && $ansiColors;
		if ($ansiColors === TRUE) {
			$title = '[1m' . $title . '[0m';
		}
		if (is_array($blacklistedClassNames)) {
			self::$blacklistedClassNames = $blacklistedClassNames;
		}
		if (is_array($blacklistedPropertyNames)) {
			self::$blacklistedPropertyNames = $blacklistedPropertyNames;
		}
		self::clearState();
		if (!$plainText && self::$stylesheetEchoed === FALSE) {
			echo '
				<style type=\'text/css\'>
					.debug-tree{position:relative;}
					.debug-tree input{position:absolute;top:0;left:0;cursor:pointer;opacity:0;z-index:2;}
					.debug-tree input ~ .debug-content{display:none;}
					.debug-tree .debug-header:before{content:"+";padding:0 2px 0 2px;margin:0 3px 0 3px;font-size:1em;font-weight:bold;color:#004fb0;border:1px #004fb0 solid;}
					.debug-tree input:checked ~ .debug-content{display:inline;}
					.debug-tree input:checked ~ .debug-header:before{content:"-";}
					.Extbase-Utility-Debugger-VarDump{display:block;text-align:left;background:#b9b9b9;border:10px solid #b9b9b9;-moz-border-radius:10px;-webkit-border-radius:10px;border-radius:10px;-moz-box-shadow:0 0 20px #333;-webkit-box-shadow:0 0 20px #333;box-shadow:0 0 20px #333;z-index:999;color:#000;margin:20px 0 0;}
					.Extbase-Utility-Debugger-VarDump-Floating{position:relative;width:96%;margin:40px auto;}
					.Extbase-Utility-Debugger-VarDump-Top{background:#eee;font:normal bold 12px \'Lucida Grande\',sans-serif;padding:5px;}
					.Extbase-Utility-Debugger-VarDump-Center{background:#b9b9b9 url(data:image/gif;base64,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) 0 18px repeat;font:normal normal 11px/18px Monospaced,\'Lucida Console\',monospace;padding:18px 10px;}
					.Extbase-Utility-Debugger-VarDump-Center pre{background-color:transparent;margin:0;padding:0;}
					.Extbase-Utility-Debugger-VarDump-Center,.Extbase-Utility-Debugger-VarDump-Center pre,.Extbase-Utility-Debugger-VarDump-Center p,.Extbase-Utility-Debugger-VarDump-Center a,.Extbase-Utility-Debugger-VarDump-Center strong,.Extbase-Utility-Debugger-VarDump-Center .debug-string{font:normal normal 11px/18px Monospaced,\'Lucida Console\',monospace;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-string{color:#000;white-space:normal;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-type{color:#004fb0;padding-right:4px;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-unregistered{background-color:#dce1e8;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-scope,.Extbase-Utility-Debugger-VarDump-Center .debug-ptype,.Extbase-Utility-Debugger-VarDump-Center .debug-proxy,.Extbase-Utility-Debugger-VarDump-Center .debug-filtered{color:#FFF;font-size:10px;line-height:16px;padding:1px 4px;margin-right:2px;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-scope{background-color:#3e7fe1;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-ptype{background-color:#6FBC16;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-dirty{background-color:#FFFF00;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-filtered{background-color:#8c8c8c;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-seeabove{text-decoration:none;font-style:italic;font-weight:400;}
					.Extbase-Utility-Debugger-VarDump-Center .debug-property{color:#555;line-height:16px;padding:1px 2px;}
				</style>';
			self::$stylesheetEchoed = TRUE;
		}
		if ($plainText) {
			$output = $title . PHP_EOL . self::renderDump($variable, 0, TRUE, $ansiColors) . PHP_EOL . PHP_EOL;
		} else {
			$output = '
				<div class="Extbase-Utility-Debugger-VarDump ' . ($return ? 'Extbase-Utility-Debugger-VarDump-Inline' : 'Extbase-Utility-Debugger-VarDump-Floating') . '">
				<div class="Extbase-Utility-Debugger-VarDump-Top">' . htmlspecialchars($title) . '</div>
				<div class="Extbase-Utility-Debugger-VarDump-Center">
					<pre dir="ltr">' . self::renderDump($variable, 0, FALSE, FALSE) . '</pre>
				</div>
			</div>
			';
		}
		if ($return === TRUE) {
			return $output;
		} else {
			echo $output;
		}
		return '';
	}
}

?>