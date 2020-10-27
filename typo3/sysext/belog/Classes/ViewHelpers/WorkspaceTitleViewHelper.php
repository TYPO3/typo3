<?php

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

namespace TYPO3\CMS\Belog\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Get workspace title from workspace id
 * @internal
 */
class WorkspaceTitleViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * First level cache of workspace titles
     *
     * @var array
     */
    protected static $workspaceTitleRuntimeCache = [];

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('uid', 'int', 'UID of the workspace', true);
    }

    /**
     * Resolve workspace title from UID.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string workspace title or UID
     * @throws \InvalidArgumentException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if (!$renderingContext instanceof RenderingContext) {
            throw new \InvalidArgumentException('The given rendering context is not of type "TYPO3\CMS\Fluid\Core\Rendering\RenderingContext"', 1468363946);
        }

        $uid = $arguments['uid'];
        if (isset(static::$workspaceTitleRuntimeCache[$uid])) {
            return static::$workspaceTitleRuntimeCache[$uid];
        }

        if ($uid === 0) {
            static::$workspaceTitleRuntimeCache[$uid] = LocalizationUtility::translate(
                'live',
                $renderingContext->getRequest()->getControllerExtensionName()
            );
        } elseif (!ExtensionManagementUtility::isLoaded('workspaces')) {
            static::$workspaceTitleRuntimeCache[$uid] = '';
        } else {
            $workspace = BackendUtility::getRecord('sys_workspace', $uid);
            static::$workspaceTitleRuntimeCache[$uid] = $workspace['title'] ?? '';
        }

        return static::$workspaceTitleRuntimeCache[$uid];
    }
}
