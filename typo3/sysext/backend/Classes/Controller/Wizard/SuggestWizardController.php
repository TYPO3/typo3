<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizardDefaultReceiver;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Receives ajax request from FormEngine suggest wizard and creates suggest answer as json result
 */
class SuggestWizardController
{
    /**
     * Ajax handler for the "suggest" feature in FormEngine.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @throws \RuntimeException for incomplete or invalid arguments
     * @return ResponseInterface
     */
    public function searchAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();

        $search = $parsedBody['value'];
        $tableName = $parsedBody['tableName'];
        $fieldName = $parsedBody['fieldName'];
        $uid = $parsedBody['uid'];
        $pid = (int)$parsedBody['pid'];
        $dataStructureIdentifier = '';
        if (!empty($parsedBody['dataStructureIdentifier'])) {
            $dataStructureIdentifier = json_encode($parsedBody['dataStructureIdentifier']);
        }
        $flexFormSheetName = $parsedBody['flexFormSheetName'];
        $flexFormFieldName = $parsedBody['flexFormFieldName'];
        $flexFormContainerName = $parsedBody['flexFormContainerName'];
        $flexFormContainerFieldName = $parsedBody['flexFormContainerFieldName'];

        // Determine TCA config of field
        if (empty($dataStructureIdentifier)) {
            // Normal columns field
            $fieldConfig = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'];
            $fieldNameInPageTsConfig = $fieldName;
        } else {
            // A flex flex form field
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $dataStructure = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            if (empty($flexFormContainerFieldName)) {
                // @todo: See if a path in pageTsConfig like "TCEForm.tableName.theContainerFieldName =" is useful and works with other pageTs, too.
                $fieldNameInPageTsConfig = $flexFormFieldName;
                if (!isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]['TCEforms']['config'])
                ) {
                    throw new \RuntimeException(
                        'Specified path ' . $flexFormFieldName . ' not found in flex form data structure',
                        1480609491
                    );
                }
                $fieldConfig = $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]['TCEforms']['config'];
            } else {
                $fieldNameInPageTsConfig = $flexFormContainerFieldName;
                if (!isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                        ['el'][$flexFormFieldName]
                        ['el'][$flexFormContainerName]
                        ['el'][$flexFormContainerFieldName]['TCEforms']['config'])
                ) {
                    throw new \RuntimeException(
                        'Specified path ' . $flexFormContainerName . ' not found in flex form section container data structure',
                        1480611208
                    );
                }
                $fieldConfig = $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]
                    ['el'][$flexFormContainerName]
                    ['el'][$flexFormContainerFieldName]['TCEforms']['config'];
            }
        }

        $pageTsConfig = BackendUtility::getPagesTSconfig($pid);

        $wizardConfig = $fieldConfig['suggestOptions'] ?? [];

        $queryTables = $this->getTablesToQueryFromFieldConfiguration($fieldConfig);
        $whereClause = $this->getWhereClause($fieldConfig);

        $resultRows = [];

        // fetch the records for each query table. A query table is a table from which records are allowed to
        // be added to the TCEForm selector, originally fetched from the "allowed" config option in the TCA
        foreach ($queryTables as $queryTable) {
            // if the table does not exist, skip it
            if (!is_array($GLOBALS['TCA'][$queryTable]) || empty($GLOBALS['TCA'][$queryTable])) {
                continue;
            }

            $config = $this->getConfigurationForTable($queryTable, $wizardConfig, $pageTsConfig, $tableName, $fieldNameInPageTsConfig);

            // process addWhere
            if (!isset($config['addWhere']) && $whereClause) {
                $config['addWhere'] = $whereClause;
            }
            if (isset($config['addWhere'])) {
                $replacement = [
                    '###THIS_UID###' => (int)$uid,
                    '###CURRENT_PID###' => (int)$pid
                ];
                if (isset($pageTsConfig['TCEFORM.'][$tableName . '.'][$fieldNameInPageTsConfig . '.'])) {
                    $fieldTSconfig = $pageTsConfig['TCEFORM.'][$tableName . '.'][$fieldNameInPageTsConfig . '.'];
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_ID'])) {
                        $replacement['###PAGE_TSCONFIG_ID###'] = (int)$fieldTSconfig['PAGE_TSCONFIG_ID'];
                    }
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_IDLIST'])) {
                        $replacement['###PAGE_TSCONFIG_IDLIST###'] =  implode(',', GeneralUtility::intExplode(',', $fieldTSconfig['PAGE_TSCONFIG_IDLIST']));
                    }
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_STR'])) {
                        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($fieldConfig['foreign_table']);
                        // nasty hack, but it's currently not possible to just quote anything "inside" the value but not escaping
                        // the whole field as it is not known where it is used in the WHERE clause
                        $replacement['###PAGE_TSCONFIG_STR###'] = trim($connection->quote($fieldTSconfig['PAGE_TSCONFIG_STR']), '\'');
                    }
                }
                $config['addWhere'] = strtr(' ' . $config['addWhere'], $replacement);
            }

            // instantiate the class that should fetch the records for this $queryTable
            $receiverClassName = $config['receiverClass'];
            if (!class_exists($receiverClassName)) {
                $receiverClassName = SuggestWizardDefaultReceiver::class;
            }
            $receiverObj = GeneralUtility::makeInstance($receiverClassName, $queryTable, $config);
            $params = ['value' => $search];
            $rows = $receiverObj->queryTable($params);
            if (empty($rows)) {
                continue;
            }
            $resultRows = $rows + $resultRows;
            unset($rows);
        }

        // Limit the number of items in the result list
        $maxItems = isset($config['maxItemsInResultList']) ? $config['maxItemsInResultList'] : 10;
        $maxItems = min(count($resultRows), $maxItems);

        array_splice($resultRows, $maxItems);

        $response->getBody()->write(json_encode(array_values($resultRows)));
        return $response;
    }

    /**
     * Returns TRUE if a table has been marked as hidden in the configuration
     *
     * @param array $tableConfig
     * @return bool
     */
    protected function isTableHidden(array $tableConfig)
    {
        return (bool)$tableConfig['ctrl']['hideTable'];
    }

    /**
     * Checks if the current backend user is allowed to access the given table, based on the ctrl-section of the
     * table's configuration array (TCA) entry.
     *
     * @param array $tableConfig
     * @return bool
     */
    protected function currentBackendUserMayAccessTable(array $tableConfig)
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }

        // If the user is no admin, they may not access admin-only tables
        if ($tableConfig['ctrl']['adminOnly']) {
            return false;
        }

        // allow access to root level pages if security restrictions should be bypassed
        return !$tableConfig['ctrl']['rootLevel'] || $tableConfig['ctrl']['security']['ignoreRootLevelRestriction'];
    }

    /**
     * Returns the configuration for the suggest wizard for the given table. This does multiple overlays from the
     * TSconfig.
     *
     * @param string $queryTable The table to query
     * @param array $wizardConfig The configuration for the wizard as configured in the data structure
     * @param array $TSconfig The TSconfig array of the current page
     * @param string $table The table where the wizard is used
     * @param string $field The field where the wizard is used
     * @return array
     */
    protected function getConfigurationForTable($queryTable, array $wizardConfig, array $TSconfig, $table, $field)
    {
        $config = (array)$wizardConfig['default'];

        if (is_array($wizardConfig[$queryTable])) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $wizardConfig[$queryTable]);
        }
        $globalSuggestTsConfig = $TSconfig['TCEFORM.']['suggest.'];
        $currentFieldSuggestTsConfig = $TSconfig['TCEFORM.'][$table . '.'][$field . '.']['suggest.'];

        // merge the configurations of different "levels" to get the working configuration for this table and
        // field (i.e., go from the most general to the most special configuration)
        if (is_array($globalSuggestTsConfig['default.'])) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $globalSuggestTsConfig['default.']);
        }

        if (is_array($globalSuggestTsConfig[$queryTable . '.'])) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $globalSuggestTsConfig[$queryTable . '.']);
        }

        // use $table instead of $queryTable here because we overlay a config
        // for the input-field here, not for the queried table
        if (is_array($currentFieldSuggestTsConfig['default.'])) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $currentFieldSuggestTsConfig['default.']);
        }

        if (is_array($currentFieldSuggestTsConfig[$queryTable . '.'])) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $currentFieldSuggestTsConfig[$queryTable . '.']);
        }

        return $config;
    }

    /**
     * Checks the given field configuration for the tables that should be used for querying and returns them as an
     * array.
     *
     * @param array $fieldConfig
     * @return array
     */
    protected function getTablesToQueryFromFieldConfiguration(array $fieldConfig)
    {
        $queryTables = [];

        if (isset($fieldConfig['allowed'])) {
            if ($fieldConfig['allowed'] !== '*') {
                // list of allowed tables
                $queryTables = GeneralUtility::trimExplode(',', $fieldConfig['allowed']);
            } else {
                // all tables are allowed, if the user can access them
                foreach ($GLOBALS['TCA'] as $tableName => $tableConfig) {
                    if (!$this->isTableHidden($tableConfig) && $this->currentBackendUserMayAccessTable($tableConfig)) {
                        $queryTables[] = $tableName;
                    }
                }
                unset($tableName, $tableConfig);
            }
        } elseif (isset($fieldConfig['foreign_table'])) {
            // use the foreign table
            $queryTables = [$fieldConfig['foreign_table']];
        }

        return $queryTables;
    }

    /**
     * Returns the SQL WHERE clause to use for querying records. This is currently only relevant if a foreign_table
     * is configured and should be used; it could e.g. be used to limit to a certain subset of records from the
     * foreign table
     *
     * @param array $fieldConfig
     * @return string
     */
    protected function getWhereClause(array $fieldConfig)
    {
        if (!isset($fieldConfig['foreign_table'])) {
            return '';
        }

        // strip ORDER BY clause
        return trim(preg_replace('/ORDER[[:space:]]+BY.*/i', '', $fieldConfig['foreign_table_where']));
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
