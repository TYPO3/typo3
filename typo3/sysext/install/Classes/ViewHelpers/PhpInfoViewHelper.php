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

namespace TYPO3\CMS\Install\ViewHelpers;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Utility ViewHelper for phpinfo()
 *
 * @internal
 */
final class PhpInfoViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        return self::removeAllHtmlOutsideBody(self::changeHtmlToHtml5(self::getPhpInfo()));
    }

    /**
     * Get information about PHP's configuration as HTML string
     */
    protected static function getPhpInfo(): string
    {
        ob_start();
        phpinfo();
        return (string)ob_get_clean();
    }

    /**
     * Remove all HTML outside the body tag from HTML string.
     */
    protected static function removeAllHtmlOutsideBody(string $html): string
    {
        // Delete anything outside the body tag and the body tag itself
        $html = (string)preg_replace('/^.*?<body.*?>/is', '', $html);
        return (string)preg_replace('/<\/body>.*?$/is', '', $html);
    }

    /**
     * Change HTML markup to HTML5.
     *
     * @param string $html HTML markup to be cleaned
     */
    protected static function changeHtmlToHtml5(string $html): string
    {
        // Delete obsolete attributes
        $html = (string)preg_replace('#\s(cellpadding|border|width)="[^"]+"#', '', $html);
        // Replace font tag with span
        return str_replace(['<font', '</font>'], ['<span', '</span>'], $html);
    }
}
