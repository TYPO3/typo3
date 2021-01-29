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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Render MFA status information
 *
 * @internal
 */
class MfaStatusViewHelper extends AbstractTagBasedViewHelper
{
    protected $tagName = 'span';

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
        $this->registerArgument('userUid', 'int', 'The uid of the user to check', true);
    }

    public function render(): string
    {
        $userUid = (int)($this->arguments['userUid'] ?? 0);
        if (!$userUid) {
            return '';
        }

        $backendUser = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $backendUser->enablecolumns = ['deleted' => true];
        $backendUser->setBeUserByUid($userUid);

        $mfaProviderRegistry = GeneralUtility::makeInstance(MfaProviderRegistry::class);

        // Check if user has active providers
        if (!$mfaProviderRegistry->hasActiveProviders($backendUser)) {
            return '';
        }

        // Check locked providers
        if ($mfaProviderRegistry->hasLockedProviders($backendUser)) {
            $this->tag->addAttribute('class', 'label label-warning');
            $this->tag->setContent(htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:lockedMfaProviders')));
            return $this->tag->render();
        }

        // Add mfa enabled label since we have active providers and non of them are locked
        $this->tag->addAttribute('class', 'label label-info');
        $this->tag->setContent(htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:beuser/Resources/Private/Language/locallang.xlf:mfaEnabled')));
        return $this->tag->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
