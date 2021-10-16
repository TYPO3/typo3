<?php

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

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Formats a string using PHPs :php:`str_pad` function.
 * See https://www.php.net/manual/function.str_pad.php.
 *
 * Examples
 * ========
 *
 * Defaults
 * --------
 *
 * ::
 *
 *    <f:format.padding padLength="10">TYPO3</f:format.padding>
 *
 * Output::
 *
 *     TYPO3␠␠␠␠␠
 *
 * ``TYPO3␠␠␠␠␠``
 *
 * Specify padding string
 * ----------------------
 *
 * ::
 *
 *    <f:format.padding padLength="10" padString="-=">TYPO3</f:format.padding>
 *
 * ``TYPO3-=-=-``
 *
 * Specify padding type
 * --------------------
 *
 * ::
 *
 *    <f:format.padding padLength="10" padString="-" padType="both">TYPO3</f:format.padding>
 *
 * ``--TYPO3---``
 */
class PaddingViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'string', 'string to format');
        $this->registerArgument('padLength', 'int', 'Length of the resulting string. If the value of pad_length is negative or less than the length of the input string, no padding takes place.', true);
        $this->registerArgument('padString', 'string', 'The padding string', false, ' ');
        $this->registerArgument('padType', 'string', 'Append the padding at this site (Possible values: right,left,both. Default: right)', false, 'right');
    }

    /**
     * Pad a string to a certain length with another string
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     * @return string The formatted value
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $renderChildrenClosure();
        $padTypes = [
            'left' => STR_PAD_LEFT,
            'right' => STR_PAD_RIGHT,
            'both' => STR_PAD_BOTH,
        ];
        $padType = $arguments['padType'];
        if (!isset($padTypes[$padType])) {
            $padType = 'right';
        }

        return StringUtility::multibyteStringPad((string)$value, $arguments['padLength'], $arguments['padString'], $padTypes[$padType]);
    }
}
