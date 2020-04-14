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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * This is the base class for ViewHelpers that work with encodings.
 * Currently that are format.htmlentities, format.htmlentitiesDecode and format.htmlspecialchars
 */
abstract class AbstractEncodingViewHelper extends AbstractViewHelper
{
    /**
     * @var string
     */
    protected static $defaultEncoding;

    /**
     * Resolve the default encoding. If none is set in Frontend or Backend, uses UTF-8.
     *
     * @return string the encoding
     */
    protected static function resolveDefaultEncoding()
    {
        if (self::$defaultEncoding === null) {
            self::$defaultEncoding = 'UTF-8';
        }
        return self::$defaultEncoding;
    }
}
