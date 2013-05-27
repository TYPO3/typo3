<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\View\Fixtures;

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
 * [Enter description here]
 */
class TransparentSyntaxTreeNode extends \TYPO3\CMS\Fluid\Core\Parser\SyntaxTree\AbstractNode {
	public $variableContainer;

	public function evaluate(\TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface $renderingContext) {
	}
}

?>