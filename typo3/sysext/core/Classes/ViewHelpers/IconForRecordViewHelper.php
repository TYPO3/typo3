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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Displays icon for record.
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <core:iconForRecord table="tt_content" row="{record}" />
 *
 * Output::
 *
 *     <span class="t3js-icon icon icon-size-small icon-state-default icon-mimetypes-x-content-text" data-identifier="mimetypes-x-content-text" aria-hidden="true">
 *         <span class="icon-markup">
 *             <img src="/typo3/sysext/core/Resources/Public/Icons/T3Icons/mimetypes/mimetypes-x-content-text.svg" width="16" height="16">
 *         </span>
 *     </span>
 */
final class IconForRecordViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * ViewHelper returns HTML, thus we need to disable output escaping
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('table', 'string', 'the table for the record icon', true);
        $this->registerArgument('row', 'array', 'the record row', true);
        $this->registerArgument('size', 'string', 'the icon size', false, Icon::SIZE_SMALL);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'alternative markup identifier', false);
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $table = $arguments['table'];
        $size = $arguments['size'];
        $row = $arguments['row'];
        $alternativeMarkupIdentifier = $arguments['alternativeMarkupIdentifier'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIconForRecord($table, $row, $size)->render($alternativeMarkupIdentifier);
    }
}
