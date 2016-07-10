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
class HtmlentitiesDecodeViewHelper extends AbstractEncodingViewHelper
{
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
     * @return string the altered string
     * @see http://www.php.net/html_entity_decode
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
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $value = $arguments['value'];
        $encoding = $arguments['encoding'];
        $keepQuotes = $arguments['keepQuotes'];

        if ($value === null) {
            $value = $renderChildrenClosure();
        }
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
