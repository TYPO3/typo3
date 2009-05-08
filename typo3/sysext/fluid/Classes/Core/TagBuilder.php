<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage Core
 * @version $Id$
 */

/**
 * Tag based view helper.
 * Sould be used as the base class for all view helpers which output simple tags, as it provides some
 * convenience methods to register default attributes, ...
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */

class Tx_Fluid_Core_TagBuilder {
	
	/**
	 * Name of the Tag to be rendered
	 *
	 * @var string
	 */
	protected $tagName = '';

	/**
	 * Content of the tag to be rendered
	 *
	 * @var string
	 */
	protected $content = '';

	/**
	 * Attributes of the tag to be rendered
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * Specifies whether this tag needs a closing tag.
	 * E.g. <textarea> cant be self-closing even if its empty
	 *
	 * @var boolean
	 */
	protected $forceClosingTag = FALSE;

	/**
	 * Constructor
	 *
	 * @param string $tagName name of the tag to be rendered
	 * @param string $tagContent content of the tag to be rendered
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct($tagName = '', $tagContent = '') {
		$this->setTagName($tagName);
		$this->setContent($tagContent);
	}

	/**
	 * Sets the tag name
	 *
	 * @param string $tagName name of the tag to be rendered
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setTagName($tagName) {
		$this->tagName = $tagName;
	}

	/**
	 * Gets the tag name
	 *
	 * @return string tag name of the tag to be rendered
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getTagName() {
		return $this->tagName;
	}

	/**
	 * Sets the content of the tag
	 *
	 * @param string $tagContent content of the tag to be rendered
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setContent($tagContent, $escapeSpecialCharacters = TRUE) {
		if ($escapeSpecialCharacters) {
			$tagContent = htmlspecialchars($tagContent);
		}
		$this->content = $tagContent;
	}

	/**
	 * Gets the content of the tag
	 *
	 * @return string content of the tag to be rendered
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Returns TRUE if tag contains content, otherwise FALSE
	 *
	 * @return boolean TRUE if tag contains text, otherwise FALSE
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function hasContent() {
		return $this->content !== '';
	}

	/**
	 * Set this to TRUE to force a closing tag
	 * E.g. <textarea> cant be self-closing even if its empty
	 *
	 * @param boolean $forceClosingTag
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function forceClosingTag($forceClosingTag) {
		$this->forceClosingTag = $forceClosingTag;
	}

	/**
	 * Adds an attribute to the $attributes-collection
	 *
	 * @param string $attributeName name of the attribute to be added to the tag
	 * @param string $attributeValue attribute value
	 * @param boolean $escapeSpecialCharacters apply htmlspecialchars to attribute value
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function addAttribute($attributeName, $attributeValue, $escapeSpecialCharacters = TRUE) {
		if ($escapeSpecialCharacters) {
			$attributeValue = htmlspecialchars($attributeValue);
		}
		$this->attributes[$attributeName] = $attributeValue;
	}

	/**
	 * Adds attributes to the $attributes-collection
	 *
	 * @param array $attributes collection of attributes to add. key = attribute name, value = attribute value
	 * @param boolean $escapeSpecialCharacters apply htmlspecialchars to attribute values#
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function addAttributes(array $attributes, $escapeSpecialCharacters = TRUE) {
		foreach($attributes as $attributeName => $attributeValue) {
			$this->addAttribute($attributeName, $attributeValue, $escapeSpecialCharacters);
		}
	}

	/**
	 * Removes an attribute from the $attributes-collection
	 *
	 * @param string $attributeName name of the attribute to be removed from the tag
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function removeAttribute($attributeName) {
		unset($this->attributes[$attributeName]);
	}

	/**
	 * Renders and returns the tag
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render() {
		if (empty($this->tagName)) {
			return '';
		}
		$output = '<' . $this->tagName;
		foreach($this->attributes as $attributeName => $attributeValue) {
			$output.= ' ' . $attributeName . '="' . $attributeValue . '"';
		}
		if ($this->hasContent() || $this->forceClosingTag) {
			$output .= '>' . $this->content . '</' . $this->tagName . '>';
		} else {
			$output .= ' />';
		}
		return $output;
	}
}
?>
