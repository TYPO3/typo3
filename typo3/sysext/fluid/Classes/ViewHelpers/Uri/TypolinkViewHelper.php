<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

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
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * A ViewHelper to create uris from fields supported by the link wizard
 *
 * == Example ==
 *
 * {link} contains "19 - - - &X=y"
 * Please note that due to the nature of typolink you have to provide a
 * full set of parameters if you use the parameter only. Target, class
 * and title will be discarded.
 *
 * <code title="minimal usage">
 * <f:uri.typolink parameter="{link}" />
 * <output>
 * index.php?id=19&X=y
 * </output>
 * </code>
 *
 * <code title="Full parameter usage">
 * <f:uri.typolink parameter="{link}" additionalParams="&u=b" useCacheHash="true" />
 * </code>
 * <output>
 * index.php?id=19&X=y&u=b
 * </output>
 */
class TypolinkViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('parameter', 'string', 'stdWrap.typolink style parameter string', true);
        $this->registerArgument('additionalParams', 'string', 'stdWrap.typolink additionalParams', false, '');
        $this->registerArgument('useCacheHash', 'bool', '', false, false);
        $this->registerArgument('addQueryString', 'bool', '', false, false);
        $this->registerArgument('addQueryStringMethod', 'string', '', false, 'GET');
        $this->registerArgument('addQueryStringExclude', 'string', '', false, '');
        $this->registerArgument('absolute', 'bool', 'Ensure the resulting URL is an absolute URL', false, false);
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
        $parameter = $arguments['parameter'];
        $additionalParams = $arguments['additionalParams'];
        $useCacheHash = $arguments['useCacheHash'];
        $addQueryString = $arguments['addQueryString'];
        $addQueryStringMethod = $arguments['addQueryStringMethod'];
        $addQueryStringExclude = $arguments['addQueryStringExclude'];
        $absolute = $arguments['absolute'];

        $content = '';
        if ($parameter) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $content = $contentObject->typoLink_URL(
                [
                    'parameter' => self::createTypolinkParameterFromArguments($parameter, $additionalParams),
                    'useCacheHash' => $useCacheHash,
                    'addQueryString' => $addQueryString,
                    'addQueryString.' => [
                        'method' => $addQueryStringMethod,
                        'exclude' => $addQueryStringExclude
                    ],
                    'forceAbsoluteUrl' => $absolute
                ]
            );
        }

        return $content;
    }

    /**
     * Transforms ViewHelper arguments to typo3link.parameters.typoscript option as array.
     *
     * @param string $parameter Example: 19 _blank - "testtitle with whitespace" &X=y
     * @param string $additionalParameters
     *
     * @return string The final TypoLink string
     */
    protected static function createTypolinkParameterFromArguments($parameter, $additionalParameters = '')
    {
        $typoLinkCodec = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $typolinkConfiguration = $typoLinkCodec->decode($parameter);

        // Combine additionalParams
        if ($additionalParameters) {
            $typolinkConfiguration['additionalParams'] .= $additionalParameters;
        }

        return $typoLinkCodec->encode($typolinkConfiguration);
    }
}
