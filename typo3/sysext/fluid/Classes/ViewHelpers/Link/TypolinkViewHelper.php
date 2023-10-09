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

namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

use TYPO3\CMS\Core\LinkHandling\TypoLinkCodecService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A ViewHelper to create links from fields supported by the link wizard
 *
 * Example
 * =======
 *
 * ``{link}`` contains: ``t3://page?uid=2&arg1=val1#9 _blank some-css-class "Title containing Whitespace"``.
 *
 * Or a legacy version from older TYPO3 versions:
 * ``{link}`` contains: ``9 _blank - "testtitle with whitespace" &X=y``.
 *
 * Minimal usage
 * -------------
 *
 * ::
 *
 *    <f:link.typolink parameter="{link}">
 *       Linktext
 *    </f:link.typolink>
 *
 * Output::
 *
 *    <a href="/page/path/name.html?X=y" title="testtitle with whitespace" target="_blank">
 *       Linktext
 *    </a>
 *
 * Depending on current page, routing and page path configuration.
 *
 * TextWrap usage
 * --------------
 *
 * ::
 *
 *    <f:link.typolink parameter="123" textWrap="<span>|</span>"/>
 *
 * Output::
 *
 *    <a href="/some/page">
 *       <span>Page title of some page wrapped in span</span>
 *    </a>
 *
 * Depending on current page, routing and page path configuration.
 *
 * Full parameter usage
 * --------------------
 *
 * ::
 *
 *    <f:link.typolink parameter="{link}" additionalParams="&u=b"
 *        target="_blank"
 *        class="ico-class" title="some title"
 *        additionalAttributes="{type:'button'}"
 *    >
 *       Linktext
 *    </f:link.typolink>
 *
 * Output::
 *
 *    <a href="/page/path/name.html?X=y&u=b" title="some title" target="_blank" class="ico-class" type="button">
 *        Linktext
 *    </a>
 *
 * Depending on routing and page path configuration.
 */
final class TypolinkViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('parameter', 'string', 'stdWrap.typolink style parameter string', true);
        $this->registerArgument('target', 'string', 'Define where to display the linked URL', false, '');
        $this->registerArgument('class', 'string', 'Define classes for the link element', false, '');
        $this->registerArgument('title', 'string', 'Define the title for the link element', false, '');
        $this->registerArgument('language', 'string', 'link to a specific language - defaults to the current language, use a language ID or "current" to enforce a specific language', false);
        $this->registerArgument('additionalParams', 'string', 'Additional query parameters to be attached to the resulting URL', false, '');
        $this->registerArgument('additionalAttributes', 'array', 'Additional tag attributes to be added directly to the resulting HTML tag', false, []);
        $this->registerArgument('addQueryString', 'string', 'If set, the current query parameters will be kept in the URL. If set to "untrusted", then ALL query parameters will be added. Be aware, that this might lead to problems when the generated link is cached.', false, false);
        $this->registerArgument('addQueryStringExclude', 'string', 'Define parameters to be excluded from the query string (only active if addQueryString is set)', false, '');
        $this->registerArgument('absolute', 'bool', 'Ensure the resulting URL is an absolute URL', false, false);
        $this->registerArgument('parts-as', 'string', 'Variable name containing typoLink parts (if any)', false, 'typoLinkParts');
        $this->registerArgument('textWrap', 'string', 'Wrap the link using the typoscript "wrap" data type', false, '');
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $parameter = $arguments['parameter'] ?? '';
        $partsAs = $arguments['parts-as'] ?? 'typoLinkParts';

        $typoLinkCodec = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $typoLinkConfiguration = $typoLinkCodec->decode($parameter);
        // Merge the $parameter with other arguments
        $mergedTypoLinkConfiguration = self::mergeTypoLinkConfiguration($typoLinkConfiguration, $arguments);
        $typoLinkParameter = $typoLinkCodec->encode($mergedTypoLinkConfiguration);

        // expose internal typoLink configuration to Fluid child context
        $variableProvider = $renderingContext->getVariableProvider();
        $variableProvider->add($partsAs, $typoLinkConfiguration);
        // If no link has to be rendered, the inner content will be returned as such
        $content = (string)$renderChildrenClosure();
        // clean up exposed variables
        $variableProvider->remove($partsAs);

        if ($parameter) {
            $content = self::invokeContentObjectRenderer($arguments, $typoLinkParameter, $content);
        }
        return $content;
    }

    protected static function invokeContentObjectRenderer(array $arguments, string $typoLinkParameter, string $content): string
    {
        $addQueryString = $arguments['addQueryString'] ?? false;
        $addQueryStringExclude = $arguments['addQueryStringExclude'] ?? '';
        $absolute = $arguments['absolute'] ?? false;
        $aTagParams = self::serializeTagParameters($arguments);

        $instructions = [
            'parameter' => $typoLinkParameter,
            'ATagParams' => $aTagParams,
            'forceAbsoluteUrl' => $absolute,
        ];
        if (isset($arguments['language']) && $arguments['language'] !== null) {
            $instructions['language'] = (string)$arguments['language'];
        }
        if ($addQueryString && $addQueryString !== 'false') {
            $instructions['addQueryString'] = $addQueryString;
            $instructions['addQueryString.'] = [
                'exclude' => $addQueryStringExclude,
            ];
        }
        if ((string)($arguments['textWrap'] ?? '') !== '') {
            $instructions['ATagBeforeWrap'] = true;
            $instructions['wrap'] = $arguments['textWrap'];
        }

        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $contentObject->typoLink($content, $instructions);
    }

    protected static function serializeTagParameters(array $arguments): string
    {
        // array(param1 -> value1, param2 -> value2) --> param1="value1" param2="value2" for typolink.ATagParams
        $extraAttributes = [];
        $additionalAttributes = $arguments['additionalAttributes'] ?? [];
        foreach ($additionalAttributes as $attributeName => $attributeValue) {
            $extraAttributes[] = $attributeName . '="' . htmlspecialchars((string)$attributeValue) . '"';
        }
        return implode(' ', $extraAttributes);
    }

    /**
     * Merges view helper arguments with typolink parts.
     */
    protected static function mergeTypoLinkConfiguration(array $typoLinkConfiguration, array $arguments): array
    {
        if ($typoLinkConfiguration === []) {
            return $typoLinkConfiguration;
        }

        $target = $arguments['target'] ?? '';
        $class = $arguments['class'] ?? '';
        $title = $arguments['title'] ?? '';
        $additionalParams = $arguments['additionalParams'] ?? '';

        // Override target if given in target argument
        if ($target) {
            $typoLinkConfiguration['target'] = $target;
        }
        // Combine classes if given in both "parameter" string and "class" argument
        if ($class) {
            $classes = explode(' ', trim($typoLinkConfiguration['class']) . ' ' . trim($class));
            $typoLinkConfiguration['class'] = implode(' ', array_unique(array_filter($classes)));
        }
        // Override title if given in title argument
        if ($title) {
            $typoLinkConfiguration['title'] = $title;
        }
        // Combine additionalParams
        if ($additionalParams) {
            $typoLinkConfiguration['additionalParams'] .= $additionalParams;
        }

        return $typoLinkConfiguration;
    }
}
