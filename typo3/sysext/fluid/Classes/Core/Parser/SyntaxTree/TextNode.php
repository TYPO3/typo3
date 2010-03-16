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
 * Text Syntax Tree Node - is a container for strings.
 *
 * @version $Id: TextNode.php 2043 2010-03-16 08:49:45Z sebastian $
 * @package Fluid
 * @subpackage Core\Parser\SyntaxTree
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_Core_Parser_SyntaxTree_TextNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

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
			throw new Tx_Fluid_Core_Parser_Exception('Text node requires an argument of type string, "' . gettype($text) . '" given.');
		}
		$this->text = $text;
	}

	/**
	 * Return the text associated to the syntax tree. Text from child nodes is
	 * appended to the text in the node's own text.
	 *
	 * @return string the text stored in this node/subtree.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function evaluate() {
		return $this->text . $this->evaluateChildNodes();
	}
}

?>