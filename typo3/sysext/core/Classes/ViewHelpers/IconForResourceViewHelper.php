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

namespace TYPO3\CMS\Core\ViewHelpers;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Displays icon for a FAL resource (file or folder means a :php:`TYPO3\CMS\Core\Resource\ResourceInterface`).
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <core:iconForResource resource="{file.resource}" />
 *
 * Output::
 *
 *     <span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-text-html" data-identifier="mimetypes-text-html">
 *         <span class="icon-markup">
 *             <img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-text-html.svg" width="16" height="16">
 *         </span>
 *     </span>
 */
final class IconForResourceViewHelper extends AbstractViewHelper
{
    /**
     * ViewHelper returns HTML, thus we need to disable output escaping
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('resource', ResourceInterface::class, 'Resource', true);
        $this->registerArgument('size', 'string', 'The icon size', false, IconSize::SMALL);
        $this->registerArgument('overlay', 'string', 'Overlay identifier', false, null);
        $this->registerArgument('options', 'array', 'An associative array with additional options', false, []);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'Alternative markup identifier');
    }

    public function render(): string
    {
        $resource = $this->arguments['resource'];
        if (!($resource instanceof ResourceInterface)) {
            return '';
        }
        $size = $this->arguments['size'];
        $overlay = $this->arguments['overlay'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIconForResource($resource, $size, $overlay, $this->arguments['options'])->render($this->arguments['alternativeMarkupIdentifier']);
    }
}
