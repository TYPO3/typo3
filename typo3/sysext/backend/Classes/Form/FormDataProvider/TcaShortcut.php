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
use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/**
 * Set the field shortcut to required if shortcut_mode is set to 0 (default)
 */
class TcaShortcut implements FormDataProviderInterface
{
    public function addData(array $result): array
    {
        $tableName = $result['tableName'];
        if ($tableName !== 'pages') {
            return $result;
        }

        if (!in_array('shortcut', $result['columnsToProcess'])
            || !in_array('shortcut_mode', $result['columnsToProcess'])
        ) {
            return $result;
        }

        $shortcutMode = $result['databaseRow']['shortcut_mode'] ?? null;
        if ($shortcutMode !== null && (int)($shortcutMode[0] ?? $shortcutMode) === PageRepository::SHORTCUT_MODE_NONE) {
            $result['processedTca']['columns']['shortcut']['config']['required'] = true;
        }

        return $result;
    }
}
