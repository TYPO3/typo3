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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
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

            // Check if richtext is enabled for the field and the user did not disable RTE in general
            if (($fieldConfig['config']['enableRichtext'] ?? false) && $this->getBackendUser()->isRTE()) {
                $richtextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
                $richtextConfiguration = $richtextConfigurationProvider->getConfiguration(
                    $result['tableName'],
                    $fieldName,
                    $result['effectivePid'],
                    (string)$result['recordTypeValue'],
                    $fieldConfig['config']
                );
                // Transform if richtext is not disabled in configuration
                if (!($richtextConfiguration['disabled'] ?? false)) {
                    // remember RTE preset name
                    $result['processedTca']['columns'][$fieldName]['config']['richtextConfigurationName'] = $fieldConfig['config']['richtextConfiguration'] ?? '';
                    // Add final resolved configuration to TCA array
                    $result['processedTca']['columns'][$fieldName]['config']['richtextConfiguration'] = $richtextConfiguration;
                    // If eval=null is set for field, value might be null ... don't transform anything in this case.
                    if ($result['databaseRow'][$fieldName] !== null) {
                        // Process "from-db-to-rte" on current value
                        $richTextParser = GeneralUtility::makeInstance(RteHtmlParser::class);
                        $result['databaseRow'][$fieldName] = $richTextParser->transformTextForRichTextEditor($result['databaseRow'][$fieldName], $richtextConfiguration['proc.'] ?? []);
                    }
                }
            }
        }

        return $result;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
