<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
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
 * Extended version of the ReflectionProperty
 *
 * @package Extbase
 * @subpackage Reflection
 * @version $Id: PropertyReflection.php 1052 2009-08-05 21:51:32Z sebastian $
 */
class Tx_Extbase_Reflection_PropertyReflection extends ReflectionProperty {

	/**
	 * @var Tx_Extbase_Reflection_DocCommentParser: An instance of the doc comment parser
	 */
	protected $docCommentParser;

	/**
	 * The constructor, initializes the reflection class
	 *
	 * @param string $className Name of the property's class
	 * @param string $propertyName Name of the property to reflect
	 * @return void
	 */
	public function __construct($className, $propertyName) {
		parent::__construct($className, $propertyName);
	}

	/**
	 * Checks if the doc comment of this property is tagged with
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
	 * @return array Values of the given tag
	 */
	public function getTagValues($tag) {
		return $this->getDocCommentParser()->getTagValues($tag);
	}

	/**
	 * Returns the value of the reflected property - even if it is protected.
	 *
	 * @param object $object Instance of the declaring class Tx_Extbase_Reflection_to read the value from
	 * @return mixed Value of the property
	 * @throws Tx_Extbase_Reflection_Exception
	 * @todo Maybe support private properties as well, as of PHP 5.3.0 we can do
	 *   $obj = new Foo;
	 *   $prop = new ReflectionProperty('Foo', 'y'); // y is private member
	 *   $prop->setAccessible(true);
	 *   var_dump($prop->getValue($obj)); // int(2)
	 */
	public function getValue($object = NULL) {
		if (!is_object($object)) throw new Tx_Extbase_Reflection_Exception('$object is of type ' . gettype($object) . ', instance of class ' . $this->class . ' expected.', 1210859212);
		if ($this->isPublic()) return parent::getValue($object);
		if ($this->isPrivate()) throw new Tx_Extbase_Reflection_Exception('Cannot return value of private property "' . $this->name . '.', 1210859206);

		parent::setAccessible(TRUE);
		return parent::getValue($object);
	}

	/**
	 * Returns an instance of the doc comment parser and
	 * runs the parse() method.
	 *
	 * @return Tx_Extbase_Reflection_DocCommentParser
	 */
	protected function getDocCommentParser() {
		if (!is_object($this->docCommentParser)) {
			$this->docCommentParser = new Tx_Extbase_Reflection_DocCommentParser;
			$this->docCommentParser->parseDocComment($this->getDocComment());
		}
		return $this->docCommentParser;
	}
}

?>