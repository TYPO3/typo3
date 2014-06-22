<?php
namespace TYPO3\CMS\Extbase\Reflection;

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
/**
 * Extended version of the ReflectionMethod
 */
class MethodReflection extends \ReflectionMethod {

	/**
	 * @var DocCommentParser An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param string $className Name of the method's class
	 * @param string $methodName Name of the method to reflect
	 */
	public function __construct($className, $methodName) {
		parent::__construct($className, $methodName);
	}

	/**
	 * Returns the declaring class
	 *
	 * @return \TYPO3\CMS\Extbase\Reflection\ClassReflection The declaring class
	 */
	public function getDeclaringClass() {
		return new \TYPO3\CMS\Extbase\Reflection\ClassReflection(parent::getDeclaringClass()->getName());
	}

	/**
	 * Replacement for the original getParameters() method which makes sure
	 * that \TYPO3\CMS\Extbase\Reflection\ParameterReflection objects are returned instead of the
	 * orginal ReflectionParameter instances.
	 *
	 * @return array of \TYPO3\CMS\Extbase\Reflection\ParameterReflection Parameter reflection objects of the parameters of this method
	 */
	public function getParameters() {
		$extendedParameters = array();
		foreach (parent::getParameters() as $parameter) {
			$extendedParameters[] = new \TYPO3\CMS\Extbase\Reflection\ParameterReflection(array($this->getDeclaringClass()->getName(), $this->getName()), $parameter->getName());
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
	 */
	public function getTagsValues() {
		return $this->getDocCommentParser()->getTagsValues();
	}

	/**
	 * Returns the values of the specified tag
	 *
	 * @param string $tag Tag name to check for
	 * @return array Values of the given tag
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns the description part of the doc comment
	 *
	 * @return string Doc comment description
	 */
	public function getDescription() {
		return $this->getDocCommentParser()->getDescription();
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return \TYPO3\CMS\Extbase\Reflection\DocCommentParser
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new \TYPO3\CMS\Extbase\Reflection\DocCommentParser();
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}
