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

namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A ViewHelper for creating URIs to resources.
 *
 * Examples
 * ========
 *
 * Best practice with EXT: syntax
 * ------------------------------
 *
 * ::
 *
 *    <link href="{f:uri.resource(path:'EXT:indexed_search/Resources/Public/Css/Stylesheet.css')}" rel="stylesheet" />
 *
 * Output::
 *
 *    <link href="typo3/sysext/indexed_search/Resources/Public/Css/Stylesheet.css" rel="stylesheet" />
 *
 * Preferred syntax that works in both extbase and non-extbase context.
 *
 * Defaults
 * --------
 *
 * ::
 *
 *    <link href="{f:uri.resource(path:'Css/Stylesheet.css')}" rel="stylesheet" />
 *
 * Output::
 *
 *    <link href="typo3conf/ext/example_extension/Resources/Public/Css/Stylesheet.css" rel="stylesheet" />
 *
 * Works only in extbase context since it uses the extbase request to find current extension, magically adds 'Resources/Public' to path.
 *
 * With extension name
 * -------------------
 *
 * ::
 *
 *    <link href="{f:uri.resource(path:'Css/Stylesheet.css', extensionName: 'AnotherExtension')}" rel="stylesheet" />
 *
 * Output::
 *
 *    <link href="typo3conf/ext/another_extension/Resources/Public/Css/Stylesheet.css" rel="stylesheet" />
 *
 * Magically adds 'Resources/Public' to path.
 */
class ResourceViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments()
    {
        $this->registerArgument('path', 'string', 'The path and filename of the resource (relative to Public resource directory of the extension).', true);
        $this->registerArgument('extensionName', 'string', 'Target extension name. If not set, the current extension name will be used');
        $this->registerArgument('absolute', 'bool', 'If set, an absolute URI is rendered', false, false);
    }

    /**
     * Render the URI to the resource. The filename is used from child content.
     *
     * @return string The URI to the resource
     * @throws InvalidFileException
     * @throws \RuntimeException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $uri = PathUtility::getPublicResourceWebPath(self::resolveExtensionPath($arguments, $renderingContext));
        if ($arguments['absolute']) {
            $uri = GeneralUtility::locationHeaderUrl($uri);
        }

        return $uri;
    }

    /**
     * Resolves the extension path, either directly when possible, or from extension name and request
     *
     * @param array $arguments
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    private static function resolveExtensionPath(array $arguments, RenderingContextInterface $renderingContext): string
    {
        $path = $arguments['path'];
        if (PathUtility::isExtensionPath($path)) {
            return $path;
        }

        return sprintf(
            'EXT:%s/Resources/Public/%s',
            self::resolveExtensionKey($arguments, $renderingContext),
            ltrim($path, '/')
        );
    }

    /**
     * Resolves extension key either from given extension name argument or from request
     *
     * @param array $arguments
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    private static function resolveExtensionKey(array $arguments, RenderingContextInterface $renderingContext): string
    {
        $extensionName = $arguments['extensionName'];
        if ($extensionName === null) {
            return self::resolveValidatedRequest($arguments, $renderingContext)->getControllerExtensionKey();
        }

        return GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);
    }

    /**
     * Resolves and validates the request from rendering context
     *
     * @param array $arguments
     * @param RenderingContextInterface $renderingContext
     * @return RequestInterface
     */
    private static function resolveValidatedRequest(array $arguments, RenderingContextInterface $renderingContext): RequestInterface
    {
        if (!$renderingContext instanceof RenderingContext) {
            throw new \RuntimeException(
                sprintf(
                    'RenderingContext must be instance of "%s", but is instance of "%s"',
                    RenderingContext::class,
                    get_class($renderingContext)
                ),
                1640095993
            );
        }
        $request = $renderingContext->getRequest();
        if (!$request instanceof RequestInterface) {
            throw new \RuntimeException(
                sprintf(
                    'ViewHelper f:uri.resource needs an Extbase Request object to resolve extension name for given path "%s".'
                    . ' If not in Extbase context, either set argument "extensionName",'
                    . ' or (better) use the standard EXT: syntax for path attribute like \'path="EXT:indexed_search/Resources/Public/Icons/Extension.svg"\'.',
                    $arguments['path']
                ),
                1639672666
            );
        }
        if ($request->getControllerExtensionKey() === '') {
            throw new \RuntimeException(
                sprintf(
                    'Can not resolve extension key for given path "%s".'
                    . ' If not in Extbase context, either set argument "extensionName",'
                    . ' or (better) use the standard EXT: syntax for path attribute like \'path="EXT:indexed_search/Resources/Public/Icons/Extension.svg"\'.',
                    $arguments['path']
                ),
                1640097205
            );
        }

        return $request;
    }
}
