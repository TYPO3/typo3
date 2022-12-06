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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * This ViewHelper strips whitespace (or other characters) from the beginning and end of a string.
 *
 * Possible sides are:
 *
 * ``both`` (default)
 *   Strip whitespace (or other characters) from the beginning and end of a string
 *
 * ``left`` or ``start``
 *   Strip whitespace (or other characters) from the beginning of a string
 *
 * ``right`` or ``end``
 *   Strip whitespace (or other characters) from the end of a string
 *
 *
 * Examples
 * ========
 *
 * Defaults
 * --------
 * ::
 *
 *    #<f:format.trim>   String to be trimmed.   </f:format.trim>#
 *
 * .. code-block:: text
 *
 *    #String to be trimmed.#
 *
 *
 * Trim only one side
 * ------------------
 *
 * ::
 *
 *    #<f:format.trim side="right">   String to be trimmed.   </f:format.trim>#
 *
 * .. code-block:: text
 *
 *    #   String to be trimmed.#
 *
 *
 * Trim special characters
 * -----------------------
 *
 * ::
 *
 *    #<f:format.trim characters=" St.">   String to be trimmed.   </f:format.trim>#
 *
 * .. code-block:: text
 *
 *    #ring to be trimmed#
 */
final class TrimViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    private const SIDE_BOTH = 'both';
    private const SIDE_LEFT = 'left';
    private const SIDE_START = 'start';
    private const SIDE_RIGHT = 'right';
    private const SIDE_END = 'end';

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'The string value to be trimmed. If not given, the evaluated child nodes will be used.', false);
        $this->registerArgument('characters', 'string', 'Optionally, the stripped characters can also be specified using the characters parameter. Simply list all characters that you want to be stripped. With .. you can specify a range of characters.', false);
        $this->registerArgument('side', 'string', 'The side to apply, must be one of this\' CASE_* constants. Defaults to both application.', false, self::SIDE_BOTH);
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string the trimmed value
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        $characters = $arguments['characters'];
        $side = $arguments['side'];

        if ($value === null) {
            $value = (string)$renderChildrenClosure();
        } else {
            $value = (string)$value;
        }

        if ($characters === null) {
            $characters = " \t\n\r\0\x0B";
        }

        return match ($side) {
            self::SIDE_BOTH => trim($value, $characters),
            self::SIDE_LEFT, self::SIDE_START => ltrim($value, $characters),
            self::SIDE_RIGHT, self::SIDE_END => rtrim($value, $characters),
            default => throw new Exception(
                'The side "' . $side . '" supplied to Fluid\'s format.trim ViewHelper is not supported.',
                1669191560
            ),
        };
    }
}
