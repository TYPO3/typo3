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
 * A node which handles object access. This means it handles structures like {object.accessor.bla}
 *
 * @version $Id: ObjectAccessorNode.php 1734 2009-11-25 21:53:57Z stucki $
 * @package Fluid
 * @subpackage Core\Parser\SyntaxTree
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_Core_Parser_SyntaxTree_ObjectAccessorNode extends Tx_Fluid_Core_Parser_SyntaxTree_AbstractNode {

	/**
	 * Object path which will be called. Is a list like "post.name.email"
	 * @var string
	 */
	protected $objectPath;

	/**
	 * Constructor. Takes an object path as input.
	 *
	 * The first part of the object path has to be a variable in the TemplateVariableContainer.
	 * For the further parts, it is checked if the object has a getObjectname method. If yes, this is called.
	 * If no, it is checked if a property "objectname" exists.
	 * If no, an error is thrown.
	 *
	 * @param string $objectPath An Object Path, like object1.object2.object3
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function __construct($objectPath) {
		$this->objectPath = $objectPath;
	}

	/**
	 * Evaluate this node and return the correct object.
	 *
	 * Handles each part (denoted by .) in $this->objectPath in the following order:
	 * - call appropriate getter
	 * - call public property, if exists
	 * - fail
	 *
	 * @return object The evaluated object, can be any object type.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @todo Depending on the context, either fail or not!!!
	 */
	public function evaluate() {
		$objectPathParts = explode('.', $this->objectPath);
		$variableName = array_shift($objectPathParts);
		if (!$this->renderingContext->getTemplateVariableContainer()->exists($variableName)) {
			return NULL;
		}
		$currentObject = $this->renderingContext->getTemplateVariableContainer()->get($variableName);
		if (count($objectPathParts) > 0) {
			$output = Tx_Extbase_Reflection_ObjectAccess::getPropertyPath($currentObject, implode('.', $objectPathParts));
		} else {
			$output = $currentObject;
		}

		$postProcessor = $this->renderingContext->getRenderingConfiguration()->getObjectAccessorPostProcessor();
		if ($postProcessor !== NULL) {
			$output = $postProcessor->process($output, $this->renderingContext->isObjectAccessorPostProcessorEnabled());
		}

		return $output;
	}
}
?>