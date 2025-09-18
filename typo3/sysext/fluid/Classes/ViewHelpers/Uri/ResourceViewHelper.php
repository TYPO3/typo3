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
use TYPO3\CMS\Core\SystemResource\Exception\InvalidSystemResourceIdentifierException;
use TYPO3\CMS\Core\SystemResource\Identifier\SystemResourceIdentifierFactory;
use TYPO3\CMS\Core\SystemResource\Publishing\SystemResourcePublisherInterface;
use TYPO3\CMS\Core\SystemResource\Publishing\UriGenerationOptions;
use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\PublicResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for creating URIs to resources (assets).
 *
 * This ViewHelper should be used to return public url to extension resource files
 * for use in html output.
 *
 * For images within FAL storages, or where graphical operations are
 * performed, use `<f:uri.image>` instead.
 *
 * ```
 *   <link href="{f:resource(identifier: 'EXT:indexed_search/Resources/Public/Css/Stylesheet.css') -> f:uri.resource()}" rel="stylesheet" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-resource
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-image
 */
final class ResourceViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly SystemResourceFactory $systemResourceFactory,
        private readonly SystemResourcePublisherInterface $resourcePublisher,
        private readonly SystemResourceIdentifierFactory $resourceIdentifierFactory,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('resource', 'object', 'The resource object given as argument or child');
        $this->registerArgument('path', 'string', 'The path and filename of the resource (relative to Public resource directory of the extension).');
        $this->registerArgument('extensionName', 'string', 'Target extension name. If not set, the current extension name will be used');
        $this->registerArgument('absolute', 'bool', 'If set, an absolute URI is rendered', false, false);
        $this->registerArgument('useCacheBusting', 'bool', 'Setting this to false has no effect any more', false, true);
    }

    /**
     * Render the URI to the resource. The filename is used from child content.
     *
     * @return string The URI to the resource
     * @throws \RuntimeException
     */
    public function render(): string
    {
        $resource = $this->renderChildren();
        if (!$resource instanceof PublicResourceInterface) {
            $uri = $this->resolveSystemUri();
            $resource = $this->systemResourceFactory->createPublicResource($uri);
        }
        $request = $this->resolveRequest();
        if ($this->arguments['absolute'] && $request === null) {
            throw new \RuntimeException(
                'ViewHelper f:uri.resource needs a Request object to generate absolute URLs,',
                1758574774
            );
        }
        return (string)$this->resourcePublisher->generateUri(
            $resource,
            $request,
            new UriGenerationOptions(absoluteUri: $this->arguments['absolute']),
        );
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'resource';
    }

    /**
     * Resolves the extension path, either directly when possible, or from extension name and request
     */
    private function resolveSystemUri(): string
    {
        if (!isset($this->arguments['path'])) {
            throw new \RuntimeException('ViewHelper f:uri.resource needs either "resource", or "path" argument to be set', 1759231234);
        }
        $path = $this->arguments['path'];
        try {
            return (string)$this->resourceIdentifierFactory->create($path);
        } catch (InvalidSystemResourceIdentifierException) {
            $packageKey = $this->resolveExtensionKey();
            $relativePath = 'Resources/Public/' . ltrim($path, '/');
            return (string)$this->resourceIdentifierFactory->createFromPackagePath(
                $this->resolveExtensionKey(),
                'Resources/Public/' . ltrim($path, '/'),
                sprintf('Uri\ResourceViewHelper, package key: "%s", relative path: "%s', $packageKey, $relativePath)
            );
        }
    }

    /**
     * Resolves extension key either from given extension name argument or from request
     */
    private function resolveExtensionKey(): string
    {
        $extensionName = $this->arguments['extensionName'];
        if ($extensionName === null) {
            return $this->resolveValidatedRequest($this->resolveRequest())->getControllerExtensionKey();
        }
        return GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName);
    }

    /**
     * Resolves the request from rendering context
     */
    private function resolveRequest(): ?ServerRequestInterface
    {
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            return $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        return null;
    }

    /**
     * Resolves and validates the request from rendering context
     */
    private function resolveValidatedRequest(?ServerRequestInterface $request): RequestInterface
    {
        if (!$request instanceof RequestInterface) {
            throw new \RuntimeException(
                sprintf(
                    'ViewHelper f:uri.resource needs an Extbase Request object to resolve extension name for given path "%s".'
                    . ' If not in Extbase context, either set argument "extensionName",'
                    . ' or (better) use the standard EXT: syntax for path attribute like \'path="EXT:indexed_search/Resources/Public/Icons/Extension.svg"\'.',
                    $this->arguments['path']
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
                    $this->arguments['path']
                ),
                1640097205
            );
        }
        return $request;
    }
}
