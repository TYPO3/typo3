<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

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
     * @throws \UnexpectedValueException
     */
    public function addData(array $result)
    {
        $languageService = $this->getLanguageService();

        $pageTs = $result['pageTsConfig'];
        $defaultLanguageLabel = $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage');
        if (isset($pageTs['mod.']['SHARED.']['defaultLanguageLabel'])) {
            $defaultLanguageLabel = $pageTs['mod.']['SHARED.']['defaultLanguageLabel'] . ' (' . $languageService->sL($defaultLanguageLabel) . ')';
        }
        $defaultLanguageFlag = 'empty-empty';
        if (isset($pageTs['mod.']['SHARED.']['defaultLanguageFlag'])) {
            $defaultLanguageFlag = 'flags-' . $pageTs['mod.']['SHARED.']['defaultLanguageFlag'];
        }

        $languageRows = [
            -1 => [
                // -1: "All" languages
                'uid' => -1,
                'title' => $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:multipleLanguages'),
                // Same as for 0, but iso is used in flex form context only and duplication handled there
                // @todo: Maybe drop this if flex form language handling is extracted?
                'iso' => 'DEF',
                'flagIconIdentifier' => 'flags-multiple',
            ],
            0 => [
                // 0: "Default" language
                'uid' => 0,
                'title' => $defaultLanguageLabel,
                // Default "DEF" is a fallback preparation for flex form iso codes "lDEF"
                // @todo: Maybe drop this if flex form language handling is extracted?
                'iso' => 'DEF',
                'flagIconIdentifier' => $defaultLanguageFlag,
            ],
        ];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_language');

        $queryBuilder->getRestrictions()->removeAll();

        $queryResult = $queryBuilder
            ->select('uid', 'title', 'language_isocode', 'flag')
            ->from('sys_language')
            ->where($queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)))
            ->orderBy('sorting')
            ->execute();

        while ($row = $queryResult->fetch()) {
            $uid = $row['uid'];
            $languageRows[$uid] = [
                'uid' => $uid,
                'title' => $row['title'],
                'flagIconIdentifier' => 'flags-' . $row['flag'],
            ];
            if (!empty($row['language_isocode'])) {
                $languageRows[$uid]['iso'] = $row['language_isocode'];
            } else {
                // No iso code could be found. This is currently possible in the system but discouraged.
                // So, code within FormEngine has to be suited to work with an empty iso code. However,
                // it may impact certain multi language scenarios, so we add a flash message hinting for
                // incomplete configuration here.
                // It might be possible to convert this to a non-catchable exception later if
                // it iso code is enforced on a different layer of the system (tca required + migration wizard).
                // @todo: This could be relaxed again if flex form language handling is extracted,
                // @todo: since the rest of the FormEngine code does not rely on iso code?
                $message = sprintf(
                    $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:error.missingLanguageIsocode'),
                    $row['title'],
                    $uid
                );
                /** @var FlashMessage $flashMessage */
                $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $message,
                        '',
                        FlashMessage::ERROR
                );
                /** @var $flashMessageService FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
                $languageRows[$uid]['iso'] = '';
            }
        }

        $result['systemLanguageRows'] = $languageRows;

        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
