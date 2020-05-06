<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
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
 * Full parameter usage
 * --------------------
 *
 * ::
 *
 *    <f:link.typolink parameter="{link}" additionalParams="&u=b"
 *        target="_blank"
 *        class="ico-class" title="some title"
 *        additionalAttributes="{type:'button'}"
 *        useCacheHash="true"
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
class TypolinkViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize ViewHelper arguments
     *
     * @throws Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('parameter', 'string', 'stdWrap.typolink style parameter string', true);
        $this->registerArgument('target', 'string', '', false, '');
        $this->registerArgument('class', 'string', '', false, '');
        $this->registerArgument('title', 'string', '', false, '');
        $this->registerArgument('additionalParams', 'string', '', false, '');
        $this->registerArgument('additionalAttributes', 'array', '', false, []);
        $this->registerArgument('useCacheHash', 'bool', '', false, false);
        $this->registerArgument('addQueryString', 'bool', '', false, false);
        $this->registerArgument('addQueryStringMethod', 'string', '', false, 'GET');
        $this->registerArgument('addQueryStringExclude', 'string', '', false, '');
        $this->registerArgument('absolute', 'bool', 'Ensure the resulting URL is an absolute URL', false, false);
    }

    /**
     * Render
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed|string
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $parameter = $arguments['parameter'] ?? '';
        $target = $arguments['target'] ?? '';
        $class = $arguments['class'] ?? '';
        $title = $arguments['title'] ?? '';
        $additionalParams = $arguments['additionalParams'] ?? '';
        $additionalAttributes = $arguments['additionalAttributes'] ?? [];
        $useCacheHash = $arguments['useCacheHash'] ?? false;
        $addQueryString = $arguments['addQueryString'] ?? false;
        $addQueryStringMethod = $arguments['addQueryStringMethod'] ?? 'GET';
        $addQueryStringExclude = $arguments['addQueryStringExclude'] ?? '';
        $absolute = $arguments['absolute'] ?? false;

        // Merge the $parameter with other arguments
        $typolinkParameter = self::createTypolinkParameterArrayFromArguments($parameter, $target, $class, $title, $additionalParams);

        // array(param1 -> value1, param2 -> value2) --> param1="value1" param2="value2" for typolink.ATagParams
        $extraAttributes = [];
        foreach ($additionalAttributes as $attributeName => $attributeValue) {
            $extraAttributes[] = $attributeName . '="' . htmlspecialchars($attributeValue) . '"';
        }
        $aTagParams = implode(' ', $extraAttributes);

        // If no link has to be rendered, the inner content will be returned as such
        $content = (string)$renderChildrenClosure();

        if ($parameter) {
            /** @var ContentObjectRenderer $contentObject */
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $contentObject->start([], '');
            $content = $contentObject->stdWrap(
                $content,
                [
                    'typolink.' => [
                        'parameter' => $typolinkParameter,
                        'ATagParams' => $aTagParams,
                        'useCacheHash' => $useCacheHash,
                        'addQueryString' => $addQueryString,
                        'addQueryString.' => [
                            'method' => $addQueryStringMethod,
                            'exclude' => $addQueryStringExclude
                        ],
                        'forceAbsoluteUrl' => $absolute
                    ]
                ]
            );
        }

        return $content;
    }

    /**
     * Transforms ViewHelper arguments to typo3link.parameters.typoscript option as array.
     *
     * @param string $parameter Example: 19 _blank - "testtitle \"with whitespace\"" &X=y
     * @param string $target
     * @param string $class
     * @param string $title
     * @param string $additionalParams
     *
     * @return string The final TypoLink string
     */
    protected static function createTypolinkParameterArrayFromArguments($parameter, $target = '', $class = '', $title = '', $additionalParams = '')
    {
        $typoLinkCodec = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $typolinkConfiguration = $typoLinkCodec->decode($parameter);
        if (empty($typolinkConfiguration)) {
            return $typolinkConfiguration;
        }

        // Override target if given in target argument
        if ($target) {
            $typolinkConfiguration['target'] = $target;
        }

        // Combine classes if given in both "parameter" string and "class" argument
        if ($class) {
            $classes = explode(' ', trim($typolinkConfiguration['class']) . ' ' . trim($class));
            $typolinkConfiguration['class'] = implode(' ', array_unique(array_filter($classes)));
        }

        // Override title if given in title argument
        if ($title) {
            $typolinkConfiguration['title'] = $title;
        }

        // Combine additionalParams
        if ($additionalParams) {
            $typolinkConfiguration['additionalParams'] .= $additionalParams;
        }

        return $typoLinkCodec->encode($typolinkConfiguration);
    }
}
