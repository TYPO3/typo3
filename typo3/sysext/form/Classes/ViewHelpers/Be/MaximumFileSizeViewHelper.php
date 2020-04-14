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

namespace TYPO3\CMS\Form\ViewHelpers\Be;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Return the max file size for use in the form editor
 *
 * Scope: backend
 * @internal
 */
class MaximumFileSizeViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @internal
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $maxUploadFileSize = GeneralUtility::getMaxUploadFileSize();
        // format according to PHP formatting rules (K = kilobytes instead of kibibytes)
        $formattedSize = GeneralUtility::formatSize($maxUploadFileSize * 1024, '|k|M|G|T|P|E|Z|Y');
        // remove decimals from result to match EXT:form validator integer format
        return preg_replace('/(\d+)(.+\d{2})?([kMGTPEZY]{0,1})/', '$1$3', $formattedSize);
    }
}
