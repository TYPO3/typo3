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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to render a string which can contain HTML markup
 * by passing it to a TYPO3 `parseFunc`. This can sanitize
 * unwanted HTML tags and attributes, and keep wanted HTML syntax and
 * take care of link substitution and other parsing.
 * Either specify a path to the TypoScript setting or set the `parseFunc` options directly.
 * By default, `lib.parseFunc_RTE` is used to parse the string.
 *
 * ```
 *   <f:format.html parseFuncTSPath="lib.myCustomParseFunc">
 *       {$project} is a cool <b>CMS</b> (<a href="https://www.typo3.org">TYPO3</a>).
 *   </f:format.html>
 * ```
 *
 * **Note:** The ViewHelper must not be used in backend context, as it triggers frontend logic.
 * Instead, use `<f:sanitize.html>` within backend context to secure a given HTML string
 * or `<f:transform.html>` to parse links in HTML.
 *
 * @see https://docs.typo3.org/permalink/t3tsref:parsefunc
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-format-html
 */
final class HtmlViewHelper extends AbstractViewHelper
{
    /**
     * Children must not be escaped, to be able to pass {bodytext} directly to it
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Plain HTML should be returned, no output escaping allowed
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('parseFuncTSPath', 'string', 'Path to the TypoScript parseFunc setup.', false, 'lib.parseFunc_RTE');
        $this->registerArgument('data', 'mixed', 'Initialize the content object with this set of data. Either an array or object.');
        $this->registerArgument('current', 'string', 'Initialize the content object with this value for current property.');
        $this->registerArgument('currentValueKey', 'string', 'Define the value key, used to locate the current value for the content object');
        $this->registerArgument('table', 'string', 'The table name associated with the "data" argument.', false, '');
    }

    public function render(): string
    {
        $parseFuncTSPath = $this->arguments['parseFuncTSPath'];
        $data = $this->arguments['data'];
        $current = $this->arguments['current'];
        $currentValueKey = $this->arguments['currentValueKey'];
        $table = $this->arguments['table'];
        $request = null;
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        $isBackendRequest = $request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isBackend();
        if ($isBackendRequest) {
            throw new \RuntimeException(
                'Using f:format.html in backend context is not allowed. Use f:sanitize.html or f:transform.html instead.',
                1686813703
            );
        }
        $value = $this->renderChildren() ?? '';
        // Prepare data array
        if (is_object($data)) {
            $data = ObjectAccess::getGettableProperties($data);
        } elseif (!is_array($data)) {
            $data = (array)$data;
        }
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObject->setRequest($request);
        $contentObject->start($data, $table);
        if ($current !== null) {
            $contentObject->setCurrentVal($current);
        } elseif ($currentValueKey !== null && isset($data[$currentValueKey])) {
            $contentObject->setCurrentVal($data[$currentValueKey]);
        }
        $content = $contentObject->parseFunc($value, null, '< ' . $parseFuncTSPath);
        return $content;
    }
}
