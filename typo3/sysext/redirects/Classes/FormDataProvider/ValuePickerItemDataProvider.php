<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Redirects\FormDataProvider;

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
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Inject sys_domain records into valuepicker form
 * @internal
 */
class ValuePickerItemDataProvider implements FormDataProviderInterface
{

    /**
     * Add sys_domains into $result data array
     *
     * @param array $result Initialized result array
     * @return array Result filled with more data
     */
    public function addData(array $result): array
    {
        if ($result['tableName'] === 'sys_redirect' && isset($result['processedTca']['columns']['source_host'])) {
            $domains = $this->getDomains();
            foreach ($domains as $domain) {
                $result['processedTca']['columns']['source_host']['config']['valuePicker']['items'][] =
                [
                    $domain['domainName'],
                    $domain['domainName'],
                ];
            }
        }
        return $result;
    }

    /**
     * Get sys_domain records from database
     *
     * @return array domain records
     */
    public function getDomains(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_domain');
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        $domains = $queryBuilder
            ->select('domainName')
            ->from('sys_domain')
            ->execute()
            ->fetchAll();
        return $domains;
    }
}
