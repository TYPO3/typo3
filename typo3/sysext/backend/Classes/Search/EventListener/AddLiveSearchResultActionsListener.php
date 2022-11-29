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

namespace TYPO3\CMS\Backend\Search\EventListener;

use TYPO3\CMS\Backend\Search\Event\ModifyResultItemInLiveSearchEvent;
use TYPO3\CMS\Backend\Search\LiveSearch\DatabaseRecordProvider;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItem;
use TYPO3\CMS\Backend\Search\LiveSearch\ResultItemAction;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Event listener to add actions to search results
 *
 * @internal
 */
final class AddLiveSearchResultActionsListener
{
    protected LanguageService $languageService;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly LanguageServiceFactory $languageServiceFactory
    ) {
        $this->languageService = $this->languageServiceFactory->createFromUserPreferences($this->getBackendUser());
    }

    public function __invoke(ModifyResultItemInLiveSearchEvent $event): void
    {
        $resultItem = $event->getResultItem();
        if ($resultItem->getProviderClassName() !== DatabaseRecordProvider::class) {
            return;
        }

        if (($resultItem->getExtraData()['table'] ?? null) === 'be_users') {
            $this->addSwitchUserAction($resultItem);
        }
    }

    protected function addSwitchUserAction(ResultItem $resultItem): void
    {
        $row = $resultItem->getInternalData()['row'];
        $backendUserIsActive =
            (int)$row['disable'] === 0
            && ($row['starttime'] === 0 && $row['endtime'] === 0 || $row['starttime'] <= time() && ($row['starttime'] === 0 && $row['endtime'] > time()));
        $currentUser = $this->getBackendUser();

        if (
            $backendUserIsActive
            && (int)(($currentUser->user[$currentUser->userid_column] ?? 0) !== $resultItem->getExtraData()['uid'])
            && $currentUser->isAdmin()
            && $currentUser->getOriginalUserIdWhenInSwitchUserMode() === null
        ) {
            $switchUserAction = (new ResultItemAction('switch_backend_user'))
                ->setLabel($this->languageService->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:switchBackMode'))
                ->setIcon($this->iconFactory->getIcon('actions-system-backend-user-switch', Icon::SIZE_SMALL))
                ->setUrl('#');
            $resultItem->addAction($switchUserAction);
        }
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
