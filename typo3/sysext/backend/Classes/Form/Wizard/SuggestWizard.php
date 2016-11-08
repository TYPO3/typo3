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
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Wizard for rendering an AJAX selector for records
 */
class SuggestWizard
{

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Construct
     *
     * @param StandaloneView $view
     */
    public function __construct(StandaloneView $view = null)
    {
        $this->view = $view ?: $this->getFluidTemplateObject('SuggestWizard.html');
    }

    /**
     * Renders an ajax-enabled text field. Also adds required JS
     *
     * @param string $fieldName The field name in the form
     * @param string $table The table we render this selector for
     * @param string $field The field we render this selector for
     * @param array $row The row which is currently edited
     * @param array $config The TSconfig of the field
     * @param array $flexFormConfig If field is within flex form, this is the TCA config of the flex field
     * @throws \RuntimeException for incomplete incoming arguments
     * @return string The HTML code for the selector
     */
    public function renderSuggestSelector($fieldName, $table, $field, array $row, array $config, array $flexFormConfig = [])
    {
        $dataStructureIdentifier = '';
        if (!empty($flexFormConfig) && $flexFormConfig['config']['type'] === 'flex') {
            $fieldPattern = 'data[' . $table . '][' . $row['uid'] . '][';
            $flexformField = str_replace($fieldPattern, '', $fieldName);
            $flexformField = substr($flexformField, 0, -1);
            $field = str_replace([']['], '|', $flexformField);
            if (!isset($flexFormConfig['config']['dataStructureIdentifier'])) {
                throw new \RuntimeException(
                    'A data structure identifier must be set in [\'config\'] part of a flex form.'
                    . ' This is usually added by TcaFlexPrepare data processor',
                    1478604742
                );
            }
            $dataStructureIdentifier = $flexFormConfig['config']['dataStructureIdentifier'];
        }

        // Get minimumCharacters from TCA
        $minChars = 0;
        if (isset($config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters'])) {
            $minChars = (int)$config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters'];
        }
        // Overwrite it with minimumCharacters from TSConfig if given
        if (isset($config['fieldTSConfig']['suggest.']['default.']['minimumCharacters'])) {
            $minChars = (int)$config['fieldTSConfig']['suggest.']['default.']['minimumCharacters'];
        }
        $minChars = $minChars > 0 ? $minChars : 2;

        // fetch the TCA field type to hand it over to the JS class
        $type = '';
        if (isset($config['fieldConf']['config']['type'])) {
            $type = $config['fieldConf']['config']['type'];
        }

        // Sign those parameters that come back in an ajax request to configure the search in searchAction()
        $hmac = GeneralUtility::hmac(
            (string)$table . (string)$field . (string)$row['uid'] . (string)$row['pid'] . (string)$dataStructureIdentifier,
            'formEngineSuggest'
        );

        $this->view->assignMultiple([
                'placeholder' => 'LLL:EXT:lang/locallang_core.xlf:labels.findRecord',
                'fieldname' => $fieldName,
                'table' => $table,
                'field' => $field,
                'uid' => $row['uid'],
                'pid' => (int)$row['pid'],
                'dataStructureIdentifier' => $dataStructureIdentifier,
                'fieldtype' => $type,
                'minchars' => (int)$minChars,
                'hmac' => $hmac,
            ]
        );

        return $this->view->render();
    }

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

        if (!isset($parsedBody['value'])
            || !isset($parsedBody['table'])
            || !isset($parsedBody['field'])
            || !isset($parsedBody['uid'])
            || !isset($parsedBody['dataStructureIdentifier'])
            || !isset($parsedBody['hmac'])
        ) {
            throw new \RuntimeException(
                'Missing at least one of the required arguments "value", "table", "field", "uid"'
                . ', "dataStructureIdentifier" or "hmac"',
                1478607036
            );
        }

        $search = $parsedBody['value'];
        $table = $parsedBody['table'];
        $field = $parsedBody['field'];
        $uid = $parsedBody['uid'];
        $pid = (int)$parsedBody['pid'];

        // flex form section container identifiers are created on js side dynamically "onClick". Those are
        // not within the generated hmac ... the js side adds "idx{dateInMilliseconds}-", so this is removed here again.
        // example outgoing in renderSuggestSelector():
        // flex_1|data|sSuggestCheckCombination|lDEF|settings.subelements|el|ID-356586b0d3-form|item|el|content|vDEF
        // incoming here:
        // flex_1|data|sSuggestCheckCombination|lDEF|settings.subelements|el|ID-356586b0d3-idx1478611729574-form|item|el|content|vDEF
        // Note: For existing containers, these parts are numeric, so "ID-356586b0d3-idx1478611729574-form" becomes 1 or 2, etc.
        // @todo: This could be kicked is the flex form section containers are moved to an ajax call on creation
        $fieldForHmac = preg_replace('/idx\d{13}-/', '', $field);

        $dataStructureIdentifierString = '';
        if (!empty($parsedBody['dataStructureIdentifier'])) {
            $dataStructureIdentifierString = json_encode($parsedBody['dataStructureIdentifier']);
        }

        $incomingHmac = $parsedBody['hmac'];
        $calculatedHmac = GeneralUtility::hmac(
            $table . $fieldForHmac . $uid . $pid . $dataStructureIdentifierString,
            'formEngineSuggest'
        );
        if ($incomingHmac !== $calculatedHmac) {
            throw new \RuntimeException(
                'Incoming and calculated hmac do not match',
                1478608245
            );
        }

        // If the $uid is numeric (existing page) and a suggest wizard in pages is handled, the effective
        // pid is the uid of that page - important for page ts config configuration.
        if (MathUtility::canBeInterpretedAsInteger($uid) && $table === 'pages') {
            $pid = $uid;
        }
        $TSconfig = BackendUtility::getPagesTSconfig($pid);

        // Determine TCA config of field
        if (empty($dataStructureIdentifierString)) {
            // Normal columns field
            $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        } else {
            // A flex flex form field
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $dataStructureArray = $flexFormTools->parseDataStructureByIdentifier($dataStructureIdentifierString);
            $parts = explode('|', $field);
            $fieldConfig = $this->getFieldConfiguration($parts, $dataStructureArray);
            // Flexform field name levels are separated with | instead of encapsulation in [];
            // reverse this here to be compatible with regular field names.
            $field = str_replace('|', '][', $field);
        }

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
                    '###CURRENT_PID###' => (int)$pid
                ];
                if (isset($TSconfig['TCEFORM.'][$table . '.'][$field . '.'])) {
                    $fieldTSconfig = $TSconfig['TCEFORM.'][$table . '.'][$field . '.'];
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
     * Get configuration for given field by traversing the flexform path to field
     * given in $parts
     *
     * @param array $parts
     * @param array $flexformDSArray
     * @return array
     */
    protected function getFieldConfiguration(array $parts, array $flexformDSArray)
    {
        $relevantParts = [];
        foreach ($parts as $part) {
            if ($this->isRelevantFlexField($part)) {
                $relevantParts[] = $part;
            }
        }
        // throw away db field name for flexform field
        array_shift($relevantParts);

        $flexformElement = array_pop($relevantParts);
        $sheetName = array_shift($relevantParts);
        $flexSubDataStructure = $flexformDSArray['sheets'][$sheetName];
        foreach ($relevantParts as $relevantPart) {
            $flexSubDataStructure = $this->getSubConfigurationForSections($flexSubDataStructure, $relevantPart);
        }
        $fieldConfig = $this->getNestedDsFieldConfig($flexSubDataStructure, $flexformElement);
        return $fieldConfig;
    }

    /**
     * Recursively get sub sections in data structure by name
     *
     * @param array $dataStructure
     * @param string $fieldName
     * @return array
     */
    protected function getSubConfigurationForSections(array $dataStructure, $fieldName)
    {
        $fieldConfig = [];
        $elements = $dataStructure['ROOT']['el'] ? $dataStructure['ROOT']['el'] : $dataStructure['el'];
        if (is_array($elements)) {
            foreach ($elements as $k => $ds) {
                if ($k === $fieldName) {
                    $fieldConfig = $ds;
                    break;
                } elseif (isset($ds['el'][$fieldName])) {
                    $fieldConfig = $ds['el'][$fieldName];
                    break;
                } else {
                    $fieldConfig = $this->getSubConfigurationForSections($ds, $fieldName);
                }
            }
        }
        return $fieldConfig;
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
     * Checks whether the field is an actual identifier or just "array filling"
     *
     * @param string $fieldName
     * @return bool
     */
    protected function isRelevantFlexField($fieldName)
    {
        return !(
            strpos($fieldName, 'ID-') === 0 ||
            $fieldName === 'lDEF' ||
            $fieldName === 'vDEF' ||
            $fieldName === 'data' ||
            $fieldName === 'el'
        );
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

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename = null):StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);

        if ($filename === null) {
            $filename = 'SuggestWizard.html';
        }

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/Wizards/' . $filename));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
