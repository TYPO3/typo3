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
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Renders a string by passing it to a TYPO3 `parseFunc`_.
 * You can either specify a path to the TypoScript setting or set the `parseFunc`_ options directly.
 * By default :typoscript:`lib.parseFunc_RTE` is used to parse the string.
 *
 * The view helper must not be used in backend context, as it triggers frontend logic.
 * Instead, use :html:`<f:sanitize.html />` to secure a given HTML string or :html:`<f:transform.html />`
 * to parse links in HTML.
 *
 * Examples
 * ========
 *
 * Default parameters
 * ------------------
 *
 * ::
 *
 *    <f:format.html>{$myConstant.project} is a cool <b>CMS</b> (<a href="https://www.typo3.org">TYPO3</a>).</f:format.html>
 *
 * Output::
 *
 *    <p class="bodytext">TYPO3 is a cool <strong>CMS</strong> (<a href="https://www.typo3.org" target="_blank">TYPO3</a>).</p>
 *
 * Depending on TYPO3 constants.
 *
 * Custom parseFunc
 * ----------------
 *
 * ::
 *
 *    <f:format.html parseFuncTSPath="lib.parseFunc">TYPO3 is a cool <b>CMS</b> (<a href="https://www.typo3.org">TYPO3</a>).</f:format.html>
 *
 * Output::
 *
 *    TYPO3 is a cool <strong>CMS</strong> (<a href="https://www.typo3.org" target="_blank">TYPO3</a>).
 *
 * Data argument
 * --------------
 *
 * If you work with TypoScript :typoscript:`field` property, you should add the current record as `data`
 * to the ViewHelper to allow processing the `field` and `dataWrap` properties correctly.
 *
 * ::
 *
 *    <f:format.html data="{newsRecord}" parseFuncTSPath="lib.news">News title: </f:format.html>
 *
 * After "dataWrap = |<strong>{FIELD:title}</strong>" you may have this Output::
 *
 *    News title: <strong>TYPO3, greatest CMS ever</strong>
 *
 * Current argument
 * -----------------
 *
 * Use the `current` argument to set the current value of the content object.
 *
 * ::
 *
 *    <f:format.html current="{strContent}" parseFuncTSPath="lib.info">I'm gone</f:format.html>
 *
 * After `setContentToCurrent = 1` you may have this output::
 *
 *    Thanks Kasper for this great CMS
 *
 * CurrentValueKey argument
 * -------------------------
 *
 * Use the `currentValueKey` argument to define a value of data object as the current value.
 *
 * ::
 *
 *    <f:format.html data="{contentRecord}" currentValueKey="header" parseFuncTSPath="lib.content">Content: </f:format.html>
 *
 * After `dataWrap = |{CURRENT:1}` you may have this Output::
 *
 *    Content: How to install TYPO3 in under 2 minutes ;-)
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
 *    TYPO3 is a cool <strong>CMS</strong> (<a href="https://www.typo3.org" target="_blank">TYPO3</a>).
 *
 * .. _parseFunc: https://docs.typo3.org/m/typo3/reference-typoscript/main/en-us/Functions/Parsefunc.html
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
        $this->registerArgument('parseFuncTSPath', 'string', 'Path to the TypoScript parseFunc setup.', false, 'lib.parseFunc_RTE');
        $this->registerArgument('data', 'mixed', 'Initialize the content object with this set of data. Either an array or object.');
        $this->registerArgument('current', 'string', 'Initialize the content object with this value for current property.');
        $this->registerArgument('currentValueKey', 'string', 'Define the value key, used to locate the current value for the content object');
        $this->registerArgument('table', 'string', 'The table name associated with the "data" argument.', false, '');
    }

    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $parseFuncTSPath = $arguments['parseFuncTSPath'];
        $data = $arguments['data'];
        $current = $arguments['current'];
        $currentValueKey = $arguments['currentValueKey'];
        $table = $arguments['table'];

        /** @var RenderingContext $renderingContext */
        $request = $renderingContext->getRequest();
        $isBackendRequest = $request instanceof ServerRequestInterface && ApplicationType::fromRequest($request)->isBackend();
        if ($isBackendRequest) {
            // @deprecated since v12, remove in v13: Drop simulateFrontendEnvironment() and resetFrontendEnvironment() and throw a \RuntimeException here.
            trigger_error('Using f:format.html in backend context has been deprecated in TYPO3 v12 and will be removed with v13', E_USER_DEPRECATED);
            $tsfeBackup = self::simulateFrontendEnvironment();
        }

        $value = $renderChildrenClosure() ?? '';

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
        // @todo: We may want to deprecate this entirely and throw an exception in v13 when this VH is used in BE scope:
        //        Core has no BE related usages to this anymore and in general it shouldn't be needed in BE scope at all.
        //        If BE really relies on content being processed via FE parseFunc, a controller should do this and assign
        //        the processed value directly, which could then be rendered using f:format.raw.
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
