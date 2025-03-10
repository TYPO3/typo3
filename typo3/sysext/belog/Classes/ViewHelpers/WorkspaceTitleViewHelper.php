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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to get workspace title from a workspace id.
 *
 * ```
 *   belog:workspaceTitle uid="{logItem.workspaceUid}" />
 * ```
 *
 * @internal
 */
final class WorkspaceTitleViewHelper extends AbstractViewHelper
{
    public function __construct(
        #[Autowire(service: 'cache.runtime')]
        private readonly FrontendInterface $workspaceTitleRuntimeCache
    ) {}

    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'UID of the workspace', true);
    }

    /**
     * Return resolved workspace title or empty string if it can not be resolved.
     *
     * @throws \InvalidArgumentException
     */
    public function render(): string
    {
        $uid = $this->arguments['uid'];
        $cacheIdentifier = 'belog-viewhelper-workspace-title_' . $uid;
        if ($this->workspaceTitleRuntimeCache->has($cacheIdentifier)) {
            return $this->workspaceTitleRuntimeCache->get($cacheIdentifier);
        }
        if ($uid === 0) {
            $this->workspaceTitleRuntimeCache->set($cacheIdentifier, htmlspecialchars(self::getLanguageService()->sL(
                'LLL:EXT:belog/Resources/Private/Language/locallang.xlf:live'
            )));
        } elseif (!ExtensionManagementUtility::isLoaded('workspaces')) {
            $this->workspaceTitleRuntimeCache->set($cacheIdentifier, '');
        } else {
            $workspace = BackendUtility::getRecord('sys_workspace', $uid);
            $this->workspaceTitleRuntimeCache->set($cacheIdentifier, $workspace['title'] ?? '');
        }
        return $this->workspaceTitleRuntimeCache->get($cacheIdentifier);
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
