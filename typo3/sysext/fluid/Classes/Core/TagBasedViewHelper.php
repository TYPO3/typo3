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
 * @version $Id: TagBasedViewHelper.php 2121 2009-04-02 14:29:21Z sebastian $
 */

/**
 * Tag based view helper.
 * Sould be used as the base class Tx_Fluid_Core_for all view helpers which output simple tags, as it provides some
 * convenience methods to register default attributes, ...
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: TagBasedViewHelper.php 2121 2009-04-02 14:29:21Z sebastian $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
abstract class Tx_Fluid_Core_TagBasedViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Names of all registered tag attributes
	 * @var array
	 */
	protected $tagAttributes = array();

	/**
	 * Constructor
	 *
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct() {
		$this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes. They will be added directly to the resulting HTML tag.', FALSE);
	}

	/**
	 * Register a new tag attribute. Tag attributes are all arguments which will be directly appended to a tag if you call $this->renderTagAttributes()
	 *
	 * The tag attributes registered here are rendered with the $this->renderTagAttributes() method.
	 *
	 * @param string $name Name of tag attribute
	 * @param strgin $type Type of the tag attribute
	 * @param string $description Description of tag attribute
	 * @param boolean $required set to TRUE if tag attribute is required. Defaults to FALSE.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function registerTagAttribute($name, $type, $description, $required = FALSE) {
		$this->registerArgument($name, $type, $description, $required, '');
		$this->tagAttributes[] = $name;
	}

	/**
	 * Registers all standard HTML universal attributes.
	 * Should be used inside registerArguments();
	 *
	 * The following attributes are registered:
	 * - class (CSS Class)
	 * - dir (Text direction)
	 * - id (Universal identifier)
	 * - lang (Language)
	 * - style (per-element style)
	 * - title (tooltip text)
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	protected function registerUniversalTagAttributes() {
		$this->registerTagAttribute('class', 'string', 'CSS class(es) for this element');
		$this->registerTagAttribute('dir', 'string', 'Text direction for this HTML element. Allowed strings: "ltr" (left to right), "rtl" (right to left)');
		$this->registerTagAttribute('id', 'string', 'Unique (in this file) identifier for this HTML element.');
		$this->registerTagAttribute('lang', 'string', 'Language for this element. Use short names specified in RFC 1766');
		$this->registerTagAttribute('style', 'string', 'Individual CSS styles for this element');
		$this->registerTagAttribute('title', 'string', 'Tooltip text of element');
		$this->registerTagAttribute('accesskey', 'string', 'Keyboard shortcut to access this element');
		$this->registerTagAttribute('tabindex', 'integer', 'Specifies the tab order of this element');
	}

	/**
	 * Render all tag attributes which were registered in $this->tagAttributes.
	 * Additionally, renders all attributes specified in additionalArguments.
	 *
	 * You should call this method in your render() method if you output some tag.
	 *
	 * @return string Concatenated list of attributes
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @todo htmlspecialchar() output
	 */
	protected function renderTagAttributes() {
		$attributes = array();
		if (isset($this->arguments['additionalAttributes']) && is_array($this->arguments['additionalAttributes'])) {
			foreach ($this->arguments['additionalAttributes'] as $key => $value) {
				$attributes[] = $key . '="' . $value . '"';
			}
		}
		foreach ($this->tagAttributes as $attributeName) {
			if ($this->arguments[$attributeName]) {
				$attributes[] = $attributeName . '="' . $this->arguments[$attributeName] . '"';
			}
		}
		return implode(' ', $attributes);
	}
}
?>