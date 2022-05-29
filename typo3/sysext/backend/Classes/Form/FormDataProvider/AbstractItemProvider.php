<?php

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

use Doctrine\DBAL\Exception as DBALException;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Contains methods used by Data providers that handle elements
 * with single items like select, radio and some more.
 */
abstract class AbstractItemProvider
{
    /**
     * Resolve "itemProcFunc" of elements.
     *
     * @param array $result Main result array
     * @param string $fieldName Field name to handle item list for
     * @param array $items Existing items array
     * @return array New list of item elements
     */
    protected function resolveItemProcessorFunction(array $result, $fieldName, array $items)
    {
        $table = $result['tableName'];
        $config = $result['processedTca']['columns'][$fieldName]['config'];

        $pageTsProcessorParameters = null;
        if (!empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['itemsProcFunc.'])) {
            $pageTsProcessorParameters = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['itemsProcFunc.'];
        }
        $processorParameters = [
            // Function manipulates $items directly and return nothing
            'items' => &$items,
            'config' => $config,
            'TSconfig' => $pageTsProcessorParameters,
            'table' => $table,
            'row' => $result['databaseRow'],
            'field' => $fieldName,
            // IMPORTANT: Below fields are only available in FormEngine context.
            // They are not used by the DataHandler when processing itemsProcFunc
            // for checking if a submitted value is valid. This means, in case
            // an item is added based on one of these fields, it won't be persisted
            // by the DataHandler. This currently(!) only concerns columns of type "check"
            // and type "radio", see checkValueForCheck() and checkValueForRadio().
            // Therefore, no limitations when using those fields with other types
            // like "select", but this may change in the future.
            'inlineParentUid' => $result['inlineParentUid'],
            'inlineParentTableName' => $result['inlineParentTableName'],
            'inlineParentFieldName' => $result['inlineParentFieldName'],
            'inlineParentConfig' => $result['inlineParentConfig'],
            'inlineTopMostParentUid' => $result['inlineTopMostParentUid'],
            'inlineTopMostParentTableName' => $result['inlineTopMostParentTableName'],
            'inlineTopMostParentFieldName' => $result['inlineTopMostParentFieldName'],
        ];
        if (!empty($result['flexParentDatabaseRow'])) {
            $processorParameters['flexParentDatabaseRow'] = $result['flexParentDatabaseRow'];
        }

        try {
            GeneralUtility::callUserFunction($config['itemsProcFunc'], $processorParameters, $this);
        } catch (\Exception $exception) {
            // The itemsProcFunc method may throw an exception, create a flash message if so
            $languageService = $this->getLanguageService();
            $fieldLabel = $fieldName;
            if (!empty($result['processedTca']['columns'][$fieldName]['label'])) {
                $fieldLabel = $languageService->sL($result['processedTca']['columns'][$fieldName]['label']);
            }
            $message = sprintf(
                $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.items_proc_func_error'),
                $fieldLabel,
                $exception->getMessage()
            );
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                FlashMessage::ERROR,
                true
            );
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }

        return $items;
    }

    /**
     * PageTsConfig addItems:
     *
     * TCEFORMS.aTable.aField[.types][.aType].addItems.aValue = aLabel,
     * with type specific options merged by pageTsConfig already
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function addItemsFromPageTsConfig(array $result, $fieldName, array $items)
    {
        $table = $result['tableName'];
        if (!empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['addItems.'])
            && is_array($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['addItems.'])
        ) {
            $addItemsArray = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['addItems.'];
            foreach ($addItemsArray as $value => $label) {
                // If the value ends with a dot, it is a subelement like "34.icon = mylabel.png", skip it
                if (substr($value, -1) === '.') {
                    continue;
                }
                // Check if value "34 = mylabel" also has a "34.icon = myImage.png"
                $iconIdentifier = null;
                if (isset($addItemsArray[$value . '.'])
                    && is_array($addItemsArray[$value . '.'])
                    && !empty($addItemsArray[$value . '.']['icon'])
                ) {
                    $iconIdentifier = $addItemsArray[$value . '.']['icon'];
                }
                $items[] = [$label, $value, $iconIdentifier];
            }
        }
        return $items;
    }

    /**
     * TCA config "special" evaluation. Add them to $items
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     * @throws \UnexpectedValueException
     * @deprecated since v11, will be removed in v12
     */
    protected function addItemsFromSpecial(array $result, $fieldName, array $items)
    {
        // Guard
        if (empty($result['processedTca']['columns'][$fieldName]['config']['special'])
            || !is_string($result['processedTca']['columns'][$fieldName]['config']['special'])
        ) {
            return $items;
        }

        $special = $result['processedTca']['columns'][$fieldName]['config']['special'];

        trigger_error(
            'Using the TCA property \'special=' . $special . '\' is deprecated and will be removed in TYPO3 v12. Use a custom itemsProcFunc instead.',
            E_USER_DEPRECATED
        );

        switch (true) {
            case $special === 'tables':
                $fieldInformation = ['items' => $items];
                (new TcaItemsProcessorFunctions())->populateAvailableTables($fieldInformation);
                $items = $fieldInformation['items'];
                break;
            case $special === 'pagetypes':
                $fieldInformation = ['items' => $items];
                (new TcaItemsProcessorFunctions())->populateAvailablePageTypes($fieldInformation);
                $items = $fieldInformation['items'];
                break;
            case $special === 'exclude':
                $fieldInformation = ['items' => $items];
                (new TcaItemsProcessorFunctions())->populateExcludeFields($fieldInformation);
                $items = $fieldInformation['items'];
                break;
            case $special === 'explicitValues':
                $fieldInformation = ['items' => $items];
                (new TcaItemsProcessorFunctions())->populateExplicitAuthValues($fieldInformation);
                $items = $fieldInformation['items'];
                break;
            case $special === 'custom':
                $fieldInformation = ['items' => $items];
                (new TcaItemsProcessorFunctions())->populateCustomPermissionOptions($fieldInformation);
                $items = $fieldInformation['items'];
                break;
            case $special === 'modListGroup':
                $fieldInformation = ['items' => $items];
                (new TcaItemsProcessorFunctions())->populateAvailableGroupModules($fieldInformation);
                $items = $fieldInformation['items'];
                break;
            case $special === 'modListUser':
                $fieldInformation = ['items' => $items];
                (new TcaItemsProcessorFunctions())->populateAvailableUserModules($fieldInformation);
                $items = $fieldInformation['items'];
                break;
            default:
                throw new \UnexpectedValueException(
                    'Unknown special value ' . $special . ' for field ' . $fieldName . ' of table ' . $result['tableName'],
                    1439298496
                );
        }
        return $items;
    }

    /**
     * TCA config "fileFolder" evaluation. Add them to $items
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     * @throws \RuntimeException
     */
    protected function addItemsFromFolder(array $result, $fieldName, array $items)
    {
        if (empty($result['processedTca']['columns'][$fieldName]['config']['fileFolderConfig']['folder'])
            || !is_string($result['processedTca']['columns'][$fieldName]['config']['fileFolderConfig']['folder'])
        ) {
            return $items;
        }

        $tableName = $result['tableName'];
        $fileFolderConfig = $result['processedTca']['columns'][$fieldName]['config']['fileFolderConfig'];
        $fileFolderTSconfig = $result['pageTsConfig']['TCEFORM.'][$tableName . '.'][$fieldName . '.']['config.']['fileFolderConfig.'] ?? [];

        if (is_array($fileFolderTSconfig) && $fileFolderTSconfig !== []) {
            if ($fileFolderTSconfig['folder'] ?? false) {
                $fileFolderConfig['folder'] = $fileFolderTSconfig['folder'];
            }
            if (isset($fileFolderTSconfig['allowedExtensions'])) {
                $fileFolderConfig['allowedExtensions'] = $fileFolderTSconfig['allowedExtensions'];
            }
            if (isset($fileFolderTSconfig['depth'])) {
                $fileFolderConfig['depth'] = (int)$fileFolderTSconfig['depth'];
            }
        }

        $folderRaw = $fileFolderConfig['folder'];
        $folder = GeneralUtility::getFileAbsFileName($folderRaw);
        if ($folder === '') {
            throw new \RuntimeException(
                'Invalid folder given for item processing: ' . $folderRaw . ' for table ' . $tableName . ', field ' . $fieldName,
                1479399227
            );
        }
        $folder = rtrim($folder, '/') . '/';

        if (@is_dir($folder)) {
            $allowedExtensions = '';
            if (!empty($fileFolderConfig['allowedExtensions']) && is_string($fileFolderConfig['allowedExtensions'])) {
                $allowedExtensions = $fileFolderConfig['allowedExtensions'];
            }
            $depth = isset($fileFolderConfig['depth'])
                ? MathUtility::forceIntegerInRange($fileFolderConfig['depth'], 0, 99)
                : 99;
            $fileArray = GeneralUtility::getAllFilesAndFoldersInPath([], $folder, $allowedExtensions, false, $depth);
            $fileArray = GeneralUtility::removePrefixPathFromList($fileArray, $folder);
            foreach ($fileArray as $fileReference) {
                $fileInformation = pathinfo($fileReference);
                $icon = GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], strtolower($fileInformation['extension']))
                    ? $folder . $fileReference
                    : '';
                $items[] = [
                    $fileReference,
                    $fileReference,
                    $icon,
                ];
            }
        }

        return $items;
    }

    /**
     * TCA config "foreign_table" evaluation. Add them to $items
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     * @throws \UnexpectedValueException
     */
    protected function addItemsFromForeignTable(array $result, $fieldName, array $items)
    {
        $databaseError = null;
        $queryResult = null;
        // Guard
        if (empty($result['processedTca']['columns'][$fieldName]['config']['foreign_table'])
            || !is_string($result['processedTca']['columns'][$fieldName]['config']['foreign_table'])
        ) {
            return $items;
        }

        $languageService = $this->getLanguageService();

        $foreignTable = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'];

        if (!isset($GLOBALS['TCA'][$foreignTable]) || !is_array($GLOBALS['TCA'][$foreignTable])) {
            throw new \UnexpectedValueException(
                'Field ' . $fieldName . ' of table ' . $result['tableName'] . ' reference to foreign table '
                . $foreignTable . ', but this table is not defined in TCA',
                1439569743
            );
        }

        $queryBuilder = $this->buildForeignTableQueryBuilder($result, $fieldName);
        try {
            $queryResult = $queryBuilder->executeQuery();
        } catch (DBALException $e) {
            $databaseError = $e->getPrevious()->getMessage();
        }

        // Early return on error with flash message
        if (!empty($databaseError)) {
            $msg = $databaseError . '. ';
            $msg .= $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.database_schema_mismatch');
            $msgTitle = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.database_schema_mismatch_title');
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $msg, $msgTitle, FlashMessage::ERROR, true);
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
            return $items;
        }

        $labelPrefix = '';
        if (!empty($result['processedTca']['columns'][$fieldName]['config']['foreign_table_prefix'])) {
            $labelPrefix = $result['processedTca']['columns'][$fieldName]['config']['foreign_table_prefix'];
            $labelPrefix = $languageService->sL($labelPrefix);
        }

        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        while ($foreignRow = $queryResult->fetchAssociative()) {
            BackendUtility::workspaceOL($foreignTable, $foreignRow);
            // Only proceed in case the row was not unset and we don't deal with a delete placeholder
            if (is_array($foreignRow)
                && !VersionState::cast($foreignRow['t3ver_state'] ?? 0)->equals(VersionState::DELETE_PLACEHOLDER)
            ) {
                // If the foreign table sets selicon_field, this field can contain an image
                // that represents this specific row.
                $iconFieldName = '';
                $isReferenceField = false;
                if (!empty($GLOBALS['TCA'][$foreignTable]['ctrl']['selicon_field'])) {
                    $iconFieldName = $GLOBALS['TCA'][$foreignTable]['ctrl']['selicon_field'];
                    if (isset($GLOBALS['TCA'][$foreignTable]['columns'][$iconFieldName]['config']['type'])
                        && $GLOBALS['TCA'][$foreignTable]['columns'][$iconFieldName]['config']['type'] === 'inline'
                        && $GLOBALS['TCA'][$foreignTable]['columns'][$iconFieldName]['config']['foreign_table'] === 'sys_file_reference'
                    ) {
                        $isReferenceField = true;
                    }
                }
                $icon = '';
                if ($isReferenceField) {
                    $references = $fileRepository->findByRelation($foreignTable, $iconFieldName, $foreignRow['uid']);
                    if (is_array($references) && !empty($references)) {
                        $icon = reset($references);
                        $icon = $icon->getPublicUrl();
                    }
                } else {
                    // Else, determine icon based on record type, or a generic fallback
                    $icon = $iconFactory->mapRecordTypeToIconIdentifier($foreignTable, $foreignRow);
                }
                // Add the item
                $items[] = [
                    $labelPrefix . BackendUtility::getRecordTitle($foreignTable, $foreignRow),
                    $foreignRow['uid'],
                    $icon,
                ];
            }
        }

        return $items;
    }

    /**
     * Remove items using "keepItems" pageTsConfig
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function removeItemsByKeepItemsPageTsConfig(array $result, $fieldName, array $items)
    {
        $table = $result['tableName'];
        if (!isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['keepItems'])
            || !is_string($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['keepItems'])
        ) {
            return $items;
        }

        // If keepItems is set but is an empty list all current items get removed
        if ($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['keepItems'] === '') {
            return [];
        }

        return ArrayUtility::keepItemsInArray(
            $items,
            $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['keepItems'],
            static function ($value) {
                return $value[1];
            }
        );
    }

    /**
     * Remove items using "removeItems" pageTsConfig
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function removeItemsByRemoveItemsPageTsConfig(array $result, $fieldName, array $items)
    {
        $table = $result['tableName'];
        if (!isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['removeItems'])
            || !is_string($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['removeItems'])
            || $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['removeItems'] === ''
        ) {
            return $items;
        }

        $removeItems = array_flip(GeneralUtility::trimExplode(
            ',',
            $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['removeItems'],
            true
        ));
        foreach ($items as $key => $itemValues) {
            if (isset($removeItems[$itemValues[1]])) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Remove items user restriction on language field
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function removeItemsByUserLanguageFieldRestriction(array $result, $fieldName, array $items)
    {
        // Guard clause returns if not a language field is handled
        if (empty($result['processedTca']['ctrl']['languageField'])
            || $result['processedTca']['ctrl']['languageField'] !== $fieldName
        ) {
            return $items;
        }

        $backendUser = $this->getBackendUser();
        foreach ($items as $key => $itemValues) {
            if (!$backendUser->checkLanguageAccess($itemValues[1])) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Remove items by user restriction on authMode items
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function removeItemsByUserAuthMode(array $result, $fieldName, array $items)
    {
        // Guard clause returns early if no authMode field is configured
        if (!isset($result['processedTca']['columns'][$fieldName]['config']['authMode'])
            || !is_string($result['processedTca']['columns'][$fieldName]['config']['authMode'])
        ) {
            return $items;
        }

        $backendUser = $this->getBackendUser();
        $authMode = $result['processedTca']['columns'][$fieldName]['config']['authMode'];
        foreach ($items as $key => $itemValues) {
            // @todo: checkAuthMode() uses $GLOBAL access for "individual" authMode - get rid of this
            if (!$backendUser->checkAuthMode($result['tableName'], $fieldName, $itemValues[1], $authMode)) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Remove items if doktype is handled for non admin users
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function removeItemsByDoktypeUserRestriction(array $result, $fieldName, array $items)
    {
        $table = $result['tableName'];
        $backendUser = $this->getBackendUser();
        // Guard clause returns if not correct table and field or if user is admin
        if ($table !== 'pages' || $fieldName !== 'doktype' || $backendUser->isAdmin()
        ) {
            return $items;
        }

        $allowedPageTypes = $backendUser->groupData['pagetypes_select'];
        foreach ($items as $key => $itemValues) {
            if (!GeneralUtility::inList($allowedPageTypes, $itemValues[1])) {
                unset($items[$key]);
            }
        }

        return $items;
    }

    /**
     * Remove items if sys_file_storage is not allowed for non-admin users.
     *
     * Used by TcaSelectItems data providers
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     */
    protected function removeItemsByUserStorageRestriction(array $result, $fieldName, array $items)
    {
        $referencedTableName = $result['processedTca']['columns'][$fieldName]['config']['foreign_table'] ?? null;
        if ($referencedTableName !== 'sys_file_storage') {
            return $items;
        }

        $allowedStorageIds = array_map(
            static function (ResourceStorage $storage) {
                return $storage->getUid();
            },
            $this->getBackendUser()->getFileStorages()
        );

        return array_filter(
            $items,
            static function (array $item) use ($allowedStorageIds) {
                $itemValue = $item[1] ?? null;
                return empty($itemValue)
                    || in_array((int)$itemValue, $allowedStorageIds, true);
            }
        );
    }

    /**
     * Build query to fetch foreign records. Helper method of
     * addItemsFromForeignTable(), do not call otherwise.
     *
     * @param array $result Result array
     * @param string $localFieldName Current handle field name
     * @return QueryBuilder
     */
    protected function buildForeignTableQueryBuilder(array $result, string $localFieldName): QueryBuilder
    {
        $backendUser = $this->getBackendUser();

        $foreignTableName = $result['processedTca']['columns'][$localFieldName]['config']['foreign_table'];
        $foreignTableClauseArray = $this->processForeignTableClause($result, $foreignTableName, $localFieldName);

        $fieldList = BackendUtility::getCommonSelectFields($foreignTableName, $foreignTableName . '.');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($foreignTableName);

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $fieldList, true))
            ->from($foreignTableName)
            ->where($foreignTableClauseArray['WHERE']);

        if (!empty($foreignTableClauseArray['GROUPBY'])) {
            $queryBuilder->groupBy(...$foreignTableClauseArray['GROUPBY']);
        }

        if (!empty($foreignTableClauseArray['ORDERBY'])) {
            foreach ($foreignTableClauseArray['ORDERBY'] as $orderPair) {
                [$fieldName, $order] = $orderPair;
                $queryBuilder->addOrderBy($fieldName, $order);
            }
        } elseif (!empty($GLOBALS['TCA'][$foreignTableName]['ctrl']['default_sortby'])) {
            $orderByClauses = QueryHelper::parseOrderBy($GLOBALS['TCA'][$foreignTableName]['ctrl']['default_sortby']);
            foreach ($orderByClauses as $orderByClause) {
                if (!empty($orderByClause[0])) {
                    $queryBuilder->addOrderBy($foreignTableName . '.' . $orderByClause[0], $orderByClause[1]);
                }
            }
        }

        if (!empty($foreignTableClauseArray['LIMIT'])) {
            if (!empty($foreignTableClauseArray['LIMIT'][1])) {
                $queryBuilder->setMaxResults($foreignTableClauseArray['LIMIT'][1]);
                $queryBuilder->setFirstResult($foreignTableClauseArray['LIMIT'][0]);
            } elseif (!empty($foreignTableClauseArray['LIMIT'][0])) {
                $queryBuilder->setMaxResults($foreignTableClauseArray['LIMIT'][0]);
            }
        }

        // rootLevel = -1 means that elements can be on the rootlevel OR on any page (pid!=-1)
        // rootLevel = 0 means that elements are not allowed on root level
        // rootLevel = 1 means that elements are only on the root level (pid=0)
        $rootLevel = 0;
        if (isset($GLOBALS['TCA'][$foreignTableName]['ctrl']['rootLevel'])) {
            $rootLevel = (int)$GLOBALS['TCA'][$foreignTableName]['ctrl']['rootLevel'];
        }

        if ($rootLevel === -1) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->neq(
                    $foreignTableName . '.pid',
                    $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                )
            );
        } elseif ($rootLevel === 1) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq(
                    $foreignTableName . '.pid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            );
        } else {
            $queryBuilder->andWhere($backendUser->getPagePermsClause(Permission::PAGE_SHOW));
            if ($foreignTableName !== 'pages') {
                $queryBuilder
                    ->from('pages')
                    ->andWhere(
                        $queryBuilder->expr()->eq(
                            'pages.uid',
                            $queryBuilder->quoteIdentifier($foreignTableName . '.pid')
                        )
                    );
            }
        }

        // @todo what about PID restriction?
        if ($this->getBackendUser()->workspace !== 0 && BackendUtility::isTableWorkspaceEnabled($foreignTableName)) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->neq(
                        $foreignTableName . '.t3ver_state',
                        $queryBuilder->createNamedParameter(VersionState::MOVE_POINTER, \PDO::PARAM_INT)
                    )
                );
        }

        return $queryBuilder;
    }

    /**
     * Replace markers in a where clause from TCA foreign_table_where
     *
     * ###REC_FIELD_[field name]###
     * ###THIS_UID### - is current element uid (zero if new).
     * ###CURRENT_PID### - is the current page id (pid of the record).
     * ###SITEROOT###
     * ###PAGE_TSCONFIG_ID### - a value you can set from Page TSconfig dynamically.
     * ###PAGE_TSCONFIG_IDLIST### - a value you can set from Page TSconfig dynamically.
     * ###PAGE_TSCONFIG_STR### - a value you can set from Page TSconfig dynamically.
     *
     * @param array $result Result array
     * @param string $foreignTableName Name of foreign table
     * @param string $localFieldName Current handle field name
     * @return array Query parts with keys WHERE, ORDERBY, GROUPBY, LIMIT
     */
    protected function processForeignTableClause(array $result, $foreignTableName, $localFieldName)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($foreignTableName);
        $localTable = $result['tableName'];
        $effectivePid = $result['effectivePid'];

        $foreignTableClause = '';
        if (!empty($result['processedTca']['columns'][$localFieldName]['config']['foreign_table_where'])
            && is_string($result['processedTca']['columns'][$localFieldName]['config']['foreign_table_where'])
        ) {
            if (GeneralUtility::makeInstance(Features::class)->isFeatureEnabled('runtimeDbQuotingOfTcaConfiguration')) {
                $foreignTableClause = QueryHelper::quoteDatabaseIdentifiers($connection, $result['processedTca']['columns'][$localFieldName]['config']['foreign_table_where']);
            } else {
                $foreignTableClause = $result['processedTca']['columns'][$localFieldName]['config']['foreign_table_where'];
            }
            // Replace possible markers in query
            if (str_contains($foreignTableClause, '###REC_FIELD_')) {
                // " AND table.field='###REC_FIELD_field1###' AND ..." -> array(" AND table.field='", "field1###' AND ...")
                $whereClauseParts = explode('###REC_FIELD_', $foreignTableClause);
                foreach ($whereClauseParts as $key => $value) {
                    if ($key !== 0) {
                        // "field1###' AND ..." -> array("field1", "' AND ...")
                        $whereClauseSubParts = explode('###', $value, 2);
                        // @todo: Throw exception if there is no value? What happens for NEW records?
                        $databaseRowKey = empty($result['flexParentDatabaseRow']) ? 'databaseRow' : 'flexParentDatabaseRow';
                        $rowFieldValue = $result[$databaseRowKey][$whereClauseSubParts[0]] ?? '';
                        if (is_array($rowFieldValue)) {
                            // If a select or group field is used here, it may have been processed already and
                            // is now an array containing uid + table + title + row.
                            // See TcaGroup data provider for details.
                            // Pick the first one (always on 0), and use uid only.
                            $rowFieldValue = $rowFieldValue[0]['uid'] ?? $rowFieldValue[0];
                        }
                        if (substr($whereClauseParts[0], -1) === '\'' && $whereClauseSubParts[1][0] === '\'') {
                            $whereClauseParts[0] = substr($whereClauseParts[0], 0, -1);
                            $whereClauseSubParts[1] = substr($whereClauseSubParts[1], 1);
                        }
                        $whereClauseParts[$key] = $connection->quote($rowFieldValue) . $whereClauseSubParts[1];
                    }
                }
                $foreignTableClause = implode('', $whereClauseParts);
            }
            if (str_contains($foreignTableClause, '###CURRENT_PID###')) {
                // Use pid from parent page clause if in flex form context
                if (!empty($result['flexParentDatabaseRow']['pid'])) {
                    $effectivePid = $result['flexParentDatabaseRow']['pid'];
                } elseif (!$effectivePid && !empty($result['databaseRow']['pid'])) {
                    // Use pid from database row if in inline context
                    $effectivePid = $result['databaseRow']['pid'];
                }
            }

            $siteRootUid = 0;
            foreach ($result['rootline'] as $rootlinePage) {
                if (!empty($rootlinePage['is_siteroot'])) {
                    $siteRootUid = (int)$rootlinePage['uid'];
                    break;
                }
            }

            $pageTsConfigId = 0;
            if (isset($result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_ID'])
                && $result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_ID']
            ) {
                $pageTsConfigId = (int)$result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_ID'];
            }

            $pageTsConfigIdList = 0;
            if (isset($result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_IDLIST'])
                && $result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_IDLIST']
            ) {
                $pageTsConfigIdList = $result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_IDLIST'];
            }
            $pageTsConfigIdListArray = GeneralUtility::trimExplode(',', $pageTsConfigIdList, true);
            $pageTsConfigIdList = [];
            foreach ($pageTsConfigIdListArray as $pageTsConfigIdListElement) {
                if (MathUtility::canBeInterpretedAsInteger($pageTsConfigIdListElement)) {
                    $pageTsConfigIdList[] = (int)$pageTsConfigIdListElement;
                }
            }
            $pageTsConfigIdList = implode(',', $pageTsConfigIdList);

            $pageTsConfigString = '';
            if (isset($result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_STR'])
                && $result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_STR']
            ) {
                $pageTsConfigString = $result['pageTsConfig']['TCEFORM.'][$localTable . '.'][$localFieldName . '.']['PAGE_TSCONFIG_STR'];
                $pageTsConfigString = $connection->quote($pageTsConfigString);
            }

            $foreignTableClause = str_replace(
                [
                    '###CURRENT_PID###',
                    '###THIS_UID###',
                    '###SITEROOT###',
                    '###PAGE_TSCONFIG_ID###',
                    '###PAGE_TSCONFIG_IDLIST###',
                    '\'###PAGE_TSCONFIG_STR###\'',
                    '###PAGE_TSCONFIG_STR###',
                ],
                [
                    (int)$effectivePid,
                    (int)$result['databaseRow']['uid'],
                    $siteRootUid,
                    $pageTsConfigId,
                    $pageTsConfigIdList,
                    $pageTsConfigString,
                    $pageTsConfigString,
                ],
                $foreignTableClause
            );

            $parsedSiteConfiguration = $this->parseSiteConfiguration($result['site'], $foreignTableClause);
            if ($parsedSiteConfiguration !== []) {
                $parsedSiteConfiguration = $this->quoteParsedSiteConfiguration($connection, $parsedSiteConfiguration);
                $foreignTableClause = $this->replaceParsedSiteConfiguration($foreignTableClause, $parsedSiteConfiguration);
            }
        }

        // Split the clause into an array with keys WHERE, GROUPBY, ORDERBY, LIMIT
        // Prepend a space to make sure "[[:space:]]+" will find a space there for the first element.
        $foreignTableClause = ' ' . $foreignTableClause;
        $foreignTableClauseArray = [
            'WHERE' => '',
            'GROUPBY' => '',
            'ORDERBY' => '',
            'LIMIT' => '',
        ];
        // Find LIMIT
        $reg = [];
        if (preg_match('/^(.*)[[:space:]]+LIMIT[[:space:]]+([[:alnum:][:space:],._]+)$/is', $foreignTableClause, $reg)) {
            $foreignTableClauseArray['LIMIT'] = GeneralUtility::intExplode(',', trim($reg[2]), true);
            $foreignTableClause = $reg[1];
        }
        // Find ORDER BY
        $reg = [];
        if (preg_match('/^(.*)[[:space:]]+ORDER[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._()"]+)$/is', $foreignTableClause, $reg)) {
            $foreignTableClauseArray['ORDERBY'] = QueryHelper::parseOrderBy(trim($reg[2]));
            $foreignTableClause = $reg[1];
        }
        // Find GROUP BY
        $reg = [];
        if (preg_match('/^(.*)[[:space:]]+GROUP[[:space:]]+BY[[:space:]]+([[:alnum:][:space:],._()"]+)$/is', $foreignTableClause, $reg)) {
            $foreignTableClauseArray['GROUPBY'] = QueryHelper::parseGroupBy(trim($reg[2]));
            $foreignTableClause = $reg[1];
        }
        // Rest is assumed to be "WHERE" clause
        $foreignTableClauseArray['WHERE'] = QueryHelper::stripLogicalOperatorPrefix($foreignTableClause);

        return $foreignTableClauseArray;
    }

    /**
     * Parse ###SITE:### placeholders in the input string and return the replacements array for later use in
     * $this->replaceParsedSiteConfiguration().
     *
     * IMPORTANT: If the values are used within raw SQL statements (e.g. foreign_table_where), consider using
     * $this->quoteParsedSiteConfiguration() *before* replacement.
     */
    protected function parseSiteConfiguration(?SiteInterface $site, string $input): array
    {
        // Since we need to access the configuration, early return in case
        // we don't deal with an instance of Site (e.g. null or NullSite).
        if (!$site instanceof Site) {
            return [];
        }

        $siteClausesRegEx = '/###SITE:([^#]+)###/m';
        preg_match_all($siteClausesRegEx, $input, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return [];
        }

        $replacements = [];
        $configuration = $site->getConfiguration();
        array_walk($matches, static function ($match) use (&$replacements, &$configuration) {
            $key = $match[1];
            try {
                $value = ArrayUtility::getValueByPath($configuration, $key, '.');
            } catch (MissingArrayPathException $exception) {
                $value = '';
            }

            $replacements[$match[0]] = $value;
        });

        return $replacements;
    }

    protected function quoteParsedSiteConfiguration(Connection $connection, array $parsedSiteConfiguration): array
    {
        foreach ($parsedSiteConfiguration as $key => $value) {
            if (is_int($value)) {
                // int values are safe, nothing to do here
                continue;
            }
            if (is_string($value)) {
                $parsedSiteConfiguration[$key] = $connection->quote($value);
                continue;
            }
            if (is_array($value)) {
                $parsedSiteConfiguration[$key] = implode(',', $this->quoteParsedSiteConfiguration($connection, $value));
                continue;
            }
            if (is_bool($value)) {
                $parsedSiteConfiguration[$key] = (int)$value;
                continue;
            }
            throw new \InvalidArgumentException(
                sprintf('Cannot quote site configuration setting "%s" of type "%s", only "int", "bool", "string" and "array" are supported', $key, gettype($value)),
                1630324435
            );
        }

        return $parsedSiteConfiguration;
    }

    protected function replaceParsedSiteConfiguration(string $input, array $parsedSiteConfiguration): string
    {
        return str_replace(
            array_keys($parsedSiteConfiguration),
            array_values($parsedSiteConfiguration),
            $input
        );
    }

    /**
     * A field's [treeConfig][startingPoints] can be set via site config, parse possibly set values
     */
    protected function parseStartingPointsFromSiteConfiguration(array $result, array $fieldConfig): array
    {
        if (!isset($fieldConfig['config']['treeConfig']['startingPoints'])) {
            return $fieldConfig;
        }

        $parsedSiteConfiguration = $this->parseSiteConfiguration($result['site'], $fieldConfig['config']['treeConfig']['startingPoints']);
        if ($parsedSiteConfiguration !== []) {
            // $this->quoteParsedSiteConfiguration() is omitted on purpose, all values are cast to integers
            $parsedSiteConfiguration = array_unique(array_map(static function ($value): string {
                if (is_array($value)) {
                    return implode(',', array_map('intval', $value));
                }

                return implode(',', GeneralUtility::intExplode(',', $value, true));
            }, $parsedSiteConfiguration));
            $resolvedStartingPoints = $this->replaceParsedSiteConfiguration($fieldConfig['config']['treeConfig']['startingPoints'], $parsedSiteConfiguration);
            // Add the resolved starting points while removing empty values
            $fieldConfig['config']['treeConfig']['startingPoints'] = implode(
                ',',
                GeneralUtility::trimExplode(',', $resolvedStartingPoints, true)
            );
        }

        return $fieldConfig;
    }

    /**
     * Convert the current database values into an array
     *
     * @param array $row database row
     * @param string $fieldName fieldname to process
     * @return array
     */
    protected function processDatabaseFieldValue(array $row, $fieldName)
    {
        $currentDatabaseValues = array_key_exists($fieldName, $row)
            ? $row[$fieldName]
            : '';
        if (!is_array($currentDatabaseValues)) {
            $currentDatabaseValues = GeneralUtility::trimExplode(',', $currentDatabaseValues, true);
        }
        return $currentDatabaseValues;
    }

    /**
     * Validate and sanitize database row values of the select field with the given name.
     * Creates an array out of databaseRow[selectField] values.
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result The current result array.
     * @param string $fieldName Name of the current select field.
     * @param array $staticValues Array with statically defined items, item value is used as array key.
     * @return array
     */
    protected function processSelectFieldValue(array $result, $fieldName, array $staticValues)
    {
        $fieldConfig = $result['processedTca']['columns'][$fieldName];

        $currentDatabaseValueArray = array_key_exists($fieldName, $result['databaseRow']) ? $result['databaseRow'][$fieldName] : [];
        $newDatabaseValueArray = [];

        // Add all values that were defined by static methods and do not come from the relation
        // e.g. TCA, TSconfig, itemProcFunc etc.
        foreach ($currentDatabaseValueArray as $value) {
            if (isset($staticValues[$value])) {
                $newDatabaseValueArray[] = $value;
            }
        }

        if (isset($fieldConfig['config']['foreign_table']) && !empty($fieldConfig['config']['foreign_table'])) {
            $relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
            $relationHandler->registerNonTableValues = !empty($fieldConfig['config']['allowNonIdValues']);
            if (!empty($fieldConfig['config']['MM']) && $result['command'] !== 'new') {
                // MM relation
                $relationHandler->start(
                    implode(',', $currentDatabaseValueArray),
                    $fieldConfig['config']['foreign_table'],
                    $fieldConfig['config']['MM'],
                    $result['databaseRow']['uid'],
                    $result['tableName'],
                    $fieldConfig['config']
                );
                $relationHandler->processDeletePlaceholder();
                $newDatabaseValueArray = array_merge($newDatabaseValueArray, $relationHandler->getValueArray());
            } else {
                // Non MM relation
                // If not dealing with MM relations, use default live uid, not versioned uid for record relations
                $relationHandler->start(
                    implode(',', $currentDatabaseValueArray),
                    $fieldConfig['config']['foreign_table'],
                    '',
                    $this->getLiveUid($result),
                    $result['tableName'],
                    $fieldConfig['config']
                );
                $relationHandler->processDeletePlaceholder();
                $databaseIds = array_merge($newDatabaseValueArray, $relationHandler->getValueArray());
                // remove all items from the current DB values if not available as relation or static value anymore
                $newDatabaseValueArray = array_values(array_intersect($currentDatabaseValueArray, $databaseIds));
            }
        }

        if ($fieldConfig['config']['multiple'] ?? false) {
            return $newDatabaseValueArray;
        }
        return array_unique($newDatabaseValueArray);
    }

    /**
     * Translate the item labels
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param array $result Result array
     * @param array $itemArray Items
     * @param string $table
     * @param string $fieldName
     * @return array
     */
    public function translateLabels(array $result, array $itemArray, $table, $fieldName)
    {
        $languageService = $this->getLanguageService();

        foreach ($itemArray as $key => $item) {
            if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'][$item[1]])
                && !empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'][$item[1]])
            ) {
                $label = $languageService->sL($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'][$item[1]]);
            } else {
                $label = $languageService->sL(trim($item[0]));
            }
            $value = strlen((string)$item[1]) > 0 ? $item[1] : '';
            $icon = !empty($item[2]) ? $item[2] : null;
            $groupId = $item[3] ?? null;
            $helpText = null;
            if (!empty($item[4])) {
                if (\is_string($item[4])) {
                    $helpText = $languageService->sL($item[4]);
                } else {
                    $helpText = $item[4];
                }
            }
            $itemArray[$key] = [
                $label,
                $value,
                $icon,
                $groupId,
                $helpText,
            ];
        }

        return $itemArray;
    }

    /**
     * Add alternative icon using "altIcons" TSconfig
     *
     * @param array $result
     * @param array $items
     * @param string $table
     * @param string $fieldName
     *
     * @return array
     */
    public function addIconFromAltIcons(array $result, array $items, string $table, string $fieldName): array
    {
        foreach ($items as &$item) {
            if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altIcons.'][$item[1]])
                && !empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altIcons.'][$item[1]])
            ) {
                $item[2] = $result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altIcons.'][$item[1]];
            }
        }

        return $items;
    }

    /**
     * Sanitize incoming item array
     *
     * Used by TcaSelectItems and TcaSelectTreeItems data providers
     *
     * @param mixed $itemArray
     * @param string $tableName
     * @param string $fieldName
     * @throws \UnexpectedValueException
     * @return array
     */
    public function sanitizeItemArray($itemArray, $tableName, $fieldName)
    {
        if (!is_array($itemArray)) {
            $itemArray = [];
        }
        foreach ($itemArray as $item) {
            if (!is_array($item)) {
                throw new \UnexpectedValueException(
                    'An item in field ' . $fieldName . ' of table ' . $tableName . ' is not an array as expected',
                    1439288036
                );
            }
        }

        return $itemArray;
    }

    /**
     * Gets the record uid of the live default record. If already
     * pointing to the live record, the submitted record uid is returned.
     *
     * @param array $result Result array
     * @return int|string If the record is new, uid will be a string beginning with "NEW". Otherwise an int.
     * @throws \UnexpectedValueException
     */
    protected function getLiveUid(array $result)
    {
        $table = $result['tableName'];
        $row = $result['databaseRow'];
        $uid = $row['uid'] ?? 0;
        if (BackendUtility::isTableWorkspaceEnabled($table) && (int)($row['t3ver_oid'] ?? 0) > 0) {
            $uid = $row['t3ver_oid'];
        }
        return $uid;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
