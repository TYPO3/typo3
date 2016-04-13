<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * View helper which creates a <base href="..."></base> tag. The Base URI is taken from the
 * current request.
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
class BaseViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

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
            array(),
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
