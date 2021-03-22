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

namespace TYPO3\CMS\Backend\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Works on processedTca to determine the final value of field descriptions.
 *
 * processedTca['columns']['aField']['description']
 */
class TcaColumnsProcessFieldDescriptions implements FormDataProviderInterface
{
    /**
     * Iterate over all processedTca columns fields
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    public function addData(array $result): array
    {
        $result = $this->setDescriptionFromPageTsConfig($result);
        $result = $this->translateDescriptions($result);
        return $result;
    }

    /**
     * page TSconfig can override description:
     *
     * TCEFORM.aTable.aField.description = override
     * TCEFORM.aTable.aField.description.en = override
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function setDescriptionFromPageTsConfig(array $result): array
    {
        $languageService = $this->getLanguageService();
        $tableName = $result['tableName'];
        foreach ($result['processedTca']['columns'] ?? [] as $fieldName => $fieldConfiguration) {
            $fieldTSconfig = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.'] ?? null;
            if (!is_array($fieldTSconfig)) {
                continue;
            }
            if (!empty($fieldTSconfig['description'])) {
                $result['processedTca']['columns'][$fieldName]['description'] = $fieldTSconfig['description'];
            }
            if (!empty($fieldTSconfig['description.'][$languageService->lang])) {
                $result['processedTca']['columns'][$fieldName]['description'] = $fieldTSconfig['description.'][$languageService->lang];
            }
        }
        return $result;
    }

    /**
     * Translate all descriptions if needed.
     *
     * @param array $result Result array
     * @return array Modified result array
     */
    protected function translateDescriptions(array $result): array
    {
        $languageService = $this->getLanguageService();
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfiguration) {
            if (!isset($fieldConfiguration['description'])) {
                continue;
            }
            $result['processedTca']['columns'][$fieldName]['description'] = $languageService->sL($fieldConfiguration['description']);
        }
        return $result;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
