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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use TYPO3\CMS\Core\SystemResource\SystemResourceFactory;
use TYPO3\CMS\Core\SystemResource\Type\StaticResourceInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for creating system resource objects.
 *
 * ```
 *   {f:resource(identifier: 'PKG:typo3/cms-indexed-search:Resources/Public/Css/Stylesheet.css') -> f:uri.resource()}
 *   {styleSheet -> f:resource() -> f:uri.resource()}
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-uri-resource
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-resource
 */
final class ResourceViewHelper extends AbstractViewHelper
{
    public function __construct(
        private readonly SystemResourceFactory $systemResourceFactory,
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('identifier', 'string', 'The resource identifier given as argument or child');
    }

    public function render(): StaticResourceInterface
    {
        return $this->systemResourceFactory->createResource($this->renderChildren());
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'identifier';
    }
}
