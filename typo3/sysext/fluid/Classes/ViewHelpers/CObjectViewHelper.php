<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

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

use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithContentArgumentAndRenderStatic;

/**
 * This ViewHelper renders CObjects from the global TypoScript configuration.
 *
 * .. note::
 *    You have to ensure proper escaping (htmlspecialchars/intval/etc.) on your own!
 *
 * Examples
 * ========
 *
 * Render lib object
 * -----------------
 *
 * ::
 *
 *    <f:cObject typoscriptObjectPath="lib.someLibObject" />
 *
 * Rendered :ts:`lib.someLibObject`.
 *
 * Specify cObject data & current value
 * ------------------------------------
 *
 * ::
 *
 *    <f:cObject typoscriptObjectPath="lib.customHeader" data="{article}" currentValueKey="title" />
 *
 * Rendered :ts:`lib.customHeader`. Data and current value will be available in TypoScript.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {article -> f:cObject(typoscriptObjectPath: 'lib.customHeader')}
 *
 * Rendered :ts:`lib.customHeader`. Data will be available in TypoScript.
 *
 * Accessing the data in TypoScript
 * --------------------------------
 *
 * ::
 *
 *    lib.customHeader = COA
 *    lib.customHeader {
 *        10 = TEXT
 *        10.field = author
 *        20 = TEXT
 *        20.current = 1
 *    }
 *
 * When passing an object with ``{data}`` the properties of the object are accessable with :ts:`.field` in
 * TypoScript. If only a single value is passed or the ``currentValueKey`` is specified :ts:`.current = 1`
 * can be used in the TypoScript.
 */
class CObjectViewHelper extends AbstractViewHelper
{
    use CompileWithContentArgumentAndRenderStatic;

    /**
     * Disable escaping of child nodes' output
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Disable escaping of this node's output
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
     */
    protected static $tsfeBackup;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('data', 'mixed', 'the data to be used for rendering the cObject. Can be an object, array or string. If this argument is not set, child nodes will be used');
        $this->registerArgument('typoscriptObjectPath', 'string', 'the TypoScript setup path of the TypoScript object to render', true);
        $this->registerArgument('currentValueKey', 'string', 'currentValueKey');
        $this->registerArgument('table', 'string', 'the table name associated with "data" argument. Typically tt_content or one of your custom tables. This argument should be set if rendering a FILES cObject where file references are used, or if the data argument is a database record.', false, '');
    }

    /**
     * Renders the TypoScript object in the given TypoScript setup path.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $data = $renderChildrenClosure();
        $typoscriptObjectPath = $arguments['typoscriptObjectPath'];
        $currentValueKey = $arguments['currentValueKey'];
        $table = $arguments['table'];
        $contentObjectRenderer = static::getContentObjectRenderer();
        if (!isset($GLOBALS['TSFE']) || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)) {
            static::simulateFrontendEnvironment();
        }
        $currentValue = null;
        if (is_object($data)) {
            $data = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($data);
        } elseif (is_string($data) || is_numeric($data)) {
            $currentValue = (string)$data;
            $data = [$data];
        }
        $contentObjectRenderer->start($data, $table);
        if ($currentValue !== null) {
            $contentObjectRenderer->setCurrentVal($currentValue);
        } elseif ($currentValueKey !== null && isset($data[$currentValueKey])) {
            $contentObjectRenderer->setCurrentVal($data[$currentValueKey]);
        }
        $pathSegments = GeneralUtility::trimExplode('.', $typoscriptObjectPath);
        $lastSegment = array_pop($pathSegments);
        $setup = static::getConfigurationManager()->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        foreach ($pathSegments as $segment) {
            if (!array_key_exists($segment . '.', $setup)) {
                throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception(
                    'TypoScript object path "' . $typoscriptObjectPath . '" does not exist',
                    1253191023
                );
            }
            $setup = $setup[$segment . '.'];
        }
        if (!isset($setup[$lastSegment])) {
            throw new \TYPO3Fluid\Fluid\Core\ViewHelper\Exception(
                'No Content Object definition found at TypoScript object path "' . $typoscriptObjectPath . '"',
                1540246570
            );
        }
        $content = self::renderContentObject($contentObjectRenderer, $setup, $typoscriptObjectPath, $lastSegment);
        if (!isset($GLOBALS['TSFE']) || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)) {
            static::resetFrontendEnvironment();
        }
        return $content;
    }

    /**
     * Renders single content object and increases time tracker stack pointer
     *
     * @param ContentObjectRenderer $contentObjectRenderer
     * @param array $setup
     * @param string $typoscriptObjectPath
     * @param string $lastSegment
     * @return string
     */
    protected static function renderContentObject(ContentObjectRenderer $contentObjectRenderer, array $setup, string $typoscriptObjectPath, string $lastSegment): string
    {
        $timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        if ($timeTracker->LR) {
            $timeTracker->push('/f:cObject/', '<' . $typoscriptObjectPath);
        }
        $timeTracker->incStackPointer();
        $content = $contentObjectRenderer->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.'] ?? [], $typoscriptObjectPath);
        $timeTracker->decStackPointer();
        if ($timeTracker->LR) {
            $timeTracker->pull($content);
        }
        return $content;
    }

    /**
     * @return ConfigurationManagerInterface
     */
    protected static function getConfigurationManager()
    {
        return GeneralUtility::makeInstance(ObjectManager::class)->get(ConfigurationManagerInterface::class);
    }

    /**
     * @return ContentObjectRenderer
     */
    protected static function getContentObjectRenderer()
    {
        return GeneralUtility::makeInstance(
            ContentObjectRenderer::class,
            $GLOBALS['TSFE'] ?? GeneralUtility::makeInstance(TypoScriptFrontendController::class, null, 0, 0)
        );
    }

    /**
     * Sets the $TSFE->cObjectDepthCounter in Backend mode
     * This somewhat hacky work around is currently needed because the cObjGetSingle() function of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer relies on this setting
     */
    protected static function simulateFrontendEnvironment()
    {
        static::$tsfeBackup = $GLOBALS['TSFE'] ?? null;
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $GLOBALS['TSFE']->cObjectDepthCounter = 100;
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     *
     * @see simulateFrontendEnvironment()
     */
    protected static function resetFrontendEnvironment()
    {
        $GLOBALS['TSFE'] = static::$tsfeBackup;
    }
}
