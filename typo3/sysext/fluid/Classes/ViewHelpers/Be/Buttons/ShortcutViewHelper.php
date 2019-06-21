<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Buttons;

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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * ViewHelper which returns shortcut button with icon.
 *
 * .. note::
 *    This ViewHelper is experimental!
 *
 * Examples
 * ========
 *
 * Default::
 *
 *    <f:be.buttons.shortcut />
 *
 * Shortcut button as known from the TYPO3 backend.
 * By default the current page id, module name and all module arguments will be stored.
 *
 * Explicitly set parameters to be stored in the shortcut::
 *
 *    <f:be.buttons.shortcut getVars="{0: 'route', 1: 'myOwnPrefix'}" setVars="{0: 'function'}" />
 *
 * Shortcut button as known from the TYPO3 backend.
 * This time only the specified GET parameters and SET[]-settings will be stored.
 *
 * .. note:
 *
 *    Normally you won't need to set getVars & setVars parameters in Extbase modules.
 */
class ShortcutViewHelper extends AbstractBackendViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
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
        parent::initializeArguments();
        $this->registerArgument('getVars', 'array', 'List of GET variables to store. By default the current id, module and all module arguments will be stored', false, []);
        $this->registerArgument('setVars', 'array', 'List of SET[] variables to store. See DocumentTemplate::makeShortcutIcon(). Normally won\'t be used by Extbase modules', false, []);
    }

    /**
     * Renders a shortcut button as known from the TYPO3 backend
     *
     * @return string the rendered shortcut button
     * @see \TYPO3\CMS\Backend\Template\DocumentTemplate::makeShortcutIcon()
     */
    public function render()
    {
        return static::renderStatic(
            $this->arguments,
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $getVars = $arguments['getVars'];
        $setVars = $arguments['setVars'];

        $mayMakeShortcut = $GLOBALS['BE_USER']->mayMakeShortcut();

        if ($mayMakeShortcut) {
            $doc = GeneralUtility::makeInstance(DocumentTemplate::class);
            $currentRequest = $renderingContext->getControllerContext()->getRequest();
            $extensionName = $currentRequest->getControllerExtensionName();
            $moduleName = $currentRequest->getPluginName();
            if (count($getVars) === 0) {
                $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
                $getVars = ['id', 'route', $modulePrefix];
            }
            $getList = implode(',', $getVars);
            $setList = implode(',', $setVars);
            return $doc->makeShortcutIcon($getList, $setList, $moduleName);
        }
        return '';
    }
}
