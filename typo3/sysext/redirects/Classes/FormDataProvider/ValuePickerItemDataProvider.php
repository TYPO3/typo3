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
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Inject available domain hosts into a valuepicker form
 * @internal
 */
class ValuePickerItemDataProvider implements FormDataProviderInterface
{
    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    /**
     * ValuePickerItemDataProvider constructor.
     * @param SiteFinder|null $siteFinder
     */
    public function __construct(SiteFinder $siteFinder = null)
    {
        $this->siteFinder = $siteFinder ?? GeneralUtility::makeInstance(SiteFinder::class);
    }

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
                        $domain,
                        $domain,
                    ];
            }
        }
        return $result;
    }

    /**
     * Get sys_domain records from database, and all from pseudo-sites
     *
     * @return array domain records
     */
    protected function getDomains(): array
    {
        $domains = $this->getDomainsFromAllSites();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_domain');
        $sysDomainRecords = $queryBuilder
            ->select('domainName')
            ->from('sys_domain')
            ->execute()
            ->fetchAll();
        foreach ($sysDomainRecords as $domainRecord) {
            $domains[] = $domainRecord['domainName'];
        }
        $domains = array_unique($domains);
        sort($domains, SORT_NATURAL);
        return $domains;
    }

    /**
     * @return array
     */
    protected function getDomainsFromAllSites(): array
    {
        $domains = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $language) {
                $domains[] = $language->getBase()->getHost();
            }
        }
        return $domains;
    }
}
