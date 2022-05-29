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
                // Default language record of localized record
                $defaultLanguageRow = $this->getRecordWorkspaceOverlay(
                    $result['tableName'],
                    (int)$result['databaseRow'][$fieldWithUidOfDefaultRecord]
                );
                if (empty($defaultLanguageRow)) {
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
                    $result['defaultLanguageDiffRow'][$defaultLanguageKey] = json_decode(
                        (string)($result['databaseRow'][$result['processedTca']['ctrl']['transOrigDiffSourceField']] ?? ''),
                        true
                    );
                }

                // Add language overlays from further localizations if requested
                // @todo: Permission check if user is in "restrict ot language" is missing here.
                // @todo: The TranslationConfigurationProvider is more stupid than good for us ... invent a better translation overlay api!
                if (!empty($result['userTsConfig']['options.']['additionalPreviewLanguages'])) {
                    $additionalLanguageUids = GeneralUtility::intExplode(',', $result['userTsConfig']['options.']['additionalPreviewLanguages'], true);
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
                            $result['tableName'],
                            (int)$result['databaseRow'][$fieldWithUidOfDefaultRecord],
                            $additionalLanguageUid
                        );
                        if (!empty($translationInfo['translations'][$additionalLanguageUid]['uid'])) {
                            $record = $this->getRecordWorkspaceOverlay(
                                $result['tableName'],
                                (int)$translationInfo['translations'][$additionalLanguageUid]['uid']
                            );
                            $result['additionalLanguageRows'][$additionalLanguageUid] = $record;
                        }
                    }
                }

                // @todo do that only if l10n_parent > 0 (not in "free mode")?
                if (!empty($result['processedTca']['ctrl']['translationSource'])
                    && is_string($result['processedTca']['ctrl']['translationSource'])
                ) {
                    $translationSourceFieldName = $result['processedTca']['ctrl']['translationSource'];
                    if (isset($result['databaseRow'][$translationSourceFieldName])
                        && $result['databaseRow'][$translationSourceFieldName] > 0
                    ) {
                        $uidOfTranslationSource = $result['databaseRow'][$translationSourceFieldName];
                        $result['sourceLanguageRow'] = $this->getRecordWorkspaceOverlay(
                            $result['tableName'],
                            $uidOfTranslationSource
                        );
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve the requested row from the database
     *
     * @param string $tableName
     * @param int $uid
     * @return array
     */
    protected function getRecordWorkspaceOverlay(string $tableName, int $uid): array
    {
        $row = BackendUtility::getRecordWSOL($tableName, $uid);

        return $row ?: [];
    }
}
