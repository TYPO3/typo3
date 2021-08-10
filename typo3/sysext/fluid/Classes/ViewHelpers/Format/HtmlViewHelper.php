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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Renders a string by passing it to a TYPO3 `parseFunc`_.
 * You can either specify a path to the TypoScript setting or set the `parseFunc`_ options directly.
 * By default :ts:`lib.parseFunc_RTE` is used to parse the string.
 *
 * Examples
 * ========
 *
 * Default parameters
 * ------------------
 *
 * ::
 *
 *    <f:format.html>foo <b>bar</b>. Some <LINK 1>link</LINK>.</f:format.html>
 *
 * Output::
 *
 *    <p class="bodytext">foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.</p>
 *
 * Depending on TYPO3 setup.
 *
 * Custom parseFunc
 * ----------------
 *
 * ::
 *
 *    <f:format.html parseFuncTSPath="lib.parseFunc">foo <b>bar</b>. Some <LINK 1>link</LINK>.</f:format.html>
 *
 * Output::
 *
 *    foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {someText -> f:format.html(parseFuncTSPath: 'lib.parseFunc')}
 *
 * Output::
 *
 *    foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.
 *
 * .. _parseFunc: https://docs.typo3.org/m/typo3/reference-typoscript/master/en-us/Functions/Parsefunc.html
 */
class HtmlViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
     */
    protected static $tsfeBackup;

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

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('parseFuncTSPath', 'string', 'Path to TypoScript parseFunc setup', false, 'lib.parseFunc_RTE');
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string the parsed string.
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $parseFuncTSPath = $arguments['parseFuncTSPath'];
        if (TYPO3_MODE === 'BE') {
            self::simulateFrontendEnvironment();
        }
        $value = $renderChildrenClosure();
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObject->start([]);
        $content = $contentObject->parseFunc($value, [], '< ' . $parseFuncTSPath);
        if (TYPO3_MODE === 'BE') {
            self::resetFrontendEnvironment();
        }
        return $content;
    }

    /**
     * Copies the specified parseFunc configuration to $GLOBALS['TSFE']->tmpl->setup in Backend mode
     * This somewhat hacky work around is currently needed because the parseFunc() function of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer relies on those variables to be set
     */
    protected static function simulateFrontendEnvironment()
    {
        self::$tsfeBackup = $GLOBALS['TSFE'] ?? null;
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
        $GLOBALS['TSFE']->tmpl->setup = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     *
     * @see simulateFrontendEnvironment()
     */
    protected static function resetFrontendEnvironment()
    {
        $GLOBALS['TSFE'] = self::$tsfeBackup;
    }
}
