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
     * List of top level array elements to be unset from
     * result array before final result is returned.
     *
     * @var array
     */
    protected $removeKeysFromFinalResultArray = [
    ];

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
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
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

        if (!is_array($result)) {
            throw new \UnexpectedValueException(
                'Data group provider must return array',
                1446664764
            );
        }

        $resultKeysAfterFormDataGroup = array_keys($result);

        if ($resultKeysAfterFormDataGroup !== $resultKeysBeforeFormDataGroup) {
            throw new \UnexpectedValueException(
                'Data group provider must not change result key list',
                1438079402
            );
        }

        // Remove some data elements form result that are data provider internal and should
        // not be exposed to calling object.
        foreach ($this->removeKeysFromFinalResultArray as $key) {
            unset($result[$key]);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function initializeResultArray()
    {
        return [
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
            'databaseRow' => [],
            // The "effective" page uid we're working on. This is the uid of a page if a page is edited, or the uid
            // of the parent page if a page or other record is added, or 0 if a record is added or edited below root node.
            'effectivePid' => 0,
            // Rootline of page the record that is handled is located at as created by BackendUtility::BEgetRootline()
            'rootline' => [],
            // For "edit", this is the permission bitmask of the page that is edited, or of the page a record is located at
            // For "new", this is the permission bitmask of the page the record is added to
            // @todo: Remove if not needed on a lower level
            'userPermissionOnPage' => 0,
            // Full user TsConfig
            'userTsConfig' => [],
            // Full page TSConfig of the page that is edited or of the parent page if a record is added.
            // This includes any defaultPageTSconfig and is merged with user TsConfig page. section. After type
            // of handled record was determined, record type specific settings [TCEFORM.][tableName.][field.][types.][type.]
            // are merged into [TCEFORM.][tableName.][field.]. Array keys still contain the concatenation dots.
            'pageTsConfig' => [],
            // Not changed TCA of parent page row if record is edited or added below a page and not root node
            'vanillaParentPageTca' => null,
            // List of available system languages. Array key is the system language uid, value array
            // contains details of the record, with iso code resolved. Key is the sys_language_uid uid.
            'systemLanguageRows' => [],
            // If the page that is handled has "page_language_overlay" records (page has localizations in
            // different languages), then this array holds those rows.
            'pageLanguageOverlayRows' => [],
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
            'additionalLanguageRows' => [],
            // The tca record type value of the record. Forced to string, there can be "named" type values.
            'recordTypeValue' => '0',
            // TCA of table with processed fields. After processing, this array contains merged and resolved
            // array data, items were resolved, only used types are set, renderTypes are set.
            'processedTca' => [],
            // List of columns to be processed by data provider. Array value is the column name.
            'columnsToProcess' => [],
            // If set to TRUE, no wizards are calculated and rendered later
            'disabledWizards' => false,

            // Flex form field data handling is done in a separated FormDataCompiler instance. The full databaseRow
            // of the record this flex form is embedded in is transferred in case features like single fields
            // itemsProcFunc need to have this data at hand to do their job.
            'flexParentDatabaseRow' => [],

            // BackendUser->uc['inlineView'] - This array holds status of expand / collapsed inline items
            // This array is "flat", an inline structure with parent uid 1 having firstChild uid 2 having secondChild uid 3
            // firstChild and secondChild are not nested. If an uid is set it means "record is expanded", example:
            // 'parent' => [
            //     1 => [
            //         'firstChild' => [ 2 ], // record 2 of firstChild table is open in inline context to parent 1
            //         'secondChild' => [ 3 ], // record 3 of secondChild table is open in inline context to parent 1
            //     ],
            // ]
            'inlineExpandCollapseStateArray' => [],
            // The "entry" pid for inline records. Nested inline records can potentially hang around on different
            // pid's, but the entry pid is needed for AJAX calls, so that they would know where the action takes
            // place on the page structure.
            'inlineFirstPid' => null,
            // The "config" section of an inline parent, prepared and sanitized by TcaInlineConfiguration provider
            'inlineParentConfig' => [],
            // Flag that is enabled if a records is child of an inline parent
            'isInlineChild' => false,
            // Flag if an inline child is expanded so that additional fields need to be rendered
            'isInlineChildExpanded' => false,
            // Flag if the inline is in an ajax context that wants to expand the element
            'isInlineAjaxOpeningContext' => false,
            // Uid of the direct parent of the inline element. Handled as string since it may be a "NEW123" string
            'inlineParentUid' => '',
            // Table name of the direct parent of the inline element
            'inlineParentTableName' => '',
            // Field name of the direct parent of the inline element
            'inlineParentFieldName' => '',
            // Uid of the top most parent element. Handled as string since it may be a "NEW123" string
            'inlineTopMostParentUid' => '',
            // Table name of the top most parent element
            'inlineTopMostParentTableName' => '',
            // Field name of the top most parent element
            'inlineTopMostParentFieldName' => '',

            // If is on symetric side of an inline child parent reference.
            // symmetric side can be achieved in case of an mm relation to the same table. If record A has a relation
            // to record B, the symmetric side is set in case that record B gets edited.
            // Record A (table1) <=> mm <=> Record B (table1)
            'isOnSymmetricSide' => false,

            // Uid of a "child-child" if a new record of an intermediate table is compiled to an existing child. This
            // happens if foreign_selector in parent inline config is set. It will be used by default database row
            // data providers to set this as value for the foreign_selector field on the intermediate table. One use
            // case is FAL, where for instance a tt_content parent adds relation to an existing sys_file record and
            // should set the uid of the existing sys_file record as uid_local - the foreign_selector of this inline
            // configuration - of the new intermediate sys_file_reference record. Data provider that are called later
            // will then use this relation to resolve for instance input placeholder relation values.
            'inlineChildChildUid' => null,
            // Inline scenario: A localized parent record is handled and localizationMode is set to "select", so inline
            // parents can have localized childen. This value is set to TRUE if this array represents a default language
            // child record that was not yet localized.
            'isInlineDefaultLanguageRecordInLocalizedParentContext' => false,
            // If set, inline children will be resolved. This is set to FALSE in inline ajax context where new children
            // are created and existing children don't matter much.
            'inlineResolveExistingChildren' => true,
            // @todo - for input placeholder inline to suppress an infinite loop, this *may* become obsolete if
            // @todo compilation of certain fields is possible
            'inlineCompileExistingChildren' => true,

            // @todo: keys below must be handled / further defined
            'elementBaseName' => '',
            'flexFormFieldIdentifierPrefix' => 'ID',
            'tabAndInlineStack' => [],
            'inlineData' => [],
            'inlineStructure' => [],
            // This array of fields will be set as hidden-fields instead of rendered normally!
            // This is used by EditDocumentController to force some field values if set as "overrideVals" in _GP
            'overrideValues' => [],

            // A place for non-core, additional, custom data providers to add data. If a data provider needs to add
            // additional data to the data array that doesn't fit elsewhere, it can place it here to use it in the
            // render part again. Data in here should be namespaced in a way that it does not collide with other
            // data providers adding further data here. Using the extension key as array key could be a good idea.
            'customData' => [],
        ];
    }
}
