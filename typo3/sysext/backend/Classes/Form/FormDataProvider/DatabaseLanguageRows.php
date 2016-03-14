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

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Form\Exception\DatabaseDefaultLanguageException;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Add language related data to result array
 */
class DatabaseLanguageRows implements FormDataProviderInterface
{
    /**
     * Fetch default language if handled record is a localized one,
     * unserialize transOrigDiffSourceField if it is defined,
     * fetch additional languages if requested.
     *
     * @param array $result
     * @return array
     * @throws DatabaseDefaultLanguageException
     */
    public function addData(array $result)
    {
        if (!empty($result['processedTca']['ctrl']['languageField'])
            && !empty($result['processedTca']['ctrl']['transOrigPointerField'])
        ) {
            $languageField = $result['processedTca']['ctrl']['languageField'];
            $fieldWithUidOfDefaultRecord = $result['processedTca']['ctrl']['transOrigPointerField'];

            if (isset($result['databaseRow'][$languageField]) && $result['databaseRow'][$languageField] > 0
                && isset($result['databaseRow'][$fieldWithUidOfDefaultRecord]) && $result['databaseRow'][$fieldWithUidOfDefaultRecord] > 0
            ) {
                // Table pages has its overlays in pages_language_overlay, this is accounted here
                $tableNameWithDefaultRecords = $result['tableName'];
                if (!empty($result['processedTca']['ctrl']['transOrigPointerTable'])) {
                    $tableNameWithDefaultRecords = $result['processedTca']['ctrl']['transOrigPointerTable'];
                }

                // Default language record of localized record
                $defaultLanguageRow = BackendUtility::getRecordWSOL(
                    $tableNameWithDefaultRecords,
                    (int)$result['databaseRow'][$fieldWithUidOfDefaultRecord]
                );
                if (!is_array($defaultLanguageRow)) {
                    throw new DatabaseDefaultLanguageException(
                        'Default language record with id ' . (int)$result['databaseRow'][$fieldWithUidOfDefaultRecord]
                        . ' not found in table ' . $result['tableName'] . ' while editing record ' . $result['databaseRow']['uid'],
                        1438249426
                    );
                }
                $result['defaultLanguageRow'] = $defaultLanguageRow;

                // Unserialize the "original diff source" if given
                if (!empty($result['processedTca']['ctrl']['transOrigDiffSourceField'])
                    && !empty($result['databaseRow'][$result['processedTca']['ctrl']['transOrigDiffSourceField']])
                ) {
                    $defaultLanguageKey = $result['tableName'] . ':' . (int)$result['databaseRow']['uid'];
                    $result['defaultLanguageDiffRow'][$defaultLanguageKey] = unserialize($result['databaseRow'][$result['processedTca']['ctrl']['transOrigDiffSourceField']]);
                }

                // Add language overlays from further localizations if requested
                // @todo: Permission check if user is in "restrict ot language" is missing here.
                // @todo: The TranslationConfigurationProvider is more stupid than good for us ... invent a better translation overlay api!
                if (!empty($result['userTsConfig']['options.']['additionalPreviewLanguages'])) {
                    $additionalLanguageUids = GeneralUtility::intExplode(',', $result['userTsConfig']['options.']['additionalPreviewLanguages'], true);
                    /** @var TranslationConfigurationProvider $translationProvider */
                    $translationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
                    foreach ($additionalLanguageUids as $additionalLanguageUid) {
                        // Continue if this system language record does not exist or if 0 or -1 is requested
                        // or if row is the same as the to-be-displayed row
                        if ($additionalLanguageUid <= 0
                            || !isset($result['systemLanguageRows'][$additionalLanguageUid])
                            || $additionalLanguageUid === (int)$result['databaseRow'][$languageField]
                        ) {
                            continue;
                        }
                        $translationInfo = $translationProvider->translationInfo(
                            $tableNameWithDefaultRecords,
                            (int)$result['databaseRow'][$fieldWithUidOfDefaultRecord],
                            $additionalLanguageUid
                        );
                        if (!empty($translationInfo['translations'][$additionalLanguageUid]['uid'])) {
                            $record = BackendUtility::getRecordWSOL($result['tableName'], (int)$translationInfo['translations'][$additionalLanguageUid]['uid']);
                            $result['additionalLanguageRows'][$additionalLanguageUid] = $record;
                        }
                    }
                }
            }
        }

        return $result;
    }
}
