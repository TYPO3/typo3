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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

/**
 * Render the icon of a report
 *
 * @internal
 */
class IconViewHelper extends AbstractBackendViewHelper implements CompilableInterface
{
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
            [
                'icon' => $icon,
                'title' => $title,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $icon = $arguments['icon'];
        $title = $arguments['title'];

        if (!empty($icon)) {
            $absIconPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFilename($icon);
            if (file_exists($absIconPath)) {
                $icon = '../' . str_replace(PATH_site, '', $absIconPath);
            }
        } else {
            $icon = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('reports') . 'ext_icon.png';
        }
        return '<img src="' . htmlspecialchars($icon) . '" width="16" height="16" title="' . htmlspecialchars($title) . '" alt="' . htmlspecialchars($title) . '" />';
    }
}
