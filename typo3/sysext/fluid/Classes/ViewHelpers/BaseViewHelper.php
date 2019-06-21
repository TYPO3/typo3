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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * ViewHelper which creates a :html:`<base href="..."></base>` tag.
 *
 * The Base URI is taken from the current request.
 *
 * Examples
 * ========
 *
 * Example::
 *
 *    <f:base />
 *
 * Output::
 *
 *    <base href="http://yourdomain.tld/" />
 *
 * Depending on your domain.
 */
class BaseViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

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
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string "base"-Tag.
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return '<base href="' . htmlspecialchars($renderingContext->getControllerContext()->getRequest()->getBaseUri()) . '" />';
    }
}
