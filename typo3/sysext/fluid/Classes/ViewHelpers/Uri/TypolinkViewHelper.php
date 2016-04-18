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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

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
 * <f:uri.typolink parameter="{link}" additionalParams="&u=b" />
 * </code>
 * <output>
 * index.php?id=19&X=y&u=b
 * </output>
 *
 */
class TypolinkViewHelper extends AbstractViewHelper
{
    /**
     * Render
     *
     * @param string $parameter stdWrap.typolink style parameter string
     * @param string $additionalParams
     *
     * @return string
     */
    public function render($parameter, $additionalParams = '')
    {
        return static::renderStatic(
            array(
                'parameter' => $parameter,
                'additionalParams' => $additionalParams
            ),
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
        $parameter = $arguments['parameter'];
        $additionalParams = $arguments['additionalParams'];

        $content = '';
        if ($parameter) {
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $content = $contentObject->typoLink_URL(
                array(
                    'parameter' => self::createTypolinkParameterArrayFromArguments($parameter, $additionalParams),
                )
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
    protected static function createTypolinkParameterArrayFromArguments($parameter, $additionalParameters = '')
    {
        $typoLinkCodec = GeneralUtility::makeInstance(TypoLinkCodecService::class);
        $typolinkConfiguration = $typoLinkCodec->decode($parameter);
        if (empty($typolinkConfiguration)) {
            return $typolinkConfiguration;
        }

        // Combine additionalParams
        if ($additionalParameters) {
            $typolinkConfiguration['additionalParams'] .= $additionalParameters;
        }

        return $typoLinkCodec->encode($typolinkConfiguration);
    }
}
