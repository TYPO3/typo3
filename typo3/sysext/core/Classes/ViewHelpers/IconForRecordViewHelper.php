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

use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\InvalidArgumentValueException;

/**
 * ViewHelper to display an icon for a record.
 *
 * The record can be given as a plain database row, which requires the table to be
 * set as well, or as a record object, which already carries its table. The arguments
 * "row" and "record" are interchangeable, exactly one of them must be given.
 *
 * ```
 *    <core:iconForRecord table="tt_content" row="{row}" />
 *    <core:iconForRecord record="{record}" />
 *    <core:iconForRecord row="{record}" />
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

    public function __construct(
        private readonly IconFactory $iconFactory
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('table', 'string', 'the table for the record icon, not evaluated for record objects');
        $this->registerArgument('row', 'array|' . RecordInterface::class, 'the record row or record object');
        $this->registerArgument('record', 'array|' . RecordInterface::class, 'the record object or record row, interchangeable with "row"');
        $this->registerArgument('size', 'string', 'the icon size', false, IconSize::SMALL->value);
        $this->registerArgument('alternativeMarkupIdentifier', 'string', 'alternative markup identifier');
    }

    public function render(): string
    {
        $row = $this->arguments['row'];
        $record = $this->arguments['record'];
        if ($row !== null && $record !== null) {
            throw new InvalidArgumentValueException('The arguments "row" and "record" of <core:iconForRecord> are interchangeable, only one of them can be given.', 1788361133);
        }
        $record ??= $row;
        if ($record === null) {
            throw new InvalidArgumentValueException('<core:iconForRecord> requires either the argument "row" or "record" to be given.', 1788361134);
        }
        $size = IconSize::from($this->arguments['size']);
        $alternativeMarkupIdentifier = $this->arguments['alternativeMarkupIdentifier'];
        if ($record instanceof RecordInterface) {
            return $this->iconFactory->getIconForRecordObject($record, $size)->render($alternativeMarkupIdentifier);
        }
        $table = $this->arguments['table'];
        if (!is_string($table) || $table === '') {
            throw new InvalidArgumentValueException('<core:iconForRecord> requires the argument "table" when a record row array is given.', 1788361135);
        }
        return $this->iconFactory->getIconForRecord($table, $record, $size)->render($alternativeMarkupIdentifier);
    }
}
