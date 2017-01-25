<?php
namespace TYPO3\CMS\Backend\Form\Wizard;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Wizard for rendering an AJAX selector for records
 */
class SuggestWizard
{
    /**
     * Renders an ajax-enabled text field. Also adds required JS
     *
     * @param string $fieldname The fieldname in the form
     * @param string $table The table we render this selector for
     * @param string $field The field we render this selector for
     * @param array $row The row which is currently edited
     * @param array $config The TSconfig of the field
     * @return string The HTML code for the selector
     */
    public function renderSuggestSelector($fieldname, $table, $field, array $row, array $config)
    {
        /** @var $iconFactory IconFactory */
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $languageService = $this->getLanguageService();
        $isFlexFormField = $GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] === 'flex';
        if ($isFlexFormField) {
            $fieldPattern = 'data[' . $table . '][' . $row['uid'] . '][';
            $flexformField = str_replace($fieldPattern, '', $fieldname);
            $flexformField = substr($flexformField, 0, -1);
            $field = str_replace([']['], '|', $flexformField);
        }

        // Get minimumCharacters from TCA
        $minChars = 0;
        if (isset($config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters'])) {
            $minChars = (int)$config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters'];
        }
        // Overwrite it with minimumCharacters from TSConfig (TCEFORM) if given
        if (isset($config['fieldTSConfig']['suggest.']['default.']['minimumCharacters'])) {
            $minChars = (int)$config['fieldTSConfig']['suggest.']['default.']['minimumCharacters'];
        }
        $minChars = $minChars > 0 ? $minChars : 2;

        // fetch the TCA field type to hand it over to the JS class
        $type = '';
        if (isset($config['fieldConf']['config']['type'])) {
            $type = $config['fieldConf']['config']['type'];
        }

        $jsRow = '';
        if ($isFlexFormField || !MathUtility::canBeInterpretedAsInteger($row['uid'])) {
            // Ff we have a new record, we hand that row over to JS.
            // This way we can properly retrieve the configuration of our wizard
            // if it is shown in a flexform
            $jsRow = json_encode($row);
        }

        $selector = '
		<div class="autocomplete t3-form-suggest-container">
			<div class="input-group">
				<span class="input-group-addon">' . $iconFactory->getIcon('actions-search', Icon::SIZE_SMALL)->render() . '</span>
				<input type="search" class="t3-form-suggest form-control"
					placeholder="' . $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.findRecord', true) . '"
					data-fieldname="' . htmlspecialchars($fieldname) . '"
					data-table="' . htmlspecialchars($table) . '"
					data-field="' . htmlspecialchars($field) . '"
					data-uid="' . htmlspecialchars($row['uid']) . '"
					data-pid="' . (int)$row['pid'] . '"
					data-fieldtype="' . htmlspecialchars($type) . '"
					data-minchars="' . (int)$minChars . '"
					data-recorddata="' . htmlspecialchars($jsRow) . '"
				/>
			</div>
		</div>';

        return $selector;
    }

    /**
     * Search a data structure array recursively -- including within nested
     * (repeating) elements -- for a particular field config.
     *
     * @param array $dataStructure The data structure
     * @param string $fieldName The field name
     * @return array
     */
    protected function getNestedDsFieldConfig(array $dataStructure, $fieldName)
    {
        $fieldConfig = [];
        $elements = $dataStructure['ROOT']['el'] ? $dataStructure['ROOT']['el'] : $dataStructure['el'];
        if (is_array($elements)) {
            foreach ($elements as $k => $ds) {
                if ($k === $fieldName) {
                    $fieldConfig = $ds['TCEforms']['config'];
                    break;
                } elseif (isset($ds['el'][$fieldName]['TCEforms']['config'])) {
                    $fieldConfig = $ds['el'][$fieldName]['TCEforms']['config'];
                    break;
                } else {
                    $fieldConfig = $this->getNestedDsFieldConfig($ds, $fieldName);
                }
            }
        }
        return $fieldConfig;
    }

    /**
     * Ajax handler for the "suggest" feature in FormEngine.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function searchAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Get parameters from $_GET/$_POST
        $search = isset($parsedBody['value']) ? $parsedBody['value'] : $queryParams['value'];
        $table = isset($parsedBody['table']) ? $parsedBody['table'] : $queryParams['table'];
        $field = isset($parsedBody['field']) ? $parsedBody['field'] : $queryParams['field'];
        $uid = isset($parsedBody['uid']) ? $parsedBody['uid'] : $queryParams['uid'];
        $pageId = (int)(isset($parsedBody['pid']) ? $parsedBody['pid'] : $queryParams['pid']);
        $newRecordRow = isset($parsedBody['newRecordRow']) ? $parsedBody['newRecordRow'] : $queryParams['newRecordRow'];
        // If the $uid is numeric, we have an already existing element, so get the
        // TSconfig of the page itself or the element container (for non-page elements)
        // otherwise it's a new element, so use given id of parent page (i.e., don't modify it here)
        if (is_numeric($uid)) {
            $row = BackendUtility::getRecord($table, $uid);
            if ($table === 'pages') {
                $pageId = $uid;
            } else {
                $pageId = $row['pid'];
            }
        } else {
            $row = json_decode($newRecordRow, true);
        }
        $TSconfig = BackendUtility::getPagesTSconfig($pageId);
        $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        $this->overrideFieldNameAndConfigurationForFlexform($table, $field, $row, $fieldConfig);

        $wizardConfig = $fieldConfig['wizards']['suggest'];

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

            $config = $this->getConfigurationForTable($queryTable, $wizardConfig, $TSconfig, $table, $field);

            // process addWhere
            if (!isset($config['addWhere']) && $whereClause) {
                $config['addWhere'] = $whereClause;
            }
            if (isset($config['addWhere'])) {
                $replacement = [
                    '###THIS_UID###' => (int)$uid,
                    '###CURRENT_PID###' => (int)$pageId
                ];
                if (isset($TSconfig['TCEFORM.'][$table . '.'][$field . '.'])) {
                    $fieldTSconfig = $TSconfig['TCEFORM.'][$table . '.'][$field . '.'];
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_ID'])) {
                        $replacement['###PAGE_TSCONFIG_ID###'] = (int)$fieldTSconfig['PAGE_TSCONFIG_ID'];
                    }
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_IDLIST'])) {
                        $replacement['###PAGE_TSCONFIG_IDLIST###'] = $GLOBALS['TYPO3_DB']->cleanIntList($fieldTSconfig['PAGE_TSCONFIG_IDLIST']);
                    }
                    if (isset($fieldTSconfig['PAGE_TSCONFIG_STR'])) {
                        $replacement['###PAGE_TSCONFIG_STR###'] = $GLOBALS['TYPO3_DB']->quoteStr($fieldTSconfig['PAGE_TSCONFIG_STR'], $fieldConfig['foreign_table']);
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

        $listItems = $this->createListItemsFromResultRow($resultRows, $maxItems);

        $response->getBody()->write(json_encode($listItems));
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
        if ($GLOBALS['BE_USER']->isAdmin()) {
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
     * Checks if the query comes from a Flexform element and if yes, resolves the field configuration from the Flexform
     * data structure.
     *
     * @param string $table
     * @param string &$field The field identifier, either a simple table field or a Flexform field path separated with |
     * @param array $row The row we're dealing with; optional (only required for Flexform records)
     * @param array|NULL &$fieldConfig
     */
    protected function overrideFieldNameAndConfigurationForFlexform($table, &$field, array $row, &$fieldConfig)
    {
        // check if field is a flexform reference
        if (strpos($field, '|') === false) {
            $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        } else {
            $parts = explode('|', $field);

            if ($GLOBALS['TCA'][$table]['columns'][$parts[0]]['config']['type'] !== 'flex') {
                return;
            }

            $flexfieldTCAConfig = $GLOBALS['TCA'][$table]['columns'][$parts[0]]['config'];
            // @todo: should be done via data preparation, resolveAllSheetsInDS() can be deprecated then
            if (substr($row['uid'], 0, 3) === 'NEW') {
                // We have to cleanup record information as they are coming from FormEngines DataProvider
                $pointerFields = GeneralUtility::trimExplode(',', $flexfieldTCAConfig['ds_pointerField']);
                foreach ($pointerFields as $pointerField) {
                    if (is_array($row[$pointerField])) {
                        $row[$pointerField] = $row[$pointerField][0];
                    }
                }
            }
            $flexformDSArray = BackendUtility::getFlexFormDS($flexfieldTCAConfig, $row, $table, $parts[0]);
            $flexformDSArray = GeneralUtility::resolveAllSheetsInDS($flexformDSArray);
            $flexformElement = $parts[count($parts) - 2];
            foreach ($flexformDSArray as $sheet) {
                foreach ($sheet as $_ => $dataStructure) {
                    $fieldConfig = $this->getNestedDsFieldConfig($dataStructure, $flexformElement);
                    if (!empty($fieldConfig)) {
                        break 2;
                    }
                }
            }
            // Flexform field name levels are separated with | instead of encapsulation in [];
            // reverse this here to be compatible with regular field names.
            $field = str_replace('|', '][', $field);
        }
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
     * Creates a list of <li> elements from a list of results returned by the receiver.
     *
     * @param array $resultRows
     * @param int $maxItems
     * @param string $rowIdSuffix
     * @return array
     */
    protected function createListItemsFromResultRow(array $resultRows, $maxItems)
    {
        if (empty($resultRows)) {
            return [];
        }
        $listItems = [];

        // traverse all found records and sort them
        $rowsSort = [];
        foreach ($resultRows as $key => $row) {
            $rowsSort[$key] = $row['text'];
        }
        asort($rowsSort);
        $rowsSort = array_keys($rowsSort);

        // put together the selector entries
        for ($i = 0; $i < $maxItems; ++$i) {
            $listItems[] = $resultRows[$rowsSort[$i]];
        }
        return $listItems;
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
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
