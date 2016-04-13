<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Formats a number with custom precision, decimal point and grouped thousands.
 *
 * @see http://www.php.net/manual/en/function.number-format.php
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.number>423423.234</f:format.number>
 * </code>
 * <output>
 * 423,423.20
 * </output>
 *
 * <code title="With all parameters">
 * <f:format.number decimals="1" decimalSeparator="," thousandsSeparator=".">423423.234</f:format.number>
 * </code>
 * <output>
 * 423.423,2
 * </output>
 *
 * @api
 */
class NumberViewHelper extends AbstractViewHelper
{
    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Format the numeric value as a number with grouped thousands, decimal point and
     * precision.
     *
     * @param int $decimals The number of digits after the decimal point
     * @param string $decimalSeparator The decimal point character
     * @param string $thousandsSeparator The character for grouping the thousand digits
     *
     * @return string The formatted number
     * @api
     */
    public function render($decimals = 2, $decimalSeparator = '.', $thousandsSeparator = ',')
    {
        return static::renderStatic(
            array(
                'decimals' => $decimals,
                'decimalSeparator' => $decimalSeparator,
                'thousandsSeparator' => $thousandsSeparator,
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $decimals = $arguments['decimals'];
        $decimalSeparator = $arguments['decimalSeparator'];
        $thousandsSeparator = $arguments['thousandsSeparator'];

        $stringToFormat = $renderChildrenClosure();
        return number_format($stringToFormat, $decimals, $decimalSeparator, $thousandsSeparator);
    }
}
