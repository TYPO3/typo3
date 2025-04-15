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
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to normalize a path that uses EXT: syntax or an absolute URL to an absolute web path.
 *
 * ```
 *    <core:normalizedUrl pathOrUrl="https://foo.bar/img.jpg" />
 *    <core:normalizedUrl pathOrUrl="EXT:core/Resources/Public/Images/typo3_black.svg" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-core-normalizedurl
 * @internal
 */
final class NormalizedUrlViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        $this->registerArgument('pathOrUrl', 'string', 'Absolute path to file using EXT: syntax or URL.');
    }

    /**
     * Output what is given as URL or extension relative path as absolute URL
     */
    public function render(): string
    {
        $pathOrUrl = $this->renderChildren();
        if (PathUtility::hasProtocolAndScheme($pathOrUrl)) {
            return $pathOrUrl;
        }
        return GeneralUtility::locationHeaderUrl(PathUtility::getPublicResourceWebPath((string)$pathOrUrl));
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'pathOrUrl';
    }
}
