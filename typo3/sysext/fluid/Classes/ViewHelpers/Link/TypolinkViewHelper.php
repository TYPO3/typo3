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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * A ViewHelper to create links from fields supported by the link wizard
 *
 * == Example ==
 *
 * {link} contains "19 _blank - "testtitle with whitespace" &X=y"
 *
 * <code title="minimal usage">
 * <f:link.typolink parameter="{link}">
 * Linktext
 * </f:link.typolink>
 * <output>
 * <a href="index.php?id=19&X=y" title="testtitle with whitespace" target="_blank">
 * Linktext
 * </a>
 * </output>
 * </code>
 *
 * <code title="Full parameter usage">
 * <f:link.typolink parameter="{link}" target="_blank" class="ico-class" title="some title" additionalParams="&u=b" additionalAttributes="{type:'button'}">
 * Linktext
 * </f:link.typolink>
 * </code>
 * <output>
 * <a href="index.php?id=19&X=y&u=b" title="some title" target="_blank" class="ico-class" type="button">
 * Linktext
 * </a>
 * </output>
 *
 */
class TypolinkViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Render
     *
     * @param string $parameter stdWrap.typolink style parameter string
     * @param string $target
     * @param string $class
     * @param string $title
     * @param string $additionalParams
     * @param array $additionalAttributes
     *
     * @return string
     */
    public function render($parameter, $target = '', $class = '', $title = '', $additionalParams = '', $additionalAttributes = array())
    {
        return static::renderStatic(
            array(
                'parameter' => $parameter,
                'target' => $target,
                'class' => $class,
                'title' => $title,
                'additionalParams' => $additionalParams,
                'additionalAttributes' => $additionalAttributes
            ),
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed|string
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $parameter = $arguments['parameter'];
        $target = $arguments['target'];
        $class = $arguments['class'];
        $title = $arguments['title'];
        $additionalParams = $arguments['additionalParams'];
        $additionalAttributes = $arguments['additionalAttributes'];

        // Merge the $parameter with other arguments
        $typolinkParameter = self::createTypolinkParameterArrayFromArguments($parameter, $target, $class, $title, $additionalParams);

        // array(param1 -> value1, param2 -> value2) --> param1="value1" param2="value2" for typolink.ATagParams
        $extraAttributes = array();
        foreach ($additionalAttributes as $attributeName => $attributeValue) {
            $extraAttributes[] = $attributeName . '="' . htmlspecialchars($attributeValue) . '"';
        }
        $aTagParams = implode(' ', $extraAttributes);

        // If no link has to be rendered, the inner content will be returned as such
        $content = (string)$renderChildrenClosure();

        if ($parameter) {
            /** @var ContentObjectRenderer $contentObject */
            $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $contentObject->start(array(), '');
            $content = $contentObject->stdWrap(
                $content,
                array(
                    'typolink.' => array(
                        'parameter' => $typolinkParameter,
                        'ATagParams' => $aTagParams,
                    )
                )
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
            if ($typolinkConfiguration['class']) {
                $typolinkConfiguration['class'] .= ' ';
            }
            $typolinkConfiguration['class'] .= $class;
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
