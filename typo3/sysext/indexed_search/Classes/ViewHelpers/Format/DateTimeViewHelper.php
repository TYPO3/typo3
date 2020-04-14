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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * DateTime viewhelper
 * @internal
 */
class DateTimeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * The rendered children are fed into data() function, which expects an integer.
     * It reduces overhead and is safe to disable children escaping here.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Render the given timestamp as date & time
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return BackendUtility::datetime($renderChildrenClosure());
    }
}
