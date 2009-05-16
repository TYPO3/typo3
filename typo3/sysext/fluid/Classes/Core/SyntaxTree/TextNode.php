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
 * @version $Id: TextNode.php 2213 2009-05-15 11:19:13Z bwaidelich $
 */

/**
 * Text Syntax Tree Node - is a container for strings.
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: TextNode.php 2213 2009-05-15 11:19:13Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 */
class Tx_Fluid_Core_SyntaxTree_TextNode extends Tx_Fluid_Core_SyntaxTree_AbstractNode {

	/**
	 * Contents of the text node
	 * @var string
	 */
	protected $text;

	/**
	 * Constructor.
	 *
	 * @param string $text text to store in this textNode
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($text) {
		if (!is_string($text)) {
			throw new Tx_Fluid_Core_ParsingException('Text node requires an argument of type string, "' . gettype($text) . '" given.');
		}
		$this->text = $text;
	}

	/**
	 * Return the text associated to the syntax tree.
	 *
	 * @return string the text stored in this node.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function evaluate() {
		return $this->text;
	}
}


?>