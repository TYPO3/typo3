<?php
namespace TYPO3\CMS\Fluid\Core\Parser\SyntaxTree;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
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
 */
interface RenderingContextAwareInterface {

	/**
	 * Sets the current rendering context
	 *
	 * @param \TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
	 * @return void
	 */
	public function setRenderingContext(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext);

}

?>