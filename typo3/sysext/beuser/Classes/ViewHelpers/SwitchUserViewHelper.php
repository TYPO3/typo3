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

namespace TYPO3\CMS\Beuser\ViewHelpers;

use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * ViewHelper to displays a 'SwitchUser' button to change the current backend user to the target backend user.
 *
 * ```
 *   <beuser:SwitchUser class="btn btn-default" backendUser="{backendUser}" />
 * ```
 *
 * @internal
 */
final class SwitchUserViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'typo3-backend-switch-user';

    public function __construct(
        private readonly IconFactory $iconFactory
    ) {
        parent::__construct();
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('backendUser', BackendUser::class, 'Target backendUser to switch active session to', true);
    }

    public function render(): string
    {
        /** @var BackendUser $targetUser */
        $targetUser = $this->arguments['backendUser'];
        $currentUser = self::getBackendUserAuthentication();

        if ((int)$targetUser->getUid() === (int)$currentUser->getUserId()
            || !$targetUser->isActive()
            || !$currentUser->isAdmin()
            || $currentUser->getOriginalUserIdWhenInSwitchUserMode() !== null
        ) {
            $this->tag->setTagName('span');
            $this->tag->addAttribute('class', $this->tag->getAttribute('class') . ' disabled');
            $this->tag->addAttribute('disabled', 'disabled');
            $this->tag->setContent($this->iconFactory->getIcon('empty-empty', IconSize::SMALL)->render());
        } else {
            $this->tag->addAttribute('title', self::getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:switchBackMode'));
            $this->tag->addAttribute('targetUser', (string)$targetUser->getUid());
            $this->tag->setContent($this->iconFactory->getIcon('actions-system-backend-user-switch', IconSize::SMALL)->render());
        }

        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

    private static function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
