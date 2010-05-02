<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
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
 * Tag builder. Can be easily accessed in TagBasedViewHelper
 *
 * @version $Id: TagBuilder.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage Core\ViewHelper
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 * @scope prototype
 */
class Tx_Fluid_Core_ViewHelper_TagBuilder {

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
	 * @api
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
	 * @api
	 */
	public function setTagName($tagName) {
		$this->tagName = $tagName;
	}

	/**
	 * Gets the tag name
	 *
	 * @return string tag name of the tag to be rendered
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
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
	 * @api
	 */
	public function setContent($tagContent) {
		$this->content = $tagContent;
	}

	/**
	 * Gets the content of the tag
	 *
	 * @return string content of the tag to be rendered
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * Returns TRUE if tag contains content, otherwise FALSE
	 *
	 * @return boolean TRUE if tag contains text, otherwise FALSE
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function hasContent() {
		if ($this->content === NULL) {
			return FALSE;
		}
		return $this->content !== '';
	}

	/**
	 * Set this to TRUE to force a closing tag
	 * E.g. <textarea> cant be self-closing even if its empty
	 *
	 * @param boolean $forceClosingTag
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
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
	 * @api
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
	 * @api
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
	 * @api
	 */
	public function removeAttribute($attributeName) {
		unset($this->attributes[$attributeName]);
	}

	/**
	 * Resets the TagBuilder by setting all members to their default value
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function reset() {
		$this->tagName = '';
		$this->content = '';
		$this->attributes = array();
		$this->forceClosingTag = FALSE;
	}

	/**
	 * Renders and returns the tag
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function render() {
		if (empty($this->tagName)) {
			return '';
		}
		$output = '<' . $this->tagName;
		foreach($this->attributes as $attributeName => $attributeValue) {
			$output .= ' ' . $attributeName . '="' . $attributeValue . '"';
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