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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Formats an integer with a byte count into human readable form.
 *
 * Examples
 * ========
 *
 * Simple
 * ------
 *
 * ::
 *
 *    {fileSize -> f:format.bytes()}
 *
 * ``123 KB``
 * Depending on the value of ``{fileSize}``.
 *
 * With arguments
 * --------------
 *
 * ::
 *
 *    {fileSize -> f:format.bytes(decimals: 2, decimalSeparator: '.', thousandsSeparator: ',')}
 *
 * ``1,023.00 B``
 * Depending on the value of ``{fileSize}``.
 *
 * You may provide an own set of units, like this: ``B,KB,MB,GB,TB,PB,EB,ZB,YB``.
 *
 * Custom units
 * ------------
 *
 * ::
 *
 *    {fileSize -> f:format.bytes(units: '{f:translate(\'viewhelper.format.bytes.units\', \'fluid\')}'
 *
 * ``123 KB``
 * Depending on the value of ``{fileSize}``.
 */
class BytesViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize ViewHelper arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'int', 'The incoming data to convert, or NULL if VH children should be used');
        $this->registerArgument('decimals', 'int', 'The number of digits after the decimal point', false, 0);
        $this->registerArgument('decimalSeparator', 'string', 'The decimal point character', false, '.');
        $this->registerArgument('thousandsSeparator', 'string', 'The character for grouping the thousand digits', false, ',');
        $this->registerArgument('units', 'string', 'comma separated list of available units, default is LocalizationUtility::translate(\'viewhelper.format.bytes.units\', \'fluid\')');
    }

    /**
     * Render the supplied byte count as a human readable string.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     *
     * @return string Formatted byte count
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if ($arguments['units'] !== null) {
            $units = $arguments['units'];
        } else {
            $units = LocalizationUtility::translate('viewhelper.format.bytes.units', 'fluid');
        }
        $units = GeneralUtility::trimExplode(',', $units, true);

        $value = $renderChildrenClosure();

        if (is_numeric($value)) {
            $value = (float)$value;
        }
        if (!is_int($value) && !is_float($value)) {
            $value = 0;
        }
        $bytes = max($value, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 2 ** (10 * $pow);

        return sprintf(
            '%s %s',
            number_format(
                round($bytes, 4 * $arguments['decimals']),
                $arguments['decimals'],
                $arguments['decimalSeparator'],
                $arguments['thousandsSeparator']
            ),
            $units[$pow]
        );
    }
}
