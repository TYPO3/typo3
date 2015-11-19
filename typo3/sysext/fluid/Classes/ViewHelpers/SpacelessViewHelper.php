<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Space Removal ViewHelper
 *
 * Removes redundant spaces between HTML tags while
 * preserving the whitespace that may be inside HTML
 * tags. Trims the final result before output.
 *
 * Heavily inspired by Twig's corresponding node type.
 *
 * <code title="Usage of f:spaceless">
 * <f:spaceless>
 * <div>
 *     <div>
 *         <div>text
 *
 * text</div>
 *     </div>
 * </div>
 * </code>
 * <output>
 * <div><div><div>text
 *
 * text</div></div></div>
 * </output>
 */
class SpacelessViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * @return string
     */
    public function render()
    {
        return static::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * @param array $arguments
     * @param \Closure $childClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $childClosure, RenderingContextInterface $renderingContext)
    {
        return trim(preg_replace('/\\>\\s+\\</', '><', $childClosure()));
    }
}
