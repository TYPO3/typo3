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

namespace TYPO3\CMS\Backend\Controller\Wizard;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Form\Wizard\SuggestWizardDefaultReceiver;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Schema\Capability\RootLevelCapability;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Receives ajax request from FormEngine suggest wizard and creates suggest answer as json result
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class SuggestWizardController
{
    public function __construct(
        private readonly FlexFormTools $flexFormTools,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {}

    /**
     * Ajax handler for the "suggest" feature in FormEngine.
     *
     * @throws \RuntimeException for incomplete or invalid arguments
     */
    public function searchAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        $search = $parsedBody['value'] ?? null;
        $tableName = $parsedBody['tableName'] ?? null;
        $fieldName = $parsedBody['fieldName'] ?? null;
        $uid = $parsedBody['uid'] ?? null;
        $pid = isset($parsedBody['pid']) ? (int)$parsedBody['pid'] : 0;
        $dataStructureIdentifier = $parsedBody['dataStructureIdentifier'] ?? '';
        $flexFormSheetName = $parsedBody['flexFormSheetName'] ?? null;
        $flexFormFieldName = $parsedBody['flexFormFieldName'] ?? null;
        $flexFormContainerName = $parsedBody['flexFormContainerName'] ?? null;
        $flexFormContainerFieldName = $parsedBody['flexFormContainerFieldName'] ?? null;
        $recordType = (string)($parsedBody['recordTypeValue'] ?? '');

        // Determine TCA config of field
        if (empty($dataStructureIdentifier)) {
            // Normal columns field
            $schema = $this->tcaSchemaFactory->get($tableName);
            $fieldInformation = $schema->getField($fieldName);
            $fieldConfig = $fieldInformation->getConfiguration();
            $fieldNameInPageTsConfig = $fieldName;

            // With possible columnsOverrides
            // @todo Validate if we can move this fallback recordType determination, should be do-able in v13?!
            if ($recordType === '') {
                $recordType = BackendUtility::getTCAtypeValue(
                    $tableName,
                    BackendUtility::getRecord($tableName, $uid) ?? []
                );
            }
            if ($recordType !== '' && $schema->hasSubSchema($recordType)) {
                $fieldConfig = $schema->getSubSchema($recordType)->getField($fieldName)->getConfiguration();
            }
        } else {
            // A flex-form field
            $dataStructure = $this->flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifier);
            if (empty($flexFormContainerFieldName)) {
                // @todo: See if a path in pageTsConfig like "TCEForm.tableName.theContainerFieldName =" is useful and works with other pageTs, too.
                $fieldNameInPageTsConfig = $flexFormFieldName;
                if (!isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]['config'])
                ) {
                    throw new \RuntimeException(
                        'Specified path ' . $flexFormFieldName . ' not found in flex form data structure',
                        1480609491
                    );
                }
                $fieldConfig = $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]['config'];
            } else {
                $fieldNameInPageTsConfig = $flexFormContainerFieldName;
                if (!isset($dataStructure['sheets'][$flexFormSheetName]['ROOT']
                        ['el'][$flexFormFieldName]
                        ['el'][$flexFormContainerName]
                        ['el'][$flexFormContainerFieldName]['config'])
                ) {
                    throw new \RuntimeException(
                        'Specified path ' . $flexFormContainerName . ' not found in flex form section container data structure',
                        1480611208
                    );
                }
                $fieldConfig = $dataStructure['sheets'][$flexFormSheetName]['ROOT']
                    ['el'][$flexFormFieldName]
                    ['el'][$flexFormContainerName]
                    ['el'][$flexFormContainerFieldName]['config'];
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
            if (!$this->tcaSchemaFactory->has($queryTable)) {
                continue;
            }

            $config = $this->getConfigurationForTable($queryTable, $wizardConfig, $pageTsConfig, $tableName, $fieldNameInPageTsConfig);

            // process addWhere
            if (!isset($config['addWhere']) && $whereClause) {
                $config['addWhere'] = $whereClause;
            }
            if (isset($config['addWhere'])) {
                $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
                $replacement = [
                    '###THIS_UID###' => (int)$uid,
                    '###CURRENT_PID###' => (int)$pid,
                ];
                if (isset($pageTsConfig['TCEFORM.'][$tableName . '.'][$fieldNameInPageTsConfig . '.'])) {
                    $fieldTSconfig = $pageTsConfig['TCEFORM.'][$tableName . '.'][$fieldNameInPageTsConfig . '.'];
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_ID'])) {
                        $replacement['###PAGE_TSCONFIG_ID###'] = (int)$fieldTSconfig['PAGE_TSCONFIG_ID'];
                    }
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_IDLIST'])) {
                        $replacement['###PAGE_TSCONFIG_IDLIST###'] = implode(',', GeneralUtility::intExplode(',', (string)$fieldTSconfig['PAGE_TSCONFIG_IDLIST']));
                    }
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_STR'])) {
                        $connection = $connectionPool->getConnectionForTable($fieldConfig['foreign_table']);
                        // nasty hack, but it's currently not possible to just quote anything "inside" the value but not escaping
                        // the whole field as it is not known where it is used in the WHERE clause
                        $replacement['###PAGE_TSCONFIG_STR###'] = trim($connection->quote($fieldTSconfig['PAGE_TSCONFIG_STR']), '\'');
                    }
                }
                $config['addWhere'] = QueryHelper::quoteDatabaseIdentifiers($connectionPool->getConnectionForTable($queryTable), strtr(' ' . $config['addWhere'], $replacement));
            }

            // instantiate the class that should fetch the records for this $queryTable
            $receiverClassName = $config['receiverClass'] ?? '';
            if (!class_exists($receiverClassName)) {
                $receiverClassName = SuggestWizardDefaultReceiver::class;
            }
            $receiverObj = GeneralUtility::makeInstance($receiverClassName, $queryTable, $config);
            $params = [
                'value' => $search,
                'uid' => $uid,
            ];
            $rows = $receiverObj->queryTable($params);
            if (empty($rows)) {
                continue;
            }
            $resultRows = $rows + $resultRows;
            unset($rows);
        }

        // Limit the number of items in the result list
        $maxItems = $config['maxItemsInResultList'] ?? 10;
        $maxItems = min(count($resultRows), $maxItems);

        array_splice($resultRows, $maxItems);
        return new JsonResponse(array_values($resultRows));
    }

    /**
     * Checks if the current backend user is allowed to access the given table, based on the schema capabilities.
     */
    protected function currentBackendUserMayAccessTable(TcaSchema $schema): bool
    {
        if ($this->getBackendUser()->isAdmin()) {
            return true;
        }

        // If the user is no admin, they may not access admin-only tables
        if ($schema->hasCapability(TcaSchemaCapability::AccessAdminOnly)) {
            return false;
        }

        /** @var RootLevelCapability $rootLevelCapability */
        $rootLevelCapability = $schema->getCapability(TcaSchemaCapability::RestrictionRootLevel);

        // allow access to root level pages if security restrictions should be bypassed
        return $rootLevelCapability->canAccessRecordsOnRootLevel();
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
     */
    protected function getConfigurationForTable(string $queryTable, array $wizardConfig, array $TSconfig, string $table, string $field): array
    {
        $config = (array)($wizardConfig['default'] ?? []);

        if (is_array($wizardConfig[$queryTable] ?? null)) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $wizardConfig[$queryTable]);
        }
        $globalSuggestTsConfig = $TSconfig['TCEFORM.']['suggest.'] ?? [];
        $currentFieldSuggestTsConfig = $TSconfig['TCEFORM.'][$table . '.'][$field . '.']['suggest.'] ?? [];

        // merge the configurations of different "levels" to get the working configuration for this table and
        // field (i.e., go from the most general to the most special configuration)
        if (is_array($globalSuggestTsConfig['default.'] ?? null)) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $globalSuggestTsConfig['default.']);
        }

        if (is_array($globalSuggestTsConfig[$queryTable . '.'] ?? null)) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $globalSuggestTsConfig[$queryTable . '.']);
        }

        // use $table instead of $queryTable here because we overlay a config
        // for the input-field here, not for the queried table
        if (is_array($currentFieldSuggestTsConfig['default.'] ?? null)) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $currentFieldSuggestTsConfig['default.']);
        }

        if (is_array($currentFieldSuggestTsConfig[$queryTable . '.'] ?? null)) {
            ArrayUtility::mergeRecursiveWithOverrule($config, $currentFieldSuggestTsConfig[$queryTable . '.']);
        }

        return $config;
    }

    /**
     * Checks the given field configuration for the tables that should be used for querying and returns them as an
     * array.
     */
    protected function getTablesToQueryFromFieldConfiguration(array $fieldConfig): array
    {
        $queryTables = [];

        if (isset($fieldConfig['allowed'])) {
            if ($fieldConfig['allowed'] !== '*') {
                // list of allowed tables
                $queryTables = GeneralUtility::trimExplode(',', $fieldConfig['allowed']);
            } else {
                // all tables are allowed, if the user can access them
                /** @var TcaSchema $schema */
                foreach ($this->tcaSchemaFactory->all() as $tableName => $schema) {
                    if ($schema->getRawConfiguration()['hideTable'] ?? false) {
                        continue;
                    }
                    if ($this->currentBackendUserMayAccessTable($schema)) {
                        $queryTables[] = $tableName;
                    }
                }
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
     */
    protected function getWhereClause(array $fieldConfig): string
    {
        if (!isset($fieldConfig['foreign_table'], $fieldConfig['foreign_table_where'])) {
            return '';
        }

        // strip ORDER BY clause
        return trim(preg_replace('/ORDER[[:space:]]+BY.*/i', '', $fieldConfig['foreign_table_where']));
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
