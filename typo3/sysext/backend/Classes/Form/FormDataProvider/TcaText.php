<?php
declare(strict_types = 1);
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
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Resolve databaseRow field content for type=text, especially handle
 * richtext transformations "from db to rte"
 */
class TcaText implements FormDataProviderInterface
{
    /**
     * Handle text field content, especially richtext transformation
     *
     * @param array $result Given result array
     * @return array Modified result array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'text') {
                continue;
            }

            if (isset($fieldConfig['config']['enableRichtext']) && (bool)$fieldConfig['config']['enableRichtext'] === true) {
                $richtextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
                $richtextConfiguration = $richtextConfigurationProvider->getConfiguration(
                    $result['tableName'],
                    $fieldName,
                    $result['effectivePid'],
                    (string)$result['recordTypeValue'],
                    $fieldConfig['config']
                );
                // remember RTE preset name
                $result['processedTca']['columns'][$fieldName]['config']['richtextConfigurationName'] = $fieldConfig['config']['richtextConfiguration'] ?? '';
                // Add final resolved configuration to TCA array
                $result['processedTca']['columns'][$fieldName]['config']['richtextConfiguration'] = $richtextConfiguration;

                // If eval=null is set for field, value might be null ... don't transform anything in this case.
                if ($result['databaseRow'][$fieldName] !== null) {
                    // Process "from-db-to-rte" on current value
                    $parseHTML = GeneralUtility::makeInstance(RteHtmlParser::class);
                    $parseHTML->init($result['tableName'] . ':' . $fieldName, $result['effectivePid']);
                    $result['databaseRow'][$fieldName] = $parseHTML->RTE_transform(
                        $result['databaseRow'][$fieldName],
                        [],
                        'rte',
                        $richtextConfiguration
                    );
                }
            }
        }

        return $result;
    }
}
