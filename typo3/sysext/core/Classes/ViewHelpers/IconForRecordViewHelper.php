<?php
namespace TYPO3\CMS\Core\ViewHelpers;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Displays icon for record
 */
class IconForRecordViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * View helper returns HTML, thus we need to disable output escaping
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('table', 'string', 'the table for the record icon', true);
        $this->registerArgument('row', 'array', 'the record row', true);
        $this->registerArgument('size', 'string', 'the icon size', false, Icon::SIZE_SMALL);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'alternative markup identifier', false, null);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $table = $arguments['table'];
        $size = $arguments['size'];
        $row = $arguments['row'];
        $alternativeMarkupIdentifier = $arguments['alternativeMarkupIdentifier'];
        /** @var IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIconForRecord($table, $row, $size)->render($alternativeMarkupIdentifier);
    }
}
