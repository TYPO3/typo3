<?php

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id: MethodReflection.php 1811 2009-01-28 12:04:49Z robert $
 */

/**
 * Extended version of the ReflectionMethod
 *
 * @package FLOW3
 * @subpackage Reflection
 * @version $Id: MethodReflection.php 1811 2009-01-28 12:04:49Z robert $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_ExtBase_Reflection_MethodReflection extends ReflectionMethod {

	/**
	 * @var Tx_ExtBase_Reflection_DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param  string $className Name of the method's class
	 * @param  string $methodName Name of the method to reflect
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className, $methodName) {
		parent::__construct($className, $methodName);
	}

	/**
	 * Returns the declaring class
	 *
	 * @return Tx_ExtBase_Reflection_ClassReflection The declaring class
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDeclaringClass() {
		return new Tx_ExtBase_Reflection_ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that Tx_ExtBase_Reflection_ParameterReflection objects are returned instead of the
	 * orginal ReflectionParameter instances.
	 *
	 * @return array of Tx_ExtBase_Reflection_ParameterReflection Parameter reflection objects of the parameters of this method
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getParameters() {
		$extendedParameters = array();
		foreach (parent::getParameters() as $parameter) {
			$extendedParameters[] = new Tx_ExtBase_Reflection_ParameterReflection(array($this->getDeclaringClass()->getName(), $this->getName()), $parameter->getName());
		}
		return $extendedParameters;
	}

	/**
	 * Checks if the doc comment of this method is tagged with
	 * the specified tag
	 *
	 * @param string $tag Tag name to check for
	 * @return boolean TRUE if such a tag has been defined, otherwise FALSE
	 */
	public function isTaggedWith($tag) {
		$result = $this->getDocCommentParser()->isTaggedWith($tag);
		return $result;
	}

	/**
	 * Returns an array of tags and their values
	 *
	 * @return array Tags and values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagsValues() {
		return $this->getDocCommentParser()->getTagsValues();
	}

	/**
	 * Returns the values of the specified tag
	 *
	 * @param string $tag Tag name to check for
	 * @return array Values of the given tag
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return Tx_ExtBase_Reflection_DocCommentParser
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new Tx_ExtBase_Reflection_DocCommentParser;
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}

?>