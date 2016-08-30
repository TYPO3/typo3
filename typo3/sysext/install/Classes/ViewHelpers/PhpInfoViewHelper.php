<?php
namespace TYPO3\CMS\Install\ViewHelpers;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Utility class for phpinfo()
 * @internal
 */
class PhpInfoViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Render PHP info
     *
     * @return string
     */
    public function render()
    {
        return static::renderStatic(
            [],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return self::removeAllHtmlOutsideBody(
            self::changeHtmlToHtml5(
                self::getPhpInfo()
            )
        );
    }

    /**
     * Get information about PHP's configuration
     *
     * @return string HTML page with the configuration options
     */
    protected static function getPhpInfo()
    {
        ob_start();
        phpinfo();

        return ob_get_clean();
    }

    /**
     * Remove all HTML outside the body tag from HTML string
     *
     * @param string $html Complete HTML markup for page
     * @return string Content of the body tag
     */
    protected static function removeAllHtmlOutsideBody($html)
    {
        // Delete anything outside of the body tag and the body tag itself
        $html = preg_replace('/^.*?<body.*?>/is', '', $html);
        $html = preg_replace('/<\/body>.*?$/is', '', $html);

        return $html;
    }

    /**
     * Change HTML markup to HTML5
     *
     * @param string $html HTML markup to be cleaned
     * @return string
     */
    protected static function changeHtmlToHtml5($html)
    {
        // Delete obsolete attributes
        $html = preg_replace('#\s(cellpadding|border|width)="[^"]+"#', '', $html);

        // Replace font tag with span
        return str_replace(['<font', '</font>'], ['<span', '</span>'], $html);
    }
}
