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

use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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
final class HtmlViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

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
        $this->registerArgument('parseFuncTSPath', 'string', ' path to TypoScript parseFunc setup.', false, 'lib.parseFunc_RTE');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $parseFuncTSPath = $arguments['parseFuncTSPath'];
        $isBackendRequest = ApplicationType::fromRequest($renderingContext->getRequest())->isBackend();
        if ($isBackendRequest) {
            $tsfeBackup = self::simulateFrontendEnvironment();
        }
        $value = $renderChildrenClosure();
        $contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $contentObject->start([]);
        $content = $contentObject->parseFunc($value, [], '< ' . $parseFuncTSPath);
        if ($isBackendRequest) {
            self::resetFrontendEnvironment($tsfeBackup);
        }
        return $content;
    }

    /**
     * Copies the specified parseFunc configuration to $GLOBALS['TSFE']->tmpl->setup in Backend mode.
     * This somewhat hacky work around is currently needed because ContentObjectRenderer->parseFunc() relies on those variables to be set.
     *
     * @return ?TypoScriptFrontendController The 'old' backed up $GLOBALS['TSFE'] or null
     */
    protected static function simulateFrontendEnvironment(): ?TypoScriptFrontendController
    {
        $tsfeBackup = $GLOBALS['TSFE'] ?? null;
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $configurationManager = GeneralUtility::makeInstance(ConfigurationManagerInterface::class);
        $GLOBALS['TSFE']->tmpl->setup = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        return $tsfeBackup;
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     */
    protected static function resetFrontendEnvironment(?TypoScriptFrontendController $tsfeBackup): void
    {
        $GLOBALS['TSFE'] = $tsfeBackup;
    }
}
