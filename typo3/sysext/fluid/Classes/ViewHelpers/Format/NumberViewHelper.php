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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

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
class NumberViewHelper extends AbstractViewHelper implements CompilableInterface
{
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
            [
                'decimals' => $decimals,
                'decimalSeparator' => $decimalSeparator,
                'thousandsSeparator' => $thousandsSeparator,
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
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
