<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\Service;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for URL-related data
 *
 * @internal
 */
class UrlService
{
    /**
     * Retrieves the first valid URL
     *
     * @return string a URL like "http://example.org"
     */
    public function getDefaultUrl(): string
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $firstPageInTree = $connection->select(['uid'], 'pages', ['pid' => 0], [], ['sorting' => 'ASC'], 1)->fetchColumn(0);
        $url = BackendUtility::getViewDomain($firstPageInTree);

        return $url;
    }
}
