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

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to display an icon for a record.
 *
 * ```
 *    <core:iconForRecord table="tt_content" row="{record}" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-core-iconforrecord
 */
final class IconForRecordViewHelper extends AbstractViewHelper
{
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
        $this->registerArgument('size', 'string', 'the icon size', false, IconSize::SMALL->value);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'alternative markup identifier');
    }

    public function render(): string
    {
        $table = $this->arguments['table'];
        $size = IconSize::from($this->arguments['size']);
        $row = $this->arguments['row'];
        $alternativeMarkupIdentifier = $this->arguments['alternativeMarkupIdentifier'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        return $iconFactory->getIconForRecord($table, $row, $size)->render($alternativeMarkupIdentifier);
    }
}
