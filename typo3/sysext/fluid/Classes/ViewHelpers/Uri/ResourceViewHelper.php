<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A view helper for creating URIs to resources.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <link href="{f:uri.resource(path:'css/stylesheet.css')}" rel="stylesheet" />
 * </code>
 * <output>
 * <link href="Resources/Packages/MyPackage/stylesheet.css" rel="stylesheet" />
 * (depending on current package)
 * </output>
 */
class ResourceViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('path', 'string', 'The path and filename of the resource (relative to Public resource directory of the extension).', true);
        $this->registerArgument('extensionName', 'string', 'Target extension name. If not set, the current extension name will be used');
        $this->registerArgument('absolute', 'bool', 'If set, an absolute URI is rendered', false, false);
    }

    /**
     * Render the URI to the resource. The filename is used from child content.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string The URI to the resource
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $path = $arguments['path'];
        $extensionName = $arguments['extensionName'];
        $absolute = $arguments['absolute'];

        if ($extensionName === null) {
            $extensionName = $renderingContext->getControllerContext()->getRequest()->getControllerExtensionName();
        }
        $uri = 'EXT:' . GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
        $uri = GeneralUtility::getFileAbsFileName($uri);
        if ($absolute === false && $uri !== false) {
            $uri = PathUtility::getAbsoluteWebPath($uri);
        }
        if ($absolute === true) {
            $uri = PathUtility::stripPathSitePrefix($uri);
            $uri = $renderingContext->getControllerContext()->getRequest()->getBaseUri() . $uri;
        }
        return $uri;
    }
}
