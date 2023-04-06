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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Displays 'SwitchUser' button to change current backend user to target backend user.
 *
 * @internal
 */
final class SwitchUserViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'typo3-backend-switch-user';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('backendUser', BackendUser::class, 'Target backendUser to switch active session to', true);
        $this->registerUniversalTagAttributes();
    }

    public function render(): string
    {
        $targetUser = $this->arguments['backendUser'];
        $currentUser = self::getBackendUserAuthentication();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        if ((int)$targetUser->getUid() === (int)($currentUser->user[$currentUser->userid_column] ?? 0)
            || !$targetUser->isActive()
            || !$currentUser->isAdmin()
            || $currentUser->getOriginalUserIdWhenInSwitchUserMode() !== null
        ) {
            $this->tag->setTagName('span');
            $this->tag->addAttribute('class', $this->tag->getAttribute('class') . ' disabled');
            $this->tag->addAttribute('disabled', 'disabled');
            $this->tag->setContent($iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render());
        } else {
            $this->tag->addAttribute('title', self::getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:switchBackMode'));
            $this->tag->addAttribute('targetUser', (string)$targetUser->getUid());
            $this->tag->setContent($iconFactory->getIcon('actions-system-backend-user-switch', Icon::SIZE_SMALL)->render());
        }

        $this->tag->forceClosingTag(true);
        return $this->tag->render();
    }

    protected static function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
