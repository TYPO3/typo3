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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

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
 * Rendered :typoscript:`lib.someLibObject`.
 *
 * Specify cObject data & current value
 * ------------------------------------
 *
 * ::
 *
 *    <f:cObject typoscriptObjectPath="lib.customHeader" data="{article}" currentValueKey="title" />
 *
 * Rendered :typoscript:`lib.customHeader`. Data and current value will be available in TypoScript.
 *
 * Inline notation
 * ---------------
 *
 * ::
 *
 *    {article -> f:cObject(typoscriptObjectPath: 'lib.customHeader')}
 *
 * Rendered :typoscript:`lib.customHeader`. Data will be available in TypoScript.
 *
 * Accessing the data in TypoScript
 * --------------------------------
 *
 * .. code-block:: typoscript
 *
 *    lib.customHeader = COA
 *    lib.customHeader {
 *        10 = TEXT
 *        10.field = author
 *        20 = TEXT
 *        20.current = 1
 *    }
 *
 * When passing an object with ``{data}``, the properties of the object are accessible with :typoscript:`.field` in
 * TypoScript. If only a single value is passed or the ``currentValueKey`` is specified, :typoscript:`.current = 1`
 * can be used in the TypoScript.
 */
final class CObjectViewHelper extends AbstractViewHelper
{
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

    public function initializeArguments(): void
    {
        $this->registerArgument('data', 'mixed', 'the data to be used for rendering the cObject. Can be an object, array or string. If this argument is not set, child nodes will be used');
        $this->registerArgument('typoscriptObjectPath', 'string', 'the TypoScript setup path of the TypoScript object to render', true);
        $this->registerArgument('currentValueKey', 'string', 'currentValueKey');
        $this->registerArgument('table', 'string', 'the table name associated with "data" argument. Typically tt_content or one of your custom tables. This argument should be set if rendering a FILES cObject where file references are used, or if the data argument is a database record.', false, '');
    }

    /**
     * Renders the TypoScript object in the given TypoScript setup path.
     *
     * @throws Exception
     */
    public function render(): string
    {
        $data = $this->renderChildren() ?? [];
        $typoscriptObjectPath = (string)$this->arguments['typoscriptObjectPath'];
        $currentValueKey = $this->arguments['currentValueKey'];
        $table = $this->arguments['table'];
        if (!$this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            throw new \RuntimeException('Required request not found in RenderingContext', 1724243608);
        }
        $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        $contentObjectRenderer = self::getContentObjectRenderer($request);
        $contentObjectRenderer->setRequest($request);
        $tsfeBackup = null;
        if (!isset($GLOBALS['TSFE']) || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)) {
            $tsfeBackup = self::simulateFrontendEnvironment();
        }
        $currentValue = null;
        if (is_object($data)) {
            $data = $data instanceof RecordInterface ? ($data->getRawRecord()?->toArray() ?? $data->toArray()) : ObjectAccess::getGettableProperties($data);
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
        $lastSegment = (string)array_pop($pathSegments);
        $setup = self::getConfigurationManager()->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        foreach ($pathSegments as $segment) {
            if (!array_key_exists($segment . '.', $setup)) {
                throw new Exception(
                    'TypoScript object path "' . $typoscriptObjectPath . '" does not exist',
                    1253191023
                );
            }
            $setup = $setup[$segment . '.'];
        }
        if (!isset($setup[$lastSegment])) {
            throw new Exception(
                'No Content Object definition found at TypoScript object path "' . $typoscriptObjectPath . '"',
                1540246570
            );
        }
        $content = self::renderContentObject($contentObjectRenderer, $setup, $typoscriptObjectPath, $lastSegment);
        if (!isset($GLOBALS['TSFE']) || !($GLOBALS['TSFE'] instanceof TypoScriptFrontendController)) {
            self::resetFrontendEnvironment($tsfeBackup);
        }
        return $content;
    }

    /**
     * Renders single content object and increases time tracker stack pointer
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

    protected static function getConfigurationManager(): ConfigurationManagerInterface
    {
        // @todo: this should be replaced by DI once Fluid can handle DI properly
        return GeneralUtility::getContainer()->get(ConfigurationManagerInterface::class);
    }

    protected static function getContentObjectRenderer(ServerRequestInterface $request): ContentObjectRenderer
    {
        if (($GLOBALS['TSFE'] ?? null) instanceof TypoScriptFrontendController) {
            $tsfe = $GLOBALS['TSFE'];
        } else {
            $tsfe = GeneralUtility::makeInstance(TypoScriptFrontendController::class);
            $tsfe->initializePageRenderer($request);
            $tsfe->initializeLanguageService($request);
        }
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class, $tsfe);
        $parent = $request->getAttribute('currentContentObject');
        if ($parent instanceof ContentObjectRenderer) {
            $contentObjectRenderer->setParent($parent->data, $parent->currentRecord);
        }
        return $contentObjectRenderer;
    }

    /**
     * \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer->cObjGetSingle() relies on $GLOBALS['TSFE']
     */
    protected static function simulateFrontendEnvironment(): ?TypoScriptFrontendController
    {
        $tsfeBackup = $GLOBALS['TSFE'] ?? null;
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $tsfeBackup;
    }

    /**
     * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
     */
    protected static function resetFrontendEnvironment(?TypoScriptFrontendController $tsfeBackup): void
    {
        $GLOBALS['TSFE'] = $tsfeBackup;
    }

    /**
     * Explicitly set argument name to be used as content.
     */
    public function getContentArgumentName(): string
    {
        return 'data';
    }
}
