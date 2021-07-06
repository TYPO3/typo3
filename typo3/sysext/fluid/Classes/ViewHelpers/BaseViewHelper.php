<?php

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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Http\NormalizedParams;
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
 *
 * @deprecated since v11, will be removed in v12.
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
     * Render the "Base" tag by outputting site URL
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
        trigger_error(__CLASS__ . ' will be removed in TYPO3 v12.', E_USER_DEPRECATED);
        $request = $renderingContext->getRequest();
        /** @var NormalizedParams $normalizedParams */
        $normalizedParams = $request->getAttribute('normalizedParams');
        $baseUri = $normalizedParams->getSiteUrl();
        if (ApplicationType::fromRequest($request)->isBackend()) {
            $baseUri .= TYPO3_mainDir;
        }
        return '<base href="' . htmlspecialchars($baseUri) . '" />';
    }
}
