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

namespace TYPO3\CMS\Core\Imaging;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * The main factory class, which acts as the entrypoint for generating an Icon object which
 * is responsible for rendering an icon. Checks for the correct icon provider through the IconRegistry.
 */
readonly class IconFactory
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private IconRegistry $iconRegistry,
        private ContainerInterface $container,
        private FrontendInterface $runtimeCache,
    ) {}

    /**
     * @todo: Change $size to allow IconSize only in v14
     */
    public function getIcon(
        string $identifier,
        string|IconSize $size = IconSize::MEDIUM,
        ?string $overlayIdentifier = null,
        \TYPO3\CMS\Core\Type\Icon\IconState|IconState|null $state = null
    ): Icon {
        if ($state instanceof \TYPO3\CMS\Core\Type\Icon\IconState) {
            trigger_error(
                'Using the non-native enumeration TYPO3\CMS\Core\Type\Icon\IconState in IconFactory->getIcon()'
                . ' will not work in TYPO3 v14.0 anymore. Use native TYPO3\CMS\Core\Imaging\IconState instead.',
                E_USER_DEPRECATED
            );
            $stateValue = (string)$state;
        } else {
            $stateValue = $state->value ?? '';
        }
        if (is_string($size)) {
            $size = IconSize::from($size);
            $size->triggerDeprecation();
        }
        $cacheIdentifier = 'icon-factory-' . hash('xxh3', $identifier . $size->value . $overlayIdentifier . $stateValue);
        $icon = $this->runtimeCache->get($cacheIdentifier);
        if ($icon instanceof Icon) {
            return $icon;
        }

        if (!$this->iconRegistry->isDeprecated($identifier) && !$this->iconRegistry->isRegistered($identifier)) {
            // If icon identifier is neither deprecated nor registered
            $identifier = $this->iconRegistry->getDefaultIconIdentifier();
        }

        $iconConfiguration = $this->iconRegistry->getIconConfigurationByIdentifier($identifier);
        $iconConfiguration['state'] = $stateValue;
        $icon = $this->createIcon($identifier, $size, $overlayIdentifier, $iconConfiguration);

        /** @var IconProviderInterface $iconProvider */
        $iconProvider = $this->container->has($iconConfiguration['provider']) ?
            $this->container->get($iconConfiguration['provider']) :
            GeneralUtility::makeInstance($iconConfiguration['provider']);
        $iconProvider->prepareIconMarkup($icon, $iconConfiguration['options']);

        $this->runtimeCache->set($cacheIdentifier, $icon);

        return $icon;
    }

    /**
     * This method is used throughout the TYPO3 Backend to show icons for a DB record
     *
     * @param string $table The TCA table name
     * @param array $row The DB record of the TCA table
     * @todo: Change $size to allow IconSize only in v14
     */
    public function getIconForRecord(string $table, array $row, string|IconSize $size = IconSize::MEDIUM): Icon
    {
        if (is_string($size)) {
            $size = IconSize::from($size);
            $size->triggerDeprecation();
        }
        $iconIdentifier = $this->mapRecordTypeToIconIdentifier($table, $row);
        $overlayIdentifier = $this->mapRecordTypeToOverlayIdentifier($table, $row);
        return $this->getIcon($iconIdentifier, $size, $overlayIdentifier);
    }

    /**
     * This helper functions looks up the column that is used for the type of the chosen TCA table and then fetches the
     * corresponding iconName based on the chosen icon class in this TCA.
     * The TCA looks up
     * - [ctrl][typeicon_column]
     * -
     * This method solely takes care of the type of this record, not any statuses used for overlays.
     *
     * see EXT:core/Configuration/TCA/pages.php for an example with the TCA table "pages"
     *
     * @param string $table The TCA table
     * @param array $row The selected record
     * @internal
     * @TODO: make this method protected, after FormEngine doesn't need it anymore.
     * @return string The icon identifier string for the icon of that DB record
     */
    public function mapRecordTypeToIconIdentifier(string $table, array $row): string
    {
        $recordType = [];
        $ref = null;

        if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_column'])) {
            $column = $GLOBALS['TCA'][$table]['ctrl']['typeicon_column'];
            if (isset($row[$column])) {
                // even if not properly documented the value of the typeicon_column in a record could be
                // an array (multiselect) in typeicon_classes a key could consist of a comma-separated string "foo,bar"
                // but mostly it should be only one entry in that array
                if (is_array($row[$column])) {
                    $recordType[1] = implode(',', $row[$column]);
                } else {
                    $recordType[1] = $row[$column];
                }
            } else {
                $recordType[1] = 'default';
            }
            // Workaround to give nav_hide pages a complete different icon
            // Although it's not a separate doctype
            // and to give root-pages an own icon
            if ($table === 'pages') {
                if (($row['nav_hide'] ?? 0) > 0) {
                    $recordType[2] = $this->getRecordTypeForPageType(
                        $recordType[1],
                        'hideinmenu',
                        $table
                    );
                }
                if (($row['is_siteroot'] ?? 0) > 0) {
                    $recordType[3] = $this->getRecordTypeForPageType(
                        $recordType[1],
                        'root',
                        $table
                    );
                }
                if (!empty($row['module'])) {
                    if (is_array($row['module'])) {
                        // field 'module' is configured as type 'select' in the TCA,
                        // so the value may have already been converted to an array
                        $moduleSuffix = reset($row['module']);
                    } else {
                        $moduleSuffix = $row['module'];
                    }
                    $recordType[4] = 'contains-' . $moduleSuffix;
                }
                $contentFromPid = is_array($row['content_from_pid'] ?? 0) ? ($row['content_from_pid'][0]['uid'] ?? 0) : $row['content_from_pid'] ?? 0;
                if ($contentFromPid > 0) {
                    if ($row['is_siteroot'] ?? false) {
                        $recordType[4] = $this->getRecordTypeForPageType(
                            $recordType[1],
                            'contentFromPid-root',
                            $table
                        );
                    } else {
                        $suffix = (int)$row['nav_hide'] === 0 ? 'contentFromPid' : 'contentFromPid-hideinmenu';
                        $recordType[4] = $this->getRecordTypeForPageType($recordType[1], $suffix, $table, 'page');
                    }
                }
            }
            if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
                && is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
            ) {
                foreach ($recordType as $key => $type) {
                    if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type])) {
                        $recordType[$key] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type];
                    } else {
                        unset($recordType[$key]);
                    }
                }
                $recordType[0] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'] ?? '';
                if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['mask'])
                    && isset($row[$column]) && is_string($row[$column])
                ) {
                    $recordType[5] = str_replace(
                        '###TYPE###',
                        $row[$column] ?? '',
                        $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['mask']
                    );
                }
                if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['userFunc'])) {
                    $parameters = ['row' => $row];
                    $recordType[6] = GeneralUtility::callUserFunction(
                        $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['userFunc'],
                        $parameters,
                        $ref
                    );
                }
            } else {
                foreach ($recordType as &$type) {
                    $type = 'tcarecords-' . $table . '-' . $type;
                }
                unset($type);
                $recordType[0] = 'tcarecords-' . $table . '-default';
            }
        } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
            && is_array($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'])
        ) {
            $recordType[0] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'];
        } else {
            $recordType[0] = 'tcarecords-' . $table . '-default';
        }

        if (($row['CType'] ?? '') === 'list' && ($row['list_type'] ?? '') !== '') {
            $pluginIcon = $this->getIconForPlugin($row['list_type']);
            if ($pluginIcon) {
                $recordType[7] = $pluginIcon;
            }
        }

        krsort($recordType);
        foreach ($recordType as $iconName) {
            if ($this->iconRegistry->isRegistered($iconName)) {
                return $iconName;
            }
        }

        return $this->iconRegistry->getDefaultIconIdentifier();
    }

    /**
     * Returns a possible configured icon for the given plugin name
     */
    protected function getIconForPlugin(string $pluginName): ?string
    {
        $result = null;
        $items = $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['items'] ?? [];
        foreach ($items as $item) {
            if ($item['value'] === $pluginName) {
                $result = $item['icon'];
                break;
            }
        }

        return $result;
    }

    /**
     * Returns recordType for icon based on a typeName and a suffix.
     * Fallback to page as typeName if resulting type is not configured.
     */
    protected function getRecordTypeForPageType(string $typeName, string $suffix, string $table, string $fallbackTypeName = '1'): string
    {
        $recordType = $typeName . '-' . $suffix;

        // Check if typeicon class exists. If not fallback to page as typeName
        if (!isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$recordType])) {
            $recordType = $fallbackTypeName . '-' . $suffix;
        }
        return $recordType;
    }

    /**
     * This helper function checks if the DB record ($row) has any special status based on the TCA settings
     * like hidden, starttime etc, and then returns a specific icon overlay identifier for the overlay of this DB record
     * This method solely takes care of the overlay of this record, not any type
     *
     * @param string $table The TCA table
     * @param array $row The selected record
     * @return string The status with the highest priority
     */
    protected function mapRecordTypeToOverlayIdentifier(string $table, array $row): string
    {
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'] ?? [];
        // Calculate for a given record the actual visibility at the moment
        $status = [
            'hidden' => false,
            'starttime' => false,
            'endtime' => false,
            'futureendtime' => false,
            'fe_group' => false,
            'deleted' => false,
            'protectedSection' => false,
            'nav_hide' => !empty($row['nav_hide']),
        ];
        // Icon state based on "enableFields":
        if (isset($tcaCtrl['enablecolumns']) && is_array($tcaCtrl['enablecolumns'])) {
            $enableColumns = $tcaCtrl['enablecolumns'];
            // If "hidden" is enabled:
            if (isset($enableColumns['disabled']) && !empty($row[$enableColumns['disabled']])) {
                $status['hidden'] = true;
            }
            // If a "starttime" is set and higher than current time:
            if (!empty($enableColumns['starttime']) && $GLOBALS['EXEC_TIME'] < (int)($row[$enableColumns['starttime']] ?? 0)) {
                $status['starttime'] = true;
            }
            // If an "endtime" is set
            if (!empty($enableColumns['endtime'])) {
                if ((int)($row[$enableColumns['endtime']] ?? 0) > 0) {
                    if ((int)$row[$enableColumns['endtime']] < $GLOBALS['EXEC_TIME']) {
                        // End-timing applies at this point.
                        $status['endtime'] = true;
                    } else {
                        // End-timing WILL apply in the future for this element.
                        $status['futureendtime'] = true;
                    }
                }
            }
            // If a user-group field is set
            if (!empty($enableColumns['fe_group']) && !empty($row[$enableColumns['fe_group']])) {
                $status['fe_group'] = true;
            }
        }
        // If "deleted" flag is set (only when listing records which are also deleted!)
        if (isset($tcaCtrl['delete']) && !empty($row[$tcaCtrl['delete']])) {
            $status['deleted'] = true;
        }
        // Detecting extendToSubpages (for pages only)
        if ($table === 'pages' && (int)($row['extendToSubpages'] ?? 0) > 0) {
            $status['protectedSection'] = true;
        }
        if (VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER) {
            $status['deleted'] = true;
        }

        // Now only show the status with the highest priority
        $iconName = '';
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['overlayPriorities'] ?? [] as $priority) {
            if ($status[$priority]) {
                if (!$GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['recordStatusMapping'][$priority]) {
                    throw new \LogicException('Priority ' . $priority . ' is not configured', 1719756056);
                }
                $iconName = $GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['recordStatusMapping'][$priority];
                break;
            }
        }

        return $this->eventDispatcher->dispatch(
            new ModifyRecordOverlayIconIdentifierEvent($iconName, $table, $row, $status)
        )->getOverlayIconIdentifier();
    }

    /**
     * Get Icon for a file by its extension
     *
     * @todo: Change $size to allow IconSize only in v14
     */
    public function getIconForFileExtension(string $fileExtension, string|IconSize $size = IconSize::MEDIUM, ?string $overlayIdentifier = null): Icon
    {
        if (is_string($size)) {
            $size = IconSize::from($size);
            $size->triggerDeprecation();
        }
        $iconName = $this->iconRegistry->getIconIdentifierForFileExtension($fileExtension);
        return $this->getIcon($iconName, $size, $overlayIdentifier);
    }

    /**
     * This method is used throughout the TYPO3 Backend to show icons for files and folders
     *
     * The method takes care of the translation of file extension to proper icon and for folders
     * it will return the icon depending on the role of the folder.
     *
     * If the given resource is a folder there are some additional options that can be used:
     *  - mount-root => TRUE (to indicate this is the root of a mount)
     *  - folder-open => TRUE (to indicate that the folder is opened in the file tree)
     *
     * There is a hook in place to manipulate the icon name and overlays.
     *
     * @param array $options An associative array with additional options.
     *
     * @todo: Change $size to allow IconSize only in v14
     */
    public function getIconForResource(
        ResourceInterface $resource,
        string|IconSize $size = IconSize::MEDIUM,
        ?string $overlayIdentifier = null,
        array $options = []
    ): Icon {
        $iconIdentifier = null;

        // Folder
        if ($resource instanceof FolderInterface) {
            // non browsable storage
            if ($resource->getStorage()->isBrowsable() === false && !empty($options['mount-root'])) {
                $iconIdentifier = 'apps-filetree-folder-locked';
            } else {
                // storage root
                if ($resource->getStorage()->getRootLevelFolder()->getIdentifier() === $resource->getIdentifier()) {
                    $iconIdentifier = 'apps-filetree-root';
                }

                $role = is_callable([$resource, 'getRole']) ? $resource->getRole() : '';

                // user/group mount root
                if (!empty($options['mount-root'])) {
                    $iconIdentifier = 'apps-filetree-mount';
                    if ($role === FolderInterface::ROLE_READONLY_MOUNT) {
                        $overlayIdentifier = 'overlay-locked';
                    } elseif ($role === FolderInterface::ROLE_USER_MOUNT) {
                        $overlayIdentifier = 'overlay-restricted';
                    }
                }

                if ($iconIdentifier === null) {
                    // in folder tree view $options['folder-open'] can define an open folder icon
                    if (!empty($options['folder-open'])) {
                        $iconIdentifier = 'apps-filetree-folder-opened';
                    } else {
                        $iconIdentifier = 'apps-filetree-folder-default';
                    }

                    if ($role === FolderInterface::ROLE_TEMPORARY) {
                        $iconIdentifier = 'apps-filetree-folder-temp';
                    } elseif ($role === FolderInterface::ROLE_RECYCLER) {
                        $iconIdentifier = 'apps-filetree-folder-recycler';
                    }
                }

                // if locked add overlay
                if ($resource instanceof InaccessibleFolder ||
                    !$resource->getStorage()->isBrowsable() ||
                    !$resource->getStorage()->checkFolderActionPermission('add', $resource)
                ) {
                    $overlayIdentifier = 'overlay-locked';
                }
            }
        } elseif ($resource instanceof File) {
            $mimeTypeIcon = $this->iconRegistry->getIconIdentifierForMimeType($resource->getMimeType());

            // Check if we find an exact matching mime type
            if ($mimeTypeIcon !== null) {
                $iconIdentifier = $mimeTypeIcon;
            } else {
                $fileExtensionIcon = $this->iconRegistry->getIconIdentifierForFileExtension($resource->getExtension());
                if ($fileExtensionIcon !== 'mimetypes-other-other') {
                    // Fallback 1: icon by file extension
                    $iconIdentifier = $fileExtensionIcon;
                } else {
                    // Fallback 2: icon by mime type with subtype replaced by *
                    $mimeTypeParts = explode('/', $resource->getMimeType());
                    $mimeTypeIcon = $this->iconRegistry->getIconIdentifierForMimeType($mimeTypeParts[0] . '/*');
                    if ($mimeTypeIcon !== null) {
                        $iconIdentifier = $mimeTypeIcon;
                    } else {
                        // Fallback 3: use 'mimetypes-other-other'
                        $iconIdentifier = $fileExtensionIcon;
                    }
                }
            }
            if ($resource->isMissing()) {
                $overlayIdentifier = 'overlay-missing';
            }
        }

        if (is_string($size)) {
            $size = IconSize::from($size);
            $size->triggerDeprecation();
        }

        $event = $this->eventDispatcher->dispatch(
            new ModifyIconForResourcePropertiesEvent(
                $resource,
                $size,
                $options,
                $iconIdentifier,
                $overlayIdentifier
            )
        );
        return $this->getIcon($event->getIconIdentifier(), $size, $event->getOverlayIdentifier());
    }

    /**
     * Creates an icon object
     *
     * @param array $iconConfiguration the icon configuration array
     */
    protected function createIcon(string $identifier, IconSize $size, ?string $overlayIdentifier = null, array $iconConfiguration = []): Icon
    {
        $icon = GeneralUtility::makeInstance(Icon::class);
        $icon->setIdentifier($identifier);
        $icon->setSize($size);
        $iconState = IconState::tryFrom($iconConfiguration['state']) ?? IconState::STATE_DEFAULT;
        $icon->setState($iconState);
        if (!empty($overlayIdentifier)) {
            $icon->setOverlayIcon($this->getIcon($overlayIdentifier, IconSize::OVERLAY));
        }
        if (!empty($iconConfiguration['options']['spinning'])) {
            $icon->setSpinning(true);
        }
        return $icon;
    }

    /**
     * @internal Remove in v14. May have been used during testing in TYPO3 <v14.
     */
    public function clearIconCache(): void {}
}
