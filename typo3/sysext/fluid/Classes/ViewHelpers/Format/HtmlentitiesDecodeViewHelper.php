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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * Applies :php:`html_entity_decode()` to a value.
 * See https://www.php.net/html_entity_decode.
 *
 * Examples
 * ========
 *
 * Default notation
 * ----------------
 *
 * ::
 *
 *    <f:format.htmlentitiesDecode>{text}</f:format.htmlentitiesDecode>
 *
 * Text containing the following escaped signs: ``&amp;`` ``&quot;`` ``&#039;`` ``&lt;`` ``&gt;``, will be processed by :php:`html_entity_decode()`.
 * These will result in: ``&`` ``"`` ``'`` ``<`` ``>``.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {text -> f:format.htmlentitiesDecode(encoding: 'ISO-8859-1')}
 *
 * Text containing the following escaped signs: ``&amp;`` ``&quot;`` ``&#039;`` ``&lt;`` ``&gt;``, will be processed by :php:`html_entity_decode()`.
 * These will result in: ``&`` ``"`` ``'`` ``<`` ``>``.
 *
 * But encoded as ISO-8859-1.
 */
class HtmlentitiesDecodeViewHelper extends AbstractEncodingViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * We accept value and children interchangeably, thus we must disable children escaping.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * If we decode, we must not encode again after that.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize ViewHelper arguments
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'string', 'string to format');
        $this->registerArgument('keepQuotes', 'bool', 'If TRUE, single and double quotes won\'t be replaced (sets ENT_NOQUOTES flag).', false, false);
        $this->registerArgument('encoding', 'string', '');
    }

    /**
     * Converts all HTML entities to their applicable characters as needed using PHPs html_entity_decode() function.
     *
     * @see https://www.php.net/html_entity_decode
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $renderChildrenClosure();
        $encoding = $arguments['encoding'];
        $keepQuotes = $arguments['keepQuotes'];

        if (!is_string($value)) {
            return $value;
        }
        if ($encoding === null) {
            $encoding = self::resolveDefaultEncoding();
        }
        $flags = $keepQuotes ? ENT_NOQUOTES : ENT_COMPAT;
        return html_entity_decode($value, $flags, $encoding);
    }
}
