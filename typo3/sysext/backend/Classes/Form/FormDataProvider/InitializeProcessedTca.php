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
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * This class is not named properly but will be reworked in the future.
 *
 * Currently, it is necessary to set the TCA from the outside as it needs to be faked e.g., for edit site configuration.
 * As the formengine handles inline Elements as nested call to itself the TCA and its schema needs to be hold as state.
 *
 * @todo: Once processedTca is refactored from its array shape remove keeping the full TCA and TCA schemata as state.
 */
readonly class InitializeProcessedTca implements FormDataProviderInterface
{
    public function __construct(private TcaSchemaFactory $tcaSchemaFactory) {}

    /**
     * Add full TCA as copy from vanilla TCA if not already set form the outside
     * Fetch TCA schemata from vanilla TCA if not already set from the outside
     * Add processed TCA as copy from vanilla TCA and sanitize some details
     */
    public function addData(array $result): array
    {
        $result = $this->initializeFullTca($result);
        $result = $this->initializeTcaSchemata($result);
        return $this->initializeProcessedTca($result);
    }

    private function initializeFullTca(array $result): array
    {
        if (!empty($result['fullTca'] ?? null)) {
            return $result;
        }

        $result['fullTca'] = $GLOBALS['TCA'];
        return $result;
    }

    private function initializeTcaSchemata(array $result): array
    {
        if (!empty($result['tcaSchemata'] ?? null)) {
            return $result;
        }

        $result['tcaSchemata'] = $this->tcaSchemaFactory->all();
        return $result;
    }

    private function initializeProcessedTca(array $result): array
    {
        if (empty($result['processedTca'])) {
            if (
                !isset($result['fullTca'][$result['tableName']])
                || !is_array($result['fullTca'][$result['tableName']])
            ) {
                throw new \UnexpectedValueException(
                    'TCA for table ' . $result['tableName'] . ' not found',
                    1437914223
                );
            }

            $result['processedTca'] = $result['fullTca'][$result['tableName']];
        }

        if (!is_array($result['processedTca']['columns'])) {
            throw new \UnexpectedValueException(
                'No columns definition in TCA table ' . $result['tableName'],
                1438594406
            );
        }
        return $result;
    }
}
