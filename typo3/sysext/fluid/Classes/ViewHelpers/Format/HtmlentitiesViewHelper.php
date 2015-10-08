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
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Applies htmlentities() escaping to a value
 * @see http://www.php.net/manual/function.htmlentities.php
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.htmlentities>{text}</f:format.htmlentities>
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlentities applied).
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.htmlentities(encoding: 'ISO-8859-1')}
 * </code>
 * <output>
 * Text with & " ' < > * replaced by HTML entities (htmlentities applied).
 * </output>
 *
 * @api
 */
class HtmlentitiesViewHelper extends AbstractEncodingViewHelper implements SingletonInterface
{
    /**
     * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
     * can decode the text's entities.
     *
     * @var bool
     */
    protected $escapingInterceptorEnabled = false;

    /**
     * Escapes special characters with their escaped counterparts as needed using PHPs htmlentities() function.
     *
     * @param string $value string to format
     * @param bool $keepQuotes if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)
     * @param string $encoding
     * @param bool $doubleEncode If FALSE existing html entities won't be encoded, the default is to convert everything.
     * @return string the altered string
     * @see http://www.php.net/manual/function.htmlentities.php
     * @api
     */
    public function render($value = null, $keepQuotes = false, $encoding = null, $doubleEncode = true)
    {
        if ($value === null) {
            $value = $this->renderChildren();
        }
        if (!is_string($value)) {
            return $value;
        }
        if ($encoding === null) {
            $encoding = self::resolveDefaultEncoding();
        }
        $flags = $keepQuotes ? ENT_NOQUOTES : ENT_COMPAT;
        return htmlentities($value, $flags, $encoding, $doubleEncode);
    }
}
