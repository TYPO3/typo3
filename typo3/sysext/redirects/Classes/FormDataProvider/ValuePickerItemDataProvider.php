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

namespace TYPO3\CMS\Redirects\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
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
            $domains = $this->getHosts();
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
     * Get all hosts from sites
     *
     * @return string[] domain records
     */
    protected function getHosts(): array
    {
        $domains = [];
        foreach ($this->siteFinder->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $language) {
                $domains[] = $language->getBase()->getHost();
            }
        }
        $domains = array_unique($domains);
        sort($domains, SORT_NATURAL);
        return $domains;
    }
}
