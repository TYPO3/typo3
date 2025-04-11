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

/**
 * Set effective pid we're working on
 */
class DatabaseEffectivePid implements FormDataProviderInterface
{
    /**
     * Effective pid is used to determine entry point for page ts and is also
     * the pid where new records are stored later.
     *
     * @return array
     */
    public function addData(array $result)
    {
        $effectivePid = 0;
        if ($result['command'] === 'edit' && $result['tableName'] === 'pages') {
            // We always need to detect the "live record of the default language"
            // Translated pages should always point to UID of the default language as "pid" anyway.
            // Good to know: l10n_parent in a translated versioned record (double-overlay) points
            // to the "live record of the default language"
            if (isset($result['databaseRow']['l10n_parent']) && $result['databaseRow']['l10n_parent'] > 0) {
                $effectivePid = $result['databaseRow']['l10n_parent'];
            } elseif (isset($result['databaseRow']['t3ver_oid']) && $result['databaseRow']['t3ver_oid'] > 0) {
                $effectivePid = $result['databaseRow']['t3ver_oid'];
            } else {
                $effectivePid = $result['databaseRow']['uid'];
            }
        } elseif ($result['command'] === 'edit') {
            $effectivePid = $result['databaseRow']['pid'];
        } elseif ($result['command'] === 'new' && is_array($result['parentPageRow'])) {
            $effectivePid = $result['parentPageRow']['uid'];
        }
        $result['effectivePid'] = (int)$effectivePid;

        return $result;
    }
}
