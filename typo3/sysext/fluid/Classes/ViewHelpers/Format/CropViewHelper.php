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
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Use this ViewHelper to crop the text between its opening and closing tags.
 *
 * Whitespace within the f:format.crop element will be considered as characters.
 *
 * Examples
 * ========
 *
 * Defaults
 * --------
 *
 * ::
 *
 *    <f:format.crop maxCharacters="10">
 *    This is some very long text
 *    </f:format.crop>
 *
 * ``This is...``
 *
 * The third word "some" does not fit in the 10 character limit, because respectWordBoundaries
 * is true by default.
 *
 * Custom suffix
 * -------------
 *
 * ::
 *
 *    <f:format.crop maxCharacters="17" append="&nbsp;[more]">
 *    This is some very long text
 *    </f:format.crop>
 *
 * ``This is some&nbsp;[more]``
 *
 * Don't respect word boundaries
 * -----------------------------
 *
 * ::
 *
 *    <f:format.crop maxCharacters="10" respectWordBoundaries="false">
 *    This is some very long text
 *    </f:format.crop>
 *
 * ``This is s...``
 *
 * Don't respect HTML tags
 * -----------------------
 *
 * ::
 *
 *    <f:format.crop maxCharacters="28" respectWordBoundaries="false" respectHtml="false">
 *    This is some text with <strong>HTML</strong> tags
 *    </f:format.crop>
 *
 * ``This is some text with <stro``
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {someLongText -> f:format.crop(maxCharacters: 10)}
 *
 * ``someLongText cropped after 10 characters…``
 * Depending on the value of ``{someLongText}``.
 */
final class CropViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

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

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $maxCharacters = (int)$arguments['maxCharacters'];
        $append = (string)$arguments['append'];
        $respectWordBoundaries = (bool)($arguments['respectWordBoundaries']);
        $respectHtml = (bool)$arguments['respectHtml'];
        $stringToTruncate = (string)$renderChildrenClosure();
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
