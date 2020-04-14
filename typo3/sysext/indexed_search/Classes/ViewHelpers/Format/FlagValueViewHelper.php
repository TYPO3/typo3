<?php

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

namespace TYPO3\CMS\IndexedSearch\ViewHelpers\Format;

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * FlagValue viewhelper
 * @internal
 */
class FlagValueViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Sets up the needed arguments for this ViewHelper.
     */
    public function initializeArguments()
    {
        $this->registerArgument('flags', 'int', '', true);
    }

    /**
     * Render additional flag information
     *
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
