<?php

declare(strict_types=1);

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for creating URIs to resources (assets).
 *
 * This ViewHelper should be used to return public locations to extension resource files
 * for use in the frontend output.
 *
 * For images within FAL storages, or where graphical operations are
 * performed, use `<f:uri.image>` instead.
 *
 * ```
 *   <link href="{f:uri.resource(path:'EXT:indexed_search/Resources/Public/Css/Stylesheet.css')}" rel="stylesheet" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-resource
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-image
 */
final class ResourceViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('path', 'string', 'The path and filename of the resource (relative to Public resource directory of the extension).', true);
        $this->registerArgument('extensionName', 'string', 'Target extension name. If not set, the current extension name will be used');
        $this->registerArgument('absolute', 'bool', 'If set, an absolute URI is rendered', false, false);
        $this->registerArgument('useCacheBusting', 'bool', 'If set, the URI is rendered with a cache buster', false, true);
    }

    /**
     * Render the URI to the resource. The filename is used from child content.
     *
     * @return string The URI to the resource
     * @throws \RuntimeException
     */
    public function render(): string
    {
        $uri = self::resolveExtensionPath($this->arguments, $this->renderingContext);
        $uri = GeneralUtility::getFileAbsFileName($uri);
        if ($this->arguments['useCacheBusting']) {
            $uri = GeneralUtility::createVersionNumberedFilename($uri);
        }
        $uri = PathUtility::getAbsoluteWebPath($uri);
        if ($this->arguments['absolute']) {
            $uri = GeneralUtility::locationHeaderUrl($uri);
        }
        return $uri;
    }

    /**
     * Resolves the extension path, either directly when possible, or from extension name and request
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
        $request = null;
        if ($renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $renderingContext->getAttribute(ServerRequestInterface::class);
        }
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
