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

/**
 * Fetch page in default language from database if it's a translated pages record
 */
class DatabaseDefaultLanguagePageRow extends AbstractDatabaseRecordProvider implements FormDataProviderInterface
{
    /**
     * Add default language page row of existing row to result
     * defaultLanguagePageRow will stay NULL in result if a record is added or edited below root node
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        // $defaultLanguagePageRow end up NULL if a record added or edited on root node
        $tableName = $result['tableName'];
        if ($tableName === 'pages' && ($result['databaseRow'][$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']] ?? 0) > 0) {
            $result['defaultLanguagePageRow'] = $this->getRecordFromDatabase('pages', $result['databaseRow'][$GLOBALS['TCA'][$tableName]['ctrl']['transOrigPointerField']]);
        }
        return $result;
    }
}
