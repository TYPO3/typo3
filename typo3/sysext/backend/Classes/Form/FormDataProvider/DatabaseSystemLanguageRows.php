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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Fill the "systemLanguageRows" part of the result array
 */
class DatabaseSystemLanguageRows implements FormDataProviderInterface
{
    /**
     * Fetch available system languages and resolve iso code if necessary.
     *
     * @param array $result
     * @return array
     * @throws \LogicException
     */
    public function addData(array $result)
    {
        $site = $result['site'] ?? null;
        if (!$site instanceof SiteInterface) {
            throw new \LogicException(
                'No valid site object found in $result[\'site\']',
                1534952559
            );
        }
        $pageIdDefaultLanguage = $result['defaultLanguagePageRow']['uid'] ?? $result['effectivePid'];
        $languages = $site->getAvailableLanguages($this->getBackendUser(), true, $pageIdDefaultLanguage);

        $languageRows = [];
        foreach ($languages as $language) {
            $languageId = $language->getLanguageId();
            if ($languageId > 0) {
                $iso = $language->getTwoLetterIsoCode();
            } else {
                $iso = 'DEF';
            }
            $languageRows[$languageId] = [
                'uid' => $languageId,
                'title' => $language->getTitle(),
                'iso' => $iso,
                'flagIconIdentifier' => $language->getFlagIdentifier(),
            ];

            if (empty($iso)) {
                // No iso code could be found. This is currently possible in the system but discouraged.
                // So, code within FormEngine has to be suited to work with an empty iso code. However,
                // it may impact certain multi language scenarios, so we add a flash message hinting for
                // incomplete configuration here.
                // It might be possible to convert this to a non-catchable exception later if
                // it iso code is enforced on a different layer of the system (tca required + migration wizard).
                // @todo: This could be relaxed again if flex form language handling is extracted,
                // @todo: since the rest of the FormEngine code does not rely on iso code?
                $message = sprintf(
                    $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.missingLanguageIsocode'),
                    $language->getTwoLetterIsoCode(),
                    $languageId
                );
                $flashMessage = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $message,
                    '',
                    FlashMessage::ERROR
                );
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }
        $result['systemLanguageRows'] = $languageRows;
        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
