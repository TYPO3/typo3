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

namespace TYPO3\CMS\Belog\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Get workspace title from workspace id
 *
 * @internal
 */
final class WorkspaceTitleViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * First level cache of workspace titles
     */
    protected static array $workspaceTitleRuntimeCache = [];

    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'UID of the workspace', true);
    }

    /**
     * Return resolved workspace title or empty string if it can not be resolved.
     *
     * @param array{uid: int} $arguments
     * @throws \InvalidArgumentException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $uid = $arguments['uid'];
        if (isset(self::$workspaceTitleRuntimeCache[$uid])) {
            return self::$workspaceTitleRuntimeCache[$uid];
        }
        if ($uid === 0) {
            self::$workspaceTitleRuntimeCache[$uid] = htmlspecialchars(self::getLanguageService()->sL(
                'LLL:EXT:belog/Resources/Private/Language/locallang.xlf:live'
            ));
        } elseif (!ExtensionManagementUtility::isLoaded('workspaces')) {
            self::$workspaceTitleRuntimeCache[$uid] = '';
        } else {
            $workspace = BackendUtility::getRecord('sys_workspace', $uid);
            self::$workspaceTitleRuntimeCache[$uid] = $workspace['title'] ?? '';
        }
        return self::$workspaceTitleRuntimeCache[$uid];
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
