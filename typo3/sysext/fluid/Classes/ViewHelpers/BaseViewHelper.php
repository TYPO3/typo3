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
 * View helper which creates a <base href="..."></base> tag. The Base URI
 * is taken from the current request.
 * In TYPO3 Flow, you should always include this ViewHelper to make the links work.
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:base />
 * </code>
 * <output>
 * <base href="http://yourdomain.tld/" />
 * (depending on your domain)
 * </output>
 *
 * @api
 */
class BaseViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Render the "Base" tag by outputting $request->getBaseUri()
     *
     * Note: renders as <base></base>, because IE6 will else refuse to display
     * the page...
     *
     * @return string "base"-Tag.
     * @api
     */
    public function render()
    {
        return static::renderStatic(
            [],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Render the "Base" tag by outputting $request->getBaseUri()
     *
     * Note: renders as <base></base>, because IE6 will else refuse to display
     * the page...
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string "base"-Tag.
     * @api
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $controllerContext = $renderingContext->getControllerContext();
        return '<base href="' . htmlspecialchars($controllerContext->getRequest()->getBaseUri()) . '" />';
    }
}
