<?php
namespace TYPO3\CMS\Reports\ViewHelpers;

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
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Render the icon of a report
 *
 * @internal
 */
class IconViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Renders the icon
     *
     * @param string $icon Icon to be used
     * @param string $title Optional title
     * @return string Content rendered image
     */
    public function render($icon, $title = '')
    {
        return static::renderStatic(
            array(
                'icon' => $icon,
                'title' => $title,
            ),
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
        $icon = $arguments['icon'];
        $title = $arguments['title'];

        $icon = GeneralUtility::getFileAbsFileName($icon ?: 'EXT:reports/ext_icon.png');
        return '<img src="' . htmlspecialchars(PathUtility::getAbsoluteWebPath($icon)) . '" width="16" height="16" title="' . htmlspecialchars($title) . '" alt="' . htmlspecialchars($title) . '" />';
    }
}
