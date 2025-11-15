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

namespace TYPO3\CMS\Fluid\ViewHelpers\Page;

use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to set meta tags from Fluid templates.
 *
 * ```
 *    <f:page.meta property="description">My page description</f:page.meta>
 *    <f:page.meta property="og:title">My article title</f:page.meta>
 *    <f:page.meta property="og:image" subProperties="{width: 1200, height: 630}">/path/to/image.jpg</f:page.meta>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-page-meta
 */
final class MetaViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly MetaTagManagerRegistry $metaTagManagerRegistry) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('property', 'string', 'The meta property name (e.g. "description", "og:title")', true);
        $this->registerArgument('type', 'string', 'The meta type attribute (name, property, http-equiv). If not set, the appropriate manager will determine the type.');
        $this->registerArgument('subProperties', 'array', 'Array of sub-properties for complex meta tags (e.g. og:image width/height)', false, []);
        $this->registerArgument('replace', 'bool', 'Replace existing meta tags with the same property', false, false);
    }

    public function render(): string
    {
        $property = $this->arguments['property'];
        $content = $this->renderChildren();

        if ($content === null || $content === '') {
            return '';
        }

        $metaTagManager = $this->metaTagManagerRegistry->getManagerForProperty($property);

        $metaTagManager->addProperty(
            $property,
            (string)$content,
            $this->arguments['subProperties'],
            $this->arguments['replace'],
            $this->arguments['type'] ?? ''
        );
        return '';
    }
}
