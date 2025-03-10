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
 * ViewHelper to apply `html_entity_decode()` to a value,
 * transforming HTML entity representations back into HTML special characters
 * (like `&quot;` to `"`).
 *
 * ```
 *   <f:format.htmlentitiesDecode>{textWithEntities}</f:format.htmlentitiesDecode>
 * ```
 *
 * @see https://www.php.net/html_entity_decode
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-format-htmlentitiesdecode
 */
final class HtmlentitiesDecodeViewHelper extends AbstractEncodingViewHelper
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

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('value', 'string', 'string to format');
        $this->registerArgument('keepQuotes', 'bool', 'If TRUE, single and double quotes won\'t be replaced (sets ENT_NOQUOTES flag).', false, false);
        $this->registerArgument('encoding', 'string', 'Define the encoding used when converting characters (Default: UTF-8).');
    }

    /**
     * Converts all HTML entities to their applicable characters as needed using PHPs html_entity_decode() function.
     *
     * @see https://www.php.net/html_entity_decode
     * @return mixed
     */
    public function render()
    {
        $value = $this->renderChildren();
        $encoding = $this->arguments['encoding'];
        $keepQuotes = $this->arguments['keepQuotes'];
        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return $value;
        }
        if ($encoding === null) {
            $encoding = self::resolveDefaultEncoding();
        }
        $flags = $keepQuotes ? ENT_NOQUOTES : ENT_COMPAT;
        return html_entity_decode((string)$value, $flags, $encoding);
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'value';
    }
}
