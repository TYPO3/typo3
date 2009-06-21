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
 * @package Fluid
 * @subpackage Core
 * @version $Id: ObjectAccessorNode.php 2588 2009-06-09 19:21:45Z sebastian $
 */

/**
 * A node which handles object access. This means it handles structures like {object.accessor.bla}
 *
 * @package Fluid
 * @subpackage Core
 * @version $Id: ObjectAccessorNode.php 2588 2009-06-09 19:21:45Z sebastian $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 * @internal
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
	 * @internal
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
	 * @todo make use of FLOW3 reflection
	 * @internal
	 */
	public function evaluate() {
		try {
			$objectPathParts = explode('.', $this->objectPath);
			$variableName = array_shift($objectPathParts);
			$currentObject = $this->renderingContext->getTemplateVariableContainer()->get($variableName);

			if (count($objectPathParts) > 0) {
				foreach ($objectPathParts as $currentObjectPath) {
					if (is_object($currentObject)) {
						$getterMethodName = 'get' . ucfirst($currentObjectPath);
						if (method_exists($currentObject, $getterMethodName)) {
							$currentObject = call_user_func(array($currentObject, $getterMethodName));
							continue;
						}

						try {
							$reflectionProperty = new ReflectionProperty($currentObject, $currentObjectPath);
						} catch(ReflectionException $e) {
							throw new Tx_Fluid_Core_RuntimeException($e->getMessage(), 1224611407);
						}
						if ($reflectionProperty->isPublic()) {
							$currentObject = $reflectionProperty->getValue($currentObject);
							continue;
						} else {
							throw new Tx_Fluid_Core_RuntimeException('Trying to resolve ' . $this->objectPath . ', but did not find public getters or variables.', 1224609559);
						}
					} elseif (is_array($currentObject)) {
						if (key_exists($currentObjectPath, $currentObject)) {
							$currentObject = $currentObject[$currentObjectPath];
						} else {
							throw new Tx_Fluid_Core_RuntimeException('Tried to read key "' . $currentObjectPath . '" from associative array, but did not find it.', 1225393852);
						}
					}
				}
			}
			$postProcessor = $this->renderingContext->getRenderingConfiguration()->getObjectAccessorPostProcessor();
			if ($postProcessor !== NULL) {
				$currentObject = $postProcessor->process($currentObject, $this->renderingContext->isObjectAccessorPostProcessorEnabled());
			}
			return $currentObject;
		} catch(Tx_Fluid_Core_RuntimeException $e) {
			// DEPENDING ON THE CONTEXT / CONFIG, either fail silently or not. Currently we always fail silently.
		}
		return '';
	}
}
?>