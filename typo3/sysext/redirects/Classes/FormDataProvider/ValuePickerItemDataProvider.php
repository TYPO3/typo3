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
use TYPO3\CMS\Redirects\Data\SourceHostProvider;

/**
 * Inject available domain hosts into a valuepicker form
 * @internal
 */
final class ValuePickerItemDataProvider implements FormDataProviderInterface
{
    public function __construct(
        private readonly SourceHostProvider $sourceHostProvider,
    ) {}

    /**
     * Add sys_domains into $result data array
     *
     * @param array $result Initialized result array
     * @return array Result filled with more data
     */
    public function addData(array $result): array
    {
        if ($result['tableName'] === 'sys_redirect' && isset($result['processedTca']['columns']['source_host'])) {
            $domains = $this->sourceHostProvider->getHosts();
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
}
