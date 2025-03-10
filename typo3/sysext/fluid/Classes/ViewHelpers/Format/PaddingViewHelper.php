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

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to format a string to specific lengths, by using PHPs `str_pad` function.
 *
 * ```
 *   <f:format.padding padLength="10" padString="!" padType="right">TYPO3</f:format.padding>
 * ```
 *
 * @see https://www.php.net/manual/en/function.str-pad
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-format-padding
 */
final class PaddingViewHelper extends AbstractViewHelper
{
    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'string to format');
        $this->registerArgument('padLength', 'int', 'Length of the resulting string. If the value of pad_length is negative or less than the length of the input string, no padding takes place.', true);
        $this->registerArgument('padString', 'string', 'The padding string', false, ' ');
        $this->registerArgument('padType', 'string', 'Append the padding at this site (Possible values: right,left,both. Default: right)', false, 'right');
    }

    /**
     * Pad a string to a certain length with another string.
     */
    public function render(): string
    {
        $value = $this->renderChildren();
        $padTypes = [
            'left' => STR_PAD_LEFT,
            'right' => STR_PAD_RIGHT,
            'both' => STR_PAD_BOTH,
        ];
        $padType = $this->arguments['padType'];
        if (!isset($padTypes[$padType])) {
            $padType = 'right';
        }
        return StringUtility::multibyteStringPad((string)$value, (int)$this->arguments['padLength'], (string)$this->arguments['padString'], $padTypes[$padType]);
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'value';
    }
}
