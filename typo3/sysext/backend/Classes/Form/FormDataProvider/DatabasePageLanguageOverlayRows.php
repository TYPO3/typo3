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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Fill the "pageLanguageOverlayRows" part of the result array
 */
class DatabasePageLanguageOverlayRows implements FormDataProviderInterface
{
    /**
     * Fetch available page overlay records of page
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        if ($result['effectivePid'] === 0) {
            // No overlays for records on pid 0 and not for new pages below root
            return $result;
        }

        $database = $this->getDatabase();

        $dbRows = $database->exec_SELECTgetRows(
            '*',
            'pages_language_overlay',
            'pid=' . (int)$result['effectivePid']
                . BackendUtility::deleteClause('pages_language_overlay')
                . BackendUtility::versioningPlaceholderClause('pages_language_overlay')
        );

        if ($dbRows === null) {
            throw new \UnexpectedValueException(
                'Database query error ' . $database->sql_error(),
                1440777705
            );
        }

        $result['pageLanguageOverlayRows'] = $dbRows;

        return $result;
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabase()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
