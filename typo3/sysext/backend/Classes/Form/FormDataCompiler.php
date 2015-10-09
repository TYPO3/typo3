<?php
namespace TYPO3\CMS\Backend\Form;

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

/**
 * Create and return a defined array of data ready to be used by the
 * container / element render part of FormEngine
 */
class FormDataCompiler
{
    /**
     * Data group that provides data
     *
     * @var FormDataGroupInterface
     */
    protected $formDataGroup;

    /**
     * Get form data group injected
     *
     * @param FormDataGroupInterface $formDataGroup
     */
    public function __construct(FormDataGroupInterface $formDataGroup)
    {
        $this->formDataGroup = $formDataGroup;
    }

    /**
     * Main entry method maps given data input array and sanitizes some
     * crucial input parameters and calls compile on FormDataGroupInterface.
     *
     * @param array $initialData Initial set of data to map into result array
     * @return array Result with data
     */
    public function compile(array $initialData)
    {
        $result = $this->initializeResultArray();

        // There must be only keys that actually exist in result data.
        $keysNotInResult = array_diff(array_keys($initialData), array_keys($result));
        if (!empty($keysNotInResult)) {
            throw new \InvalidArgumentException(
                'Array keys ' . implode(',', $keysNotInResult) . ' do not exist in result array and can not be set',
                1440601540
            );
        }

        foreach ($initialData as $dataKey => $dataValue) {
            if ($dataKey === 'command') {
                // Sanitize $command
                if ($dataValue !== 'edit' && $dataValue !== 'new') {
                    throw new \InvalidArgumentException('Command must be either "edit" or "new"', 1437653136);
                }
            }
            if ($dataKey === 'tableName') {
                // Sanitize $tableName
                if (empty($dataValue)) {
                    throw new \InvalidArgumentException('No $tableName given', 1437654409);
                }
            }
            if ($dataKey === 'vanillaUid') {
                if (!is_int($dataValue)) {
                    throw new \InvalidArgumentException('$vanillaUid is not an integer', 1437654247);
                }
                if (isset($initialData['command']) && $initialData['command'] === 'edit' && $dataValue < 0) {
                    throw new \InvalidArgumentException('Negative $vanillaUid is not supported with $command="edit', 1437654332);
                }
            }
            $result[$dataKey] = $dataValue;
        }

        // Call the data group provider but take care it does not add or remove result keys
        // This is basically a safety measure against data providers colliding with our array "contract"
        $resultKeysBeforeFormDataGroup = array_keys($result);

        $result = $this->formDataGroup->compile($result);

        $resultKeysAfterFormDataGroup = array_keys($result);

        if ($resultKeysAfterFormDataGroup !== $resultKeysBeforeFormDataGroup) {
            throw new \UnexpectedValueException(
                'Data group provider must not change result key list',
                1438079402
            );
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function initializeResultArray()
    {
        return array(
            // Either "edit" or "new"
            'command' => '',
            // Table name of the handled row
            'tableName' => '',
            // Forced integer of otherwise not changed uid of the record, meaning of value depends on context (new / edit)
            // * If $command is "edit"
            // ** $vanillaUid is a positive integer > 0 pointing to the record in the table
            // * If $command is "new":
            // ** If $vanillaUid > 0, it is the uid of a page the record should be added at
            // ** If $vanillaUid < 0, it is the uid of a record in the same table after which the new record should be added
            // ** If $vanillaUid = 0, a new record is added on page 0
            'vanillaUid' => 0,
            // Url to return to
            'returnUrl' => null,
            // Title of the handled record.
            'recordTitle' => '',
            // Parent page record is either the full row of the parent page the record is located at or should
            // be added to, or it is NULL, if a record is added or edited below the root page node.
            'parentPageRow' => null,
            // Holds the "neighbor" row if incoming vanillaUid is negative and record creation is relative to a row of the same table.
            'neighborRow' => null,
            // For "new" this is the fully initialized row with defaults
            // The database row. For "edit" fixVersioningPid() was applied already.
            // @todo: rename to valueStructure or handledData or similar
            'databaseRow' => array(),
            // The "effective" page uid we're working on. This is the uid of a page if a page is edited, or the uid
            // of the parent page if a page or other record is added, or 0 if a record is added or edited below root node.
            'effectivePid' => 0,
            // Rootline of page the record that is handled is located at as created by BackendUtility::BEgetRootline()
            'rootline' => array(),
            // For "edit", this is the permission bitmask of the page that is edited, or of the page a record is located at
            // For "new", this is the permission bitmask of the page the record is added to
            // @todo: Remove if not needed on a lower level
            'userPermissionOnPage' => 0,
            // Full user TsConfig
            'userTsConfig' => array(),
            // Full page TSConfig of the page that is edited or of the parent page if a record is added.
            // This includes any defaultPageTSconfig and is merged with user TsConfig page. section
            'pageTsConfig' => array(),
            // Not changed TCA of handled table
            'vanillaTableTca' => array(),
            // Not changed TCA of parent page row if record is edited or added below a page and not root node
            'vanillaParentPageTca' => null,
            // List of available system languages. Array key is the system language uid, value array
            // contains details of the record, with iso code resolved. Key is the sys_language_uid uid.
            'systemLanguageRows' => array(),
            // If the page that is handled has "page_language_overlay" records (page has localizations in
            // different languages), then this array holds those rows.
            'pageLanguageOverlayRows' => array(),
            // If the handled row is a localized row, this entry hold the default language row array
            'defaultLanguageRow' => null,
            // If the handled row is a localized row and a transOrigDiffSourceField is defined, this
            // is the unserialized version of it. The diff source field is basically a shadow version
            // of the default language record at the time when the language overlay record was created.
            // This is used later to compare the default record with this content to show a "diff" if
            // the default language record changed meanwhile.
            'defaultLanguageDiffRow' => null,
            // With userTS options.additionalPreviewLanguages set, field values of additional languages
            // can be shown. This array holds those additional language records, Array key is sys_language_uid.
            'additionalLanguageRows' => array(),
            // The tca record type value of the record. Forced to string, there can be "named" type values.
            'recordTypeValue' => '0',
            // prepared Ts config: Type specific configuration is merged and some additional values are set.
            'pageTsConfigMerged' => array(),
            // TCA of table with processed fields. After processing, this array contains merged and resolved
            // array data, items were resolved, only used types are set, renderTypes are set.
            'processedTca' => array(),
            // List of columns to be processed by data provider. Array value is the column name.
            'columnsToProcess' => array(),
            // If set to TRUE, no wizards are calculated and rendered later
            'disabledWizards' => false,
            // BackendUser->uc['inlineView'] - This array holds status of expand / collapsed inline items
            // @todo: better documentation of nesting behaviour and bug fixing in this area
            'inlineExpandCollapseStateArray' => array(),
            // The "entry" pid for inline records. Nested inline records can potentially hang around on different
            // pid's, but the entry pid is needed for AJAX calls, so that they would know where the action takes place on the page structure.
            'inlineFirstPid' => null,
            // This array of fields will be set as hidden-fields instead of rendered normally!
            // This is used by EditDocumentController to force some field values if set as "overrideVals" in _GP
            'overrideValues' => [],

            // Inline scenario: A localized parent record is handled and localizationMode is set to "select", so inline
            // parents can have localized children. This value is set to TRUE if this array represents a localized child
            // overlay record that has no default language record.
            'inlineIsDanglingLocalization' => false,
            // Inline scenario: A localized parent record is handled and localizationMode is set to "select", so inline
            // parents can have localized childen. This value is set to TRUE if this array represents a default language
            // child record that was not yet localized.
            'inlineIsDefaultLanguage' => false,
            // If set, inline children will be resolved. This is set to FALSE in inline ajax context where new children
            // are created an existing children don't matter much.
            'inlineResolveExistingChildren' => true,
            // @todo - for input placeholder inline to suppress an infinite loop, this *may* become obsolete if
            // @todo compilation of certain fields is possible
            'inlineCompileExistingChildren' => true,

            // @todo: must be handled / further defined
            'elementBaseName' => '',
            'flexFormFieldIdentifierPrefix' => 'ID',
            'tabAndInlineStack' => [],
            'inlineData' => [],
            'inlineStructure' => [],
            'overruleTypesArray' => [],
        );
    }
}
