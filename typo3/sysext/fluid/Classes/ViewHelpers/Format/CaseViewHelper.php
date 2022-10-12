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
 * Modifies the case of an input string to upper- or lowercase or capitalization.
 * The default transformation will be uppercase as in `mb_convert_case`_.
 *
 * Possible modes are:
 *
 * ``lower``
 *   Transforms the input string to its lowercase representation
 *
 * ``upper``
 *   Transforms the input string to its uppercase representation
 *
 * ``capital``
 *   Transforms the input string to its first letter upper-cased, i.e. capitalization
 *
 * ``uncapital``
 *   Transforms the input string to its first letter lower-cased, i.e. uncapitalization
 *
 * ``capitalWords``
 *   Not supported yet: Transforms the input string to each containing word being capitalized
 *
 * Note that the behavior will be the same as in the appropriate PHP function `mb_convert_case`_;
 * especially regarding locale and multibyte behavior.
 *
 * .. _mb_convert_case: https://www.php.net/manual/function.mb-convert-case.php
 *
 * Examples
 * ========
 *
 * Default
 * -------
 *
 * ::
 *
 *    <f:format.case>Some Text with miXed case</f:format.case>
 *
 * Output::
 *
 *    SOME TEXT WITH MIXED CASE
 *
 * Example with given mode
 * -----------------------
 *
 * ::
 *
 *    <f:format.case mode="capital">someString</f:format.case>
 *
 * Output::
 *
 *    SomeString
 */
final class CaseViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Directs the input string being converted to "lowercase"
     */
    public const CASE_LOWER = 'lower';

    /**
     * Directs the input string being converted to "UPPERCASE"
     */
    public const CASE_UPPER = 'upper';

    /**
     * Directs the input string being converted to "Capital case"
     */
    public const CASE_CAPITAL = 'capital';

    /**
     * Directs the input string being converted to "unCapital case"
     */
    public const CASE_UNCAPITAL = 'uncapital';

    /**
     * Directs the input string being converted to "Capital Case For Each Word"
     */
    public const CASE_CAPITAL_WORDS = 'capitalWords';

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'The input value. If not given, the evaluated child nodes will be used.', false);
        $this->registerArgument('mode', 'string', 'The case to apply, must be one of this\' CASE_* constants. Defaults to uppercase application.', false, self::CASE_UPPER);
    }

    /**
     * Changes the case of the input string
     * @throws Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $value = $arguments['value'];
        $mode = $arguments['mode'];

        if ($value === null) {
            $value = (string)$renderChildrenClosure();
        }

        switch ($mode) {
            case self::CASE_LOWER:
                $output = mb_strtolower($value, 'utf-8');
                break;
            case self::CASE_UPPER:
                $output = mb_strtoupper($value, 'utf-8');
                break;
            case self::CASE_CAPITAL:
                $firstChar = mb_substr($value, 0, 1, 'utf-8');
                $firstChar = mb_strtoupper($firstChar, 'utf-8');
                $remainder = mb_substr($value, 1, null, 'utf-8');
                $output = $firstChar . $remainder;
                break;
            case self::CASE_UNCAPITAL:
                $firstChar = mb_substr($value, 0, 1, 'utf-8');
                $firstChar = mb_strtolower($firstChar, 'utf-8');
                $remainder = mb_substr($value, 1, null, 'utf-8');
                $output = $firstChar . $remainder;
                break;
            case self::CASE_CAPITAL_WORDS:
                $output = mb_convert_case($value, MB_CASE_TITLE, 'utf-8');
                break;
            default:
                throw new Exception('The case mode "' . $mode . '" supplied to Fluid\'s format.case ViewHelper is not supported.', 1358349150);
        }

        return $output;
    }
}
