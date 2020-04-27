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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Normalizes a path that uses EXT: syntax or an absolute URL to an absolute web path
 *
 * Examples
 * ========
 *
 * Url::
 *
 *    <core:normalizedUrl pathOrUrl="https://foo.bar/img.jpg" />
 *
 * Output::
 *
 *     https://foo.bar/img.jpg
 *
 * Path::
 *
 *    <core:normalizedUrl pathOrUrl="EXT:core/Resources/Public/Images/typo3_black.svg" />
 *
 * Output::
 *
 *     /typo3/sysext/core/Resources/Public/Images/typo3_black.svg
 * @internal
 */
class NormalizedUrlViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('pathOrUrl', 'string', 'Absolute path to file using EXT: syntax or URL.');
    }

    /**
     * Ouputs what is given as URL or extension relative path as absolute URL
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $pathOrUrl = $renderChildrenClosure();
        if (PathUtility::hasProtocolAndScheme($pathOrUrl)) {
            return $pathOrUrl;
        }

        return GeneralUtility::locationHeaderUrl(PathUtility::getPublicResourceWebPath($pathOrUrl));
    }
}
