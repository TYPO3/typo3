<?php
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

use Doctrine\DBAL\DBALException;
use TYPO3\CMS\Backend\Module\ModuleLoader;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

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
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $message,
                '',
                FlashMessage::ERROR,
                true
            );
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
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
     */
    protected function addItemsFromSpecial(array $result, $fieldName, array $items)
    {
        // Guard
        if (empty($result['processedTca']['columns'][$fieldName]['config']['special'])
            || !is_string($result['processedTca']['columns'][$fieldName]['config']['special'])
        ) {
            return $items;
        }

        $languageService = $this->getLanguageService();
        $iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $special = $result['processedTca']['columns'][$fieldName]['config']['special'];
        switch (true) {
            case $special === 'tables':
                foreach ($GLOBALS['TCA'] as $currentTable => $_) {
                    if (!empty($GLOBALS['TCA'][$currentTable]['ctrl']['adminOnly'])) {
                        // Hide "admin only" tables
                        continue;
                    }
                    $label = !empty($GLOBALS['TCA'][$currentTable]['ctrl']['title']) ? $GLOBALS['TCA'][$currentTable]['ctrl']['title'] : '';
                    $icon = $iconFactory->mapRecordTypeToIconIdentifier($currentTable, []);
                    $helpText = [];
                    $languageService->loadSingleTableDescription($currentTable);
                    // @todo: check if this actually works, currently help texts are missing
                    $helpTextArray = $GLOBALS['TCA_DESCR'][$currentTable]['columns'][''];
                    if (!empty($helpTextArray['description'])) {
                        $helpText['description'] = $helpTextArray['description'];
                    }
                    $items[] = [$label, $currentTable, $icon, $helpText];
                }
                break;
            case $special === 'pagetypes':
                if (isset($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'])
                    && is_array($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'])
                ) {
                    $specialItems = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
                    foreach ($specialItems as $specialItem) {
                        if (!is_array($specialItem) || $specialItem[1] === '--div--') {
                            // Skip non arrays and divider items
                            continue;
                        }
                        $label = $specialItem[0];
                        $value = $specialItem[1];
                        $icon = $iconFactory->mapRecordTypeToIconIdentifier('pages', ['doktype' => $specialItem[1]]);
                        $items[] = [$label, $value, $icon];
                    }
                }
                break;
            case $special === 'exclude':
                $excludeArrays = $this->getExcludeFields();
                foreach ($excludeArrays as $excludeArray) {
                    // If the field comes from a FlexForm, the syntax is more complex
                    if ($excludeArray['origin'] === 'flexForm') {
                        // The field comes from a plugins FlexForm
                        // Add header if not yet set for plugin section
                        if (!isset($items[$excludeArray['sectionHeader']])) {
                            // there is no icon handling for plugins - we take the icon from the table
                            $icon = $iconFactory->mapRecordTypeToIconIdentifier($excludeArray['table'], []);
                            $items[$excludeArray['sectionHeader']] = [
                                $excludeArray['sectionHeader'],
                                '--div--',
                                $icon
                            ];
                        }
                    } else {
                        // Add header if not yet set for table
                        if (!isset($items[$excludeArray['table']])) {
                            $icon = $iconFactory->mapRecordTypeToIconIdentifier($excludeArray['table'], []);
                            $items[$excludeArray['table']] = [
                                $GLOBALS['TCA'][$excludeArray['table']]['ctrl']['title'],
                                '--div--',
                                $icon
                            ];
                        }
                    }
                    // Add help text
                    $helpText = [];
                    $languageService->loadSingleTableDescription($excludeArray['table']);
                    $helpTextArray = $GLOBALS['TCA_DESCR'][$excludeArray['table']]['columns'][$excludeArray['table']] ?? [];
                    if (!empty($helpTextArray['description'])) {
                        $helpText['description'] = $helpTextArray['description'];
                    }
                    // Item configuration:
                    $items[] = [
                        rtrim($excludeArray['origin'] === 'flexForm' ? $excludeArray['fieldLabel'] : $languageService->sL($GLOBALS['TCA'][$excludeArray['table']]['columns'][$excludeArray['fieldName']]['label']), ':') . ' (' . $excludeArray['fieldName'] . ')',
                        $excludeArray['table'] . ':' . $excludeArray['fullField'],
                        'empty-empty',
                        $helpText
                    ];
                }
                break;
            case $special === 'explicitValues':
                $theTypes = $this->getExplicitAuthFieldValues();
                $icons = [
                    'ALLOW' => 'status-status-permission-granted',
                    'DENY' => 'status-status-permission-denied'
                ];
                // Traverse types:
                foreach ($theTypes as $tableFieldKey => $theTypeArrays) {
                    if (!empty($theTypeArrays['items'])) {
                        // Add header:
                        $items[] = [
                            $theTypeArrays['tableFieldLabel'],
                            '--div--',
                        ];
                        // Traverse options for this field:
                        foreach ($theTypeArrays['items'] as $itemValue => $itemContent) {
                            // Add item to be selected:
                            $items[] = [
                                '[' . $itemContent[2] . '] ' . $itemContent[1],
                                $tableFieldKey . ':' . preg_replace('/[:|,]/', '', $itemValue) . ':' . $itemContent[0],
                                $icons[$itemContent[0]]
                            ];
                        }
                    }
                }
                break;
            case $special === 'languages':
                foreach ($result['systemLanguageRows'] as $language) {
                    if ($language['uid'] !== -1) {
                        $items[] = [
                            0 => $language['title'],
                            1 => $language['uid'],
                            2 => $language['flagIconIdentifier']
                        ];
                    }
                }
                break;
            case $special === 'custom':
                $customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
                if (is_array($customOptions)) {
                    foreach ($customOptions as $coKey => $coValue) {
                        if (is_array($coValue['items'])) {
                            // Add header:
                            $items[] = [
                                $languageService->sL($coValue['header']),
                                '--div--'
                            ];
                            // Traverse items:
                            foreach ($coValue['items'] as $itemKey => $itemCfg) {
                                $icon = 'empty-empty';
                                $helpText = [];
                                if (!empty($itemCfg[1])) {
                                    if ($iconRegistry->isRegistered($itemCfg[1])) {
                                        // Use icon identifier when registered
                                        $icon = $itemCfg[1];
                                    }
                                }
                                if (!empty($itemCfg[2])) {
                                    $helpText['description'] = $languageService->sL($itemCfg[2]);
                                }
                                $items[] = [
                                    $languageService->sL($itemCfg[0]),
                                    $coKey . ':' . preg_replace('/[:|,]/', '', $itemKey),
                                    $icon,
                                    $helpText
                                ];
                            }
                        }
                    }
                }
                break;
            case $special === 'modListGroup' || $special === 'modListUser':
                /** @var ModuleLoader $loadModules */
                $loadModules = GeneralUtility::makeInstance(ModuleLoader::class);
                $loadModules->load($GLOBALS['TBE_MODULES']);
                $modList = $special === 'modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
                if (is_array($modList)) {
                    foreach ($modList as $theMod) {
                        $moduleLabels = $loadModules->getLabelsForModule($theMod);
                        $moduleArray = GeneralUtility::trimExplode('_', $theMod, true);
                        $mainModule = $moduleArray[0] ?? '';
                        $subModule = $moduleArray[1] ?? '';
                        // Icon:
                        if (!empty($subModule)) {
                            $icon = $loadModules->modules[$mainModule]['sub'][$subModule]['iconIdentifier'];
                        } else {
                            $icon = $loadModules->modules[$theMod]['iconIdentifier'];
                        }
                        // Add help text
                        $helpText = [
                            'title' => $languageService->sL($moduleLabels['shortdescription']),
                            'description' => $languageService->sL($moduleLabels['description'])
                        ];

                        $label = '';
                        // Add label for main module if this is a submodule
                        if (!empty($subModule)) {
                            $mainModuleLabels = $loadModules->getLabelsForModule($mainModule);
                            $label .= $languageService->sL($mainModuleLabels['title']) . '>';
                        }
                        // Add modules own label now
                        $label .= $languageService->sL($moduleLabels['title']);

                        // Item configuration
                        $items[] = [$label, $theMod, $icon, $helpText];
                    }
                }
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
        if (empty($result['processedTca']['columns'][$fieldName]['config']['fileFolder'])
            || !is_string($result['processedTca']['columns'][$fieldName]['config']['fileFolder'])
        ) {
            return $items;
        }

        $fileFolderRaw = $result['processedTca']['columns'][$fieldName]['config']['fileFolder'];
        $fileFolder = GeneralUtility::getFileAbsFileName($fileFolderRaw);
        if ($fileFolder === '') {
            throw new \RuntimeException(
                'Invalid folder given for item processing: ' . $fileFolderRaw . ' for table ' . $result['tableName'] . ', field ' . $fieldName,
                1479399227
            );
        }
        $fileFolder = rtrim($fileFolder, '/') . '/';

        if (@is_dir($fileFolder)) {
            $fileExtensionList = '';
            if (!empty($result['processedTca']['columns'][$fieldName]['config']['fileFolder_extList'])
                && is_string($result['processedTca']['columns'][$fieldName]['config']['fileFolder_extList'])
            ) {
                $fileExtensionList = $result['processedTca']['columns'][$fieldName]['config']['fileFolder_extList'];
            }
            $recursionLevels = isset($result['processedTca']['columns'][$fieldName]['config']['fileFolder_recursions'])
                ? MathUtility::forceIntegerInRange($result['processedTca']['columns'][$fieldName]['config']['fileFolder_recursions'], 0, 99)
                : 99;
            $fileArray = GeneralUtility::getAllFilesAndFoldersInPath([], $fileFolder, $fileExtensionList, 0, $recursionLevels);
            $fileArray = GeneralUtility::removePrefixPathFromList($fileArray, $fileFolder);
            foreach ($fileArray as $fileReference) {
                $fileInformation = pathinfo($fileReference);
                $icon = GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], strtolower($fileInformation['extension']))
                    ? $fileFolder . $fileReference
                    : '';
                $items[] = [
                    $fileReference,
                    $fileReference,
                    $icon
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
            $queryResult = $queryBuilder->execute();
        } catch (DBALException $e) {
            $databaseError = $e->getPrevious()->getMessage();
        }

        // Early return on error with flash message
        if (!empty($databaseError)) {
            $msg = $databaseError . '. ';
            $msg .= $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.database_schema_mismatch');
            $msgTitle = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.database_schema_mismatch_title');
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $msg, $msgTitle, FlashMessage::ERROR, true);
            /** @var FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
            return $items;
        }

        $labelPrefix = '';
        if (!empty($result['processedTca']['columns'][$fieldName]['config']['foreign_table_prefix'])) {
            $labelPrefix = $result['processedTca']['columns'][$fieldName]['config']['foreign_table_prefix'];
            $labelPrefix = $languageService->sL($labelPrefix);
        }

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $fileRepository = GeneralUtility::makeInstance(FileRepository::class);

        while ($foreignRow = $queryResult->fetch()) {
            BackendUtility::workspaceOL($foreignTable, $foreignRow);
            if (is_array($foreignRow)) {
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
                    $iconPath = '';
                    if (!empty($GLOBALS['TCA'][$foreignTable]['ctrl']['selicon_field_path'])) {
                        $iconPath = $GLOBALS['TCA'][$foreignTable]['ctrl']['selicon_field_path'];
                    }
                    if ($iconFieldName && $iconPath && $foreignRow[$iconFieldName]) {
                        // Prepare the row icon if available
                        $iParts = GeneralUtility::trimExplode(',', $foreignRow[$iconFieldName], true);
                        $icon = $iconPath . '/' . trim($iParts[0]);
                    } else {
                        // Else, determine icon based on record type, or a generic fallback
                        $icon = $iconFactory->mapRecordTypeToIconIdentifier($foreignTable, $foreignRow);
                    }
                }
                // Add the item
                $items[] = [
                    $labelPrefix . BackendUtility::getRecordTitle($foreignTable, $foreignRow),
                    $foreignRow['uid'],
                    $icon
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
            function ($value) {
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
            function (\TYPO3\CMS\Core\Resource\ResourceStorage $storage) {
                return $storage->getUid();
            },
            $this->getBackendUser()->getFileStorages()
        );

        return array_filter(
            $items,
            function (array $item) use ($allowedStorageIds) {
                $itemValue = $item[1] ?? null;
                return empty($itemValue)
                    || in_array((int)$itemValue, $allowedStorageIds, true);
            }
        );
    }

    /**
     * Returns an array with the exclude fields as defined in TCA and FlexForms
     * Used for listing the exclude fields in be_groups forms.
     *
     * @return array Array of arrays with excludeFields (fieldName, table:fieldName) from TCA
     *               and FlexForms (fieldName, table:extKey;sheetName;fieldName)
     */
    protected function getExcludeFields()
    {
        $languageService = $this->getLanguageService();
        $finalExcludeArray = [];

        // Fetch translations for table names
        $tableToTranslation = [];
        // All TCA keys
        foreach ($GLOBALS['TCA'] as $table => $conf) {
            $tableToTranslation[$table] = $languageService->sL($conf['ctrl']['title']);
        }
        // Sort by translations
        asort($tableToTranslation);
        foreach ($tableToTranslation as $table => $translatedTable) {
            $excludeArrayTable = [];

            // All field names configured and not restricted to admins
            if (is_array($GLOBALS['TCA'][$table]['columns'])
                && empty($GLOBALS['TCA'][$table]['ctrl']['adminOnly'])
                && (empty($GLOBALS['TCA'][$table]['ctrl']['rootLevel']) || !empty($GLOBALS['TCA'][$table]['ctrl']['security']['ignoreRootLevelRestriction']))
            ) {
                foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $_) {
                    if (isset($GLOBALS['TCA'][$table]['columns'][$field]['exclude']) && (bool)$GLOBALS['TCA'][$table]['columns'][$field]['exclude']) {
                        // Get human readable names of fields
                        $translatedField = $languageService->sL($GLOBALS['TCA'][$table]['columns'][$field]['label']);
                        // Add entry, key 'labels' needed for sorting
                        $excludeArrayTable[] = [
                            'labels' => $translatedTable . ':' . $translatedField,
                            'sectionHeader' => $translatedTable,
                            'table' => $table,
                            'tableField' => $field,
                            'fieldName' => $field,
                            'fullField' => $field,
                            'fieldLabel' => $translatedField,
                            'origin' => 'tca',
                        ];
                    }
                }
            }
            // All FlexForm fields
            $flexFormArray = $this->getRegisteredFlexForms($table);
            foreach ($flexFormArray as $tableField => $flexForms) {
                // Prefix for field label, e.g. "Plugin Options:"
                $labelPrefix = '';
                if (!empty($GLOBALS['TCA'][$table]['columns'][$tableField]['label'])) {
                    $labelPrefix = $languageService->sL($GLOBALS['TCA'][$table]['columns'][$tableField]['label']);
                }
                // Get all sheets
                foreach ($flexForms as $extIdent => $extConf) {
                    // Get all fields in sheet
                    foreach ($extConf['sheets'] as $sheetName => $sheet) {
                        if (empty($sheet['ROOT']['el']) || !is_array($sheet['ROOT']['el'])) {
                            continue;
                        }
                        foreach ($sheet['ROOT']['el'] as $pluginFieldName => $field) {
                            // Use only fields that have exclude flag set
                            if (empty($field['TCEforms']['exclude'])) {
                                continue;
                            }
                            $fieldLabel = !empty($field['TCEforms']['label'])
                                ? $languageService->sL($field['TCEforms']['label'])
                                : $pluginFieldName;
                            $excludeArrayTable[] = [
                                'labels' => trim($translatedTable . ' ' . $labelPrefix . ' ' . $extIdent, ': ') . ':' . $fieldLabel,
                                'sectionHeader' => trim($translatedTable . ' ' . $labelPrefix . ' ' . $extIdent, ':'),
                                'table' => $table,
                                'tableField' => $tableField,
                                'extIdent' => $extIdent,
                                'fieldName' => $pluginFieldName,
                                'fullField' => $tableField . ';' . $extIdent . ';' . $sheetName . ';' . $pluginFieldName,
                                'fieldLabel' => $fieldLabel,
                                'origin' => 'flexForm',
                            ];
                        }
                    }
                }
            }
            // Sort fields by the translated value
            if (!empty($excludeArrayTable)) {
                usort($excludeArrayTable, function (array $array1, array $array2) {
                    $array1 = reset($array1);
                    $array2 = reset($array2);
                    if (is_string($array1) && is_string($array2)) {
                        return strcasecmp($array1, $array2);
                    }
                    return 0;
                });
                $finalExcludeArray = array_merge($finalExcludeArray, $excludeArrayTable);
            }
        }

        return $finalExcludeArray;
    }

    /**
     * Returns FlexForm data structures it finds. Used in select "special" for be_groups
     * to set "exclude" flags for single flex form fields.
     *
     * This only finds flex forms registered in 'ds' config sections.
     * This does not resolve other sophisticated flex form data structure references.
     *
     * @todo: This approach is limited and doesn't find everything. It works for casual tt_content plugins, though:
     * @todo: The data structure identifier determination depends on data row, but we don't have all rows at hand here.
     * @todo: The code thus "guesses" some standard data structure identifier scenarios and tries to resolve those.
     * @todo: This guessing can not be solved in a good way. A general registry of "all" possible data structures is
     * @todo: probably not wanted, since that wouldn't work for truly dynamic DS calculations. Probably the only
     * @todo: thing we could do here is a hook to allow extensions declaring specific data structures to
     * @todo: allow backend admins to set exclude flags for certain fields in those cases.
     *
     * @param string $table Table to handle
     * @return array Data structures
     */
    protected function getRegisteredFlexForms($table)
    {
        if (empty($table) || empty($GLOBALS['TCA'][$table]['columns'])) {
            return [];
        }
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $flexForms = [];
        foreach ($GLOBALS['TCA'][$table]['columns'] as $tableField => $fieldConf) {
            if (!empty($fieldConf['config']['type']) && !empty($fieldConf['config']['ds']) && $fieldConf['config']['type'] === 'flex') {
                $flexForms[$tableField] = [];
                foreach (array_keys($fieldConf['config']['ds']) as $flexFormKey) {
                    // Get extension identifier (uses second value if it's not empty, "list" or "*", else first one)
                    $identFields = GeneralUtility::trimExplode(',', $flexFormKey);
                    $extIdent = $identFields[0];
                    if (!empty($identFields[1]) && $identFields[1] !== 'list' && $identFields[1] !== '*') {
                        $extIdent = $identFields[1];
                    }
                    $flexFormDataStructureIdentifier = json_encode([
                        'type' => 'tca',
                        'tableName' => $table,
                        'fieldName' => $tableField,
                        'dataStructureKey' => $flexFormKey,
                    ]);
                    try {
                        $dataStructure = $flexFormTools->parseDataStructureByIdentifier($flexFormDataStructureIdentifier);
                        $flexForms[$tableField][$extIdent] = $dataStructure;
                    } catch (InvalidIdentifierException $e) {
                        // Deliberately empty: The DS identifier is guesswork and the flex ds parser throws
                        // this exception if it can not resolve to a valid data structure. This is "ok" here
                        // and the exception is just eaten.
                    }
                }
            }
        }
        return $flexForms;
    }

    /**
     * Returns an array with explicit Allow/Deny fields.
     * Used for listing these field/value pairs in be_groups forms
     *
     * @return array Array with information from all of $GLOBALS['TCA']
     */
    protected function getExplicitAuthFieldValues()
    {
        $languageService = static::getLanguageService();
        $adLabel = [
            'ALLOW' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.allow'),
            'DENY' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deny')
        ];
        $allowDenyOptions = [];
        foreach ($GLOBALS['TCA'] as $table => $_) {
            // All field names configured:
            if (is_array($GLOBALS['TCA'][$table]['columns'])) {
                foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $__) {
                    $fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
                    if ($fieldConfig['type'] === 'select' && $fieldConfig['authMode']) {
                        // Check for items
                        if (is_array($fieldConfig['items'])) {
                            // Get Human Readable names of fields and table:
                            $allowDenyOptions[$table . ':' . $field]['tableFieldLabel'] =
                                $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title']) . ': '
                                . $languageService->sL($GLOBALS['TCA'][$table]['columns'][$field]['label']);
                            foreach ($fieldConfig['items'] as $iVal) {
                                $itemIdentifier = (string)$iVal[1];
                                // Values '' and '--div--' are not controlled by this setting.
                                if ($itemIdentifier === '' || $itemIdentifier === '--div--') {
                                    continue;
                                }
                                // Find iMode
                                $iMode = '';
                                switch ((string)$fieldConfig['authMode']) {
                                    case 'explicitAllow':
                                        $iMode = 'ALLOW';
                                        break;
                                    case 'explicitDeny':
                                        $iMode = 'DENY';
                                        break;
                                    case 'individual':
                                        if (isset($iVal[4]) && $iVal[4] === 'EXPL_ALLOW') {
                                            $iMode = 'ALLOW';
                                        } elseif (isset($iVal[4]) && $iVal[4] === 'EXPL_DENY') {
                                            $iMode = 'DENY';
                                        }
                                        break;
                                }
                                // Set iMode
                                if ($iMode) {
                                    $allowDenyOptions[$table . ':' . $field]['items'][$itemIdentifier] = [
                                        $iMode,
                                        $languageService->sL($iVal[0]),
                                        $adLabel[$iMode]
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $allowDenyOptions;
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
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($foreignTableName);

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $fieldList, true))
            ->from($foreignTableName)
            ->where($foreignTableClauseArray['WHERE']);

        if (!empty($foreignTableClauseArray['GROUPBY'])) {
            $queryBuilder->groupBy(...$foreignTableClauseArray['GROUPBY']);
        }

        if (!empty($foreignTableClauseArray['ORDERBY'])) {
            foreach ($foreignTableClauseArray['ORDERBY'] as $orderPair) {
                list($fieldName, $order) = $orderPair;
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
            $foreignTableClause = $result['processedTca']['columns'][$localFieldName]['config']['foreign_table_where'];
            // Replace possible markers in query
            if (strstr($foreignTableClause, '###REC_FIELD_')) {
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
            if (strpos($foreignTableClause, '###CURRENT_PID###') !== false) {
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
                    '###PAGE_TSCONFIG_STR###'
                ],
                [
                    (int)$effectivePid,
                    (int)$result['databaseRow']['uid'],
                    $siteRootUid,
                    $pageTsConfigId,
                    $pageTsConfigIdList,
                    $pageTsConfigString,
                    $pageTsConfigString
                ],
                $foreignTableClause
            );
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
            /** @var RelationHandler $relationHandler */
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
            if (!isset($dynamicItems[$key])) {
                $staticValues[$item[1]] = $item;
            }
            if (isset($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'][$item[1]])
                && !empty($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'][$item[1]])
            ) {
                $label = $languageService->sL($result['pageTsConfig']['TCEFORM.'][$table . '.'][$fieldName . '.']['altLabels.'][$item[1]]);
            } else {
                $label = $languageService->sL(trim($item[0]));
            }
            $value = strlen((string)$item[1]) > 0 ? $item[1] : '';
            $icon = !empty($item[2]) ? $item[2] : null;
            $helpText = null;
            if (!empty($item[3])) {
                if (\is_string($item[3])) {
                    $helpText = $languageService->sL($item[3]);
                } else {
                    $helpText = $item[3];
                }
            }
            $itemArray[$key] = [
                $label,
                $value,
                $icon,
                $helpText
            ];
        }

        return $itemArray;
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
     * @return int
     * @throws \UnexpectedValueException
     */
    protected function getLiveUid(array $result)
    {
        $table = $result['tableName'];
        $row = $result['databaseRow'];
        $uid = $row['uid'];
        if (!empty($result['processedTca']['ctrl']['versioningWS'])
            && $result['pid'] === -1
        ) {
            if (empty($row['t3ver_oid'])) {
                throw new \UnexpectedValueException(
                    'No t3ver_oid found for record ' . $row['uid'] . ' on table ' . $table,
                    1440066481
                );
            }
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
