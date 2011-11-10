<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Interface for objects which are aware of Fluid's rendering context. All objects
 * marked with this interface will get the current rendering context injected
 * by the ObjectAccessorNode on trying to evaluate them.
 *
 */
interface Tx_Fluid_Core_Parser_SyntaxTree_RenderingContextAwareInterface {

	/**
	 * Sets the current rendering context
	 *
	 * @param Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext
	 * @return void
	 */
	public function setRenderingContext(Tx_Fluid_Core_Rendering_RenderingContextInterface $renderingContext);

}

?>