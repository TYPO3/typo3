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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Formats an integer with a byte count into human-readable form.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * {fileSize -> f:format.bytes()}
 * </code>
 * <output>
 * 123 KB
 * // depending on the value of {fileSize}
 * </output>
 *
 * <code title="Defaults">
 * {fileSize -> f:format.bytes(decimals: 2, decimalSeparator: '.', thousandsSeparator: ',')}
 * </code>
 * <output>
 * 1,023.00 B
 * // depending on the value of {fileSize}
 * </output>
 *
 * @api
 */
class BytesViewHelper extends AbstractViewHelper
{

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize ViewHelper arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'int',
            'The incoming data to convert, or NULL if VH children should be used');
        $this->registerArgument('decimals', 'int', 'The number of digits after the decimal point');
        $this->registerArgument('decimalSeparator', 'string', 'The decimal point character', false,
            '.');
        $this->registerArgument('thousandsSeparator', 'string',
            'The character for grouping the thousand digits', false, ',');
    }

    /**
     * @var array
     */
    protected static $units = array();

    /**
     * Render the supplied byte count as a human readable string.
     *
     * @return string Formatted byte count
     * @api
     */
    public function render()
    {
        return static::renderStatic(
            $this->arguments,
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * set units - temporary method, will vanish with https://review.typo3.org/#/c/49289/
     *
     */
    public static function setUnits($units = [])
    {
        self::$units = $units;
        if (empty($units)) {
            self::$units = GeneralUtility::trimExplode(',',
                LocalizationUtility::translate('viewhelper.format.bytes.units', 'fluid'));
        }
    }

    /**
     * Applies htmlspecialchars() on the specified value.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param \TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        if (empty(self::$units)) {
            self::setUnits();
        }

        $value = $arguments['value'];
        if ($value === null) {
            $value = $renderChildrenClosure();
        }

        if (is_numeric($value)) {
            $value = (float)$value;
        }
        if (!is_int($value) && !is_float($value)) {
            $value = 0;
        }
        $bytes = max($value, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count(self::$units) - 1);
        $bytes /= pow(2, 10 * $pow);

        return sprintf(
            '%s %s',
            number_format(round($bytes, 4 * $arguments['decimals']), $arguments['decimals'],
                $arguments['decimalSeparator'], $arguments['thousandsSeparator']),
            self::$units[$pow]
        );
    }
}
