<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Modifies the case of an input string to upper- or lowercase or capitalization.
 * The default transformation will be uppercase as in ``mb_convert_case`` [1].
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
 * Note that the behavior will be the same as in the appropriate PHP function ``mb_convert_case`` [1];
 * especially regarding locale and multibyte behavior.
 *
 * @see http://php.net/manual/function.mb-convert-case.php [1]
 *
 * = Examples =
 *
 * <code title="Example">
 * <f:format.case>Some Text with miXed case</f:format.case>
 * </code>
 * <output>
 * SOME TEXT WITH MIXED CASE
 * </output>
 *
 * <code title="Example with given mode">
 * <f:format.case mode="capital">someString</f:format.case>
 * </code>
 * <output>
 * SomeString
 * </output>
 *
 * @api
 */
class CaseViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Directs the input string being converted to "lowercase"
     */
    const CASE_LOWER = 'lower';

    /**
     * Directs the input string being converted to "UPPERCASE"
     */
    const CASE_UPPER = 'upper';

    /**
     * Directs the input string being converted to "Capital case"
     */
    const CASE_CAPITAL = 'capital';

    /**
     * Directs the input string being converted to "unCapital case"
     */
    const CASE_UNCAPITAL = 'uncapital';

    /**
     * Directs the input string being converted to "Capital Case For Each Word"
     */
    const CASE_CAPITAL_WORDS = 'capitalWords';

    /**
     * @var NULL|CharsetConverter
     */
    protected static $charsetConverter = null;

    /**
     * Changes the case of the input string
     *
     * @param string $value The input value. If not given, the evaluated child nodes will be used
     * @param string $mode The case to apply, must be one of this' CASE_* constants. Defaults to uppercase application
     * @return string the altered string.
     * @api
     */
    public function render($value = null, $mode = self::CASE_UPPER)
    {
        return static::renderStatic(
            [
                'value' => $value,
                'mode' => $mode,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Changes the case of the input string
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     * @throws InvalidVariableException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        $mode = $arguments['mode'];

        if ($value === null) {
            $value = $renderChildrenClosure();
        }

        if (is_null(static::$charsetConverter)) {
            static::$charsetConverter = GeneralUtility::makeInstance(CharsetConverter::class);
        }
        $charsetConverter = static::$charsetConverter;

        switch ($mode) {
            case self::CASE_LOWER:
                $output = $charsetConverter->conv_case('utf-8', $value, 'toLower');
                break;
            case self::CASE_UPPER:
                $output = $charsetConverter->conv_case('utf-8', $value, 'toUpper');
                break;
            case self::CASE_CAPITAL:
                $output = $charsetConverter->utf8_substr($charsetConverter->convCaseFirst('utf-8', $value, 'toUpper'), 0, 1) . $charsetConverter->utf8_substr($value, 1);
                break;
            case self::CASE_UNCAPITAL:
                $output = $charsetConverter->utf8_substr($charsetConverter->convCaseFirst('utf-8', $value, 'toLower'), 0, 1) . $charsetConverter->utf8_substr($value, 1);
                break;
            case self::CASE_CAPITAL_WORDS:
                // @todo: Implement method once there is a proper solution with using the CharsetConverter
            default:
                throw new InvalidVariableException('The case mode "' . $mode . '" supplied to Fluid\'s format.case ViewHelper is not supported.', 1358349150);
        }

        return $output;
    }
}
