<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\ViewHelpers\Be;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Used by the form editor.
 * Render a content element preview like the page module
 *
 * Scope: backend
 * @internal
 */
class RenderContentElementPreviewViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments.
     *
     * @internal
     */
    public function initializeArguments()
    {
        $this->registerArgument('contentElementUid', 'int', 'The uid of a content element');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @internal
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $content = '';
        $contentElementUid = $arguments['contentElementUid'];
        $contentRecord = BackendUtility::getRecord('tt_content', $contentElementUid);
        if (!empty($contentRecord)) {
            $pageLayoutView = GeneralUtility::makeInstance(PageLayoutView::class);
            $content = $pageLayoutView->tt_content_drawItem($contentRecord);
        }
        return $content;
    }
}
