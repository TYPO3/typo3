<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */
/**
 * This is the base class for ViewHelpers that work with encodings.
 * Currently that are format.htmlentities, format.htmlentitiesDecode and format.htmlspecialchars
 */
abstract class AbstractEncodingViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    /**
     * @var string
     */
    protected static $defaultEncoding = null;

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
