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

/**
 * ViewHelper to apply `htmlentities()` escaping to a value,
 * transforming all HTML special characters to entity representations
 * (like `"` to `&quot;`).
 *
 * ```
 *   <f:format.htmlentities>{textWithHtml}</f:format.htmlentities>
 * ```
 *
 * @see https://www.php.net/manual/function.htmlentities.php
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-format-htmlentities
 */
final class HtmlentitiesViewHelper extends AbstractEncodingViewHelper
{
    /**
     * Output gets encoded by this viewhelper
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * This prevents double encoding as the whole output gets encoded at the end
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'string to format');
        $this->registerArgument('keepQuotes', 'bool', 'If TRUE, single and double quotes won\'t be replaced (sets ENT_NOQUOTES flag).', false, false);
        $this->registerArgument('encoding', 'string', 'Define the encoding used when converting characters (Default: UTF-8');
        $this->registerArgument('doubleEncode', 'bool', 'If FALSE existing html entities won\'t be encoded, the default is to convert everything.', false, true);
    }

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs htmlentities() function.
     *
     * @see https://www.php.net/manual/function.htmlentities.php
     * @return mixed
     */
    public function render()
    {
        $value = $this->renderChildren();
        $encoding = $this->arguments['encoding'];
        $keepQuotes = $this->arguments['keepQuotes'];
        $doubleEncode = $this->arguments['doubleEncode'];
        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return $value;
        }
        if ($encoding === null) {
            $encoding = self::resolveDefaultEncoding();
        }
        $flags = $keepQuotes ? ENT_NOQUOTES : ENT_QUOTES;
        return htmlentities((string)$value, $flags, $encoding, $doubleEncode);
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'value';
    }
}
