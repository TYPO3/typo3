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

/**
 * Applies html_entity_decode() to a value
 * @see http://www.php.net/html_entity_decode
 *
 * = Examples =
 *
 * <code title="default notation">
 * <f:format.htmlentitiesDecode>{text}</f:format.htmlentitiesDecode>
 * </code>
 * <output>
 * Text with &amp; &quot; &lt; &gt; replaced by unescaped entities (html_entity_decode applied).
 * </output>
 *
 * <code title="inline notation">
 * {text -> f:format.htmlentitiesDecode(encoding: 'ISO-8859-1')}
 * </code>
 * <output>
 * Text with &amp; &quot; &lt; &gt; replaced by unescaped entities (html_entity_decode applied).
 * </output>
 *
 * @api
 */
class HtmlentitiesDecodeViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Format\AbstractEncodingViewHelper {

	/**
	 * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
	 * can decode the text's entities.
	 *
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * Converts all HTML entities to their applicable characters as needed using PHPs html_entity_decode() function.
	 *
	 * @param string $value string to format
	 * @param boolean $keepQuotes if TRUE, single and double quotes won't be replaced (sets ENT_NOQUOTES flag)
	 * @param string $encoding
	 * @return string the altered string
	 * @see http://www.php.net/html_entity_decode
	 * @api
	 */
	public function render($value = NULL, $keepQuotes = FALSE, $encoding = NULL) {
		if ($value === NULL) {
			$value = $this->renderChildren();
		}
		if (!is_string($value)) {
			return $value;
		}
		if ($encoding === NULL) {
			$encoding = $this->resolveDefaultEncoding();
		}
		$flags = $keepQuotes ? ENT_NOQUOTES : ENT_COMPAT;
		return html_entity_decode($value, $flags, $encoding);
	}
}

?>