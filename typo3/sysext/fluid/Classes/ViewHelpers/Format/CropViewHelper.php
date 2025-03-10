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

namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

use TYPO3\CMS\Core\Html\HtmlCropper;
use TYPO3\CMS\Core\Text\TextCropper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper which can crop (shorten) a text.
 * Whitespace within the `<f:format.crop>` element will be counted as characters.
 *
 * ```
 *   <f:format.crop maxCharacters="10" append="&hellip;[more]">
 *     This is some very long text
 *   </f:format.crop>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-format-crop
 */
final class CropViewHelper extends AbstractViewHelper
{
    /**
     * The output may contain HTML and can not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('maxCharacters', 'int', 'Place where to truncate the string', true);
        $this->registerArgument('append', 'string', 'What to append, if truncation happened', false, '&hellip;');
        $this->registerArgument('respectWordBoundaries', 'bool', 'If TRUE and division is in the middle of a word, the remains of that word is removed.', false, true);
        $this->registerArgument('respectHtml', 'bool', 'If TRUE the cropped string will respect HTML tags and entities. Technically that means, that cropHTML() is called rather than crop()', false, true);
    }

    public function render(): string
    {
        $maxCharacters = (int)$this->arguments['maxCharacters'];
        $append = (string)$this->arguments['append'];
        $respectWordBoundaries = (bool)($this->arguments['respectWordBoundaries']);
        $respectHtml = (bool)$this->arguments['respectHtml'];
        $stringToTruncate = (string)$this->renderChildren();
        $cropperClass = $respectHtml
            ? HtmlCropper::class
            : TextCropper::class;
        return GeneralUtility::makeInstance($cropperClass)->crop(
            content: $stringToTruncate,
            numberOfChars: $maxCharacters,
            replacementForEllipsis: $append,
            cropToSpace: $respectWordBoundaries
        );
    }
}
