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
 * Declares new variables which are aliases of other variables.
 * Takes a "map"-Parameter which is an associative array which defines the shorthand mapping.
 *
 * The variables are only declared inside the <f:alias>...</f:alias>-tag. After the
 * closing tag, all declared variables are removed again.
 *
 * = Examples =
 *
 * <code title="Single alias">
 * <f:alias map="{x: 'foo'}">{x}</f:alias>
 * </code>
 * <output>
 * foo
 * </output>
 *
 * <code title="Multiple mappings">
 * <f:alias map="{x: foo.bar.baz, y: foo.bar.baz.name}">
 *   {x.name} or {y}
 * </f:alias>
 * </code>
 * <output>
 * [name] or [name]
 * depending on {foo.bar.baz}
 * </output>
 *
 * @api
 */
class AliasViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Declare aliases
     *
     * @param array $map array that specifies which variables should be mapped to which alias
     * @return string Rendered string
     * @api
     */
    public function render(array $map)
    {
        return static::renderStatic(
            ['map' => $map],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Declare aliases
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $map = $arguments['map'];
        $templateVariableContainer = $renderingContext->getTemplateVariableContainer();

        foreach ($map as $aliasName => $value) {
            $templateVariableContainer->add($aliasName, $value);
        }
        $output = $renderChildrenClosure();
        foreach ($map as $aliasName => $value) {
            $templateVariableContainer->remove($aliasName);
        }
        return $output;
    }
}
