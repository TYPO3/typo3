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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Encodes the given string according to http://www.faqs.org/rfcs/rfc3986.html
 * Applying PHPs :php:`rawurlencode()` function.
 * See https://www.php.net/manual/function.rawurlencode.php.
 *
 * .. note::
 *    The output is not escaped. You may have to ensure proper escaping on your own.
 *
 * Examples
 * ========
 *
 * Default notation
 * ----------------
 *
 * ::
 *
 *    <f:format.rawurlencode>foo @+%/</f:format.rawurlencode>
 *
 * ``foo%20%40%2B%25%2F`` :php:`rawurlencode()` applied.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {text -> f:format.urlencode()}
 *
 * Url encoded text :php:`rawurlencode()` applied.
 */
class UrlencodeViewHelper extends AbstractViewHelper
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
     *
     * @throws Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'string', 'string to format');
    }

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs rawurlencode() function.
     *
     * @see https://www.php.net/manual/function.rawurlencode.php
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $renderChildrenClosure();
        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return $value;
        }
        return rawurlencode((string)$value);
    }
}
