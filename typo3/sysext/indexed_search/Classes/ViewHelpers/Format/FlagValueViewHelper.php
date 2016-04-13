<?php
namespace TYPO3\CMS\IndexedSearch\ViewHelpers\Format;

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
 * FlagValue viewhelper
 */
class FlagValueViewHelper extends AbstractViewHelper
{
    /**
     * Render additional flag information
     *
     * @param int $flags
     * @return string
     */
    public function render($flags)
    {
        return static::renderStatic(
            array(
                'flags' => $flags,
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
        $flags = (int)$arguments['flags'];

        if ($flags > 0) {
            $content = ($flags & 128 ? '<title>' : '')
                . ($flags & 64 ? '<meta/keywords>' : '')
                . ($flags & 32 ? '<meta/description>' : '');
            return $content;
        }
        return '';
    }
}
