<?php
namespace TYPO3\CMS\Belog\ViewHelpers\Be;

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
 * Get page path string from page id
 * @internal
 */
class PagePathViewHelper extends AbstractBackendViewHelper implements CompilableInterface
{
    /**
     * Resolve page id to page path string (with automatic cropping to maximum given length).
     *
     * @param int $pid Pid of the page
     * @param int $titleLimit Limit of the page title
     * @return string Page path string
     */
    public function render($pid, $titleLimit = 20)
    {
        return static::renderStatic(
            [
                'pid' => $pid,
                'titleLimit' => $titleLimit
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
        return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($arguments['pid'], '', $arguments['titleLimit']);
    }
}
