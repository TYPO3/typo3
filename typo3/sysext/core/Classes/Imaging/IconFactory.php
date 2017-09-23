<?php
namespace TYPO3\CMS\Core\Imaging;

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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * The main factory class, which acts as the entrypoint for generating an Icon object which
 * is responsible for rendering an icon. Checks for the correct icon provider through the IconRegistry.
 */
class IconFactory
{
    /**
     * @var IconRegistry
     */
    protected $iconRegistry;

    /**
     * Mapping of record status to overlays.
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['recordStatusMapping']
     *
     * @var string[]
     */
    protected $recordStatusMapping = [];

    /**
     * Order of priorities for overlays.
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['overlayPriorities']
     *
     * @var string[]
     */
    protected $overlayPriorities = [];

    /**
     * Runtime icon cache
     *
     * @var array
     */
    protected static $iconCache = [];

    /**
     * @param IconRegistry $iconRegistry
     */
    public function __construct(IconRegistry $iconRegistry = null)
    {
        $this->iconRegistry = $iconRegistry ? $iconRegistry : GeneralUtility::makeInstance(IconRegistry::class);
        $this->recordStatusMapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['recordStatusMapping'];
        $this->overlayPriorities = $GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['overlayPriorities'];
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return string
     * @internal
     */
    public function processAjaxRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $requestedIcon = json_decode(
            isset($parsedBody['icon']) ? $parsedBody['icon'] : $queryParams['icon'],
            true
        );

        list($identifier, $size, $overlayIdentifier, $iconState, $alternativeMarkupIdentifier) = $requestedIcon;
        if (empty($overlayIdentifier)) {
            $overlayIdentifier = null;
        }
        $iconState = IconState::cast($iconState);
        $response->getBody()->write(
            $this->getIcon($identifier, $size, $overlayIdentifier, $iconState)->render($alternativeMarkupIdentifier)
        );
        $response = $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    /**
     * @param string $identifier
     * @param string $size "large", "small" or "default", see the constants of the Icon class
     * @param string $overlayIdentifier
     * @param IconState $state
     * @return Icon
     */
    public function getIcon($identifier, $size = Icon::SIZE_DEFAULT, $overlayIdentifier = null, IconState $state = null)
    {
        $cacheIdentifier = md5($identifier . $size . $overlayIdentifier . (string)$state);
        if (!empty(static::$iconCache[$cacheIdentifier])) {
            return static::$iconCache[$cacheIdentifier];
        }
        if (!$this->iconRegistry->isRegistered($identifier)) {
            $identifier = $this->iconRegistry->getDefaultIconIdentifier();
        }

        $iconConfiguration = $this->iconRegistry->getIconConfigurationByIdentifier($identifier);
        $iconConfiguration['state'] = $state;
        $icon = $this->createIcon($identifier, $size, $overlayIdentifier, $iconConfiguration);

        /** @var IconProviderInterface $iconProvider */
        $iconProvider = GeneralUtility::makeInstance($iconConfiguration['provider']);
        $iconProvider->prepareIconMarkup($icon, $iconConfiguration['options']);

        static::$iconCache[$cacheIdentifier] = $icon;

        return $icon;
    }

    /**
     * This method is used throughout the TYPO3 Backend to show icons for a DB record
     *
     * @param string $table The TCA table name
     * @param array $row The DB record of the TCA table
     * @param string $size "large" "small" or "default", see the constants of the Icon class
     * @return Icon
     */
    public function getIconForRecord($table, array $row, $size = Icon::SIZE_DEFAULT)
    {
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
    public function mapRecordTypeToIconIdentifier($table, array $row)
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
                if ((int)$row['nav_hide'] > 0) {
                    $recordType[2] = $recordType[1] . '-hideinmenu';
                }
                if ((int)$row['is_siteroot'] > 0) {
                    $recordType[3] = $recordType[1] . '-root';
                }
                if (!empty($row['module'])) {
                    $recordType[4] = 'contains-' . $row['module'];
                }
                if ((int)$row['content_from_pid'] > 0) {
                    if ($row['is_siteroot']) {
                        $recordType[4] = 'page-contentFromPid-root';
                    } else {
                        $recordType[4] = (int)$row['nav_hide'] === 0
                            ? 'page-contentFromPid' : 'page-contentFromPid-hideinmenu';
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
                $recordType[0] = $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['default'];
                if (isset($GLOBALS['TCA'][$table]['ctrl']['typeicon_classes']['mask'])) {
                    $recordType[5] = str_replace(
                        '###TYPE###',
                        $row[$column],
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

        krsort($recordType);
        foreach ($recordType as $iconName) {
            if ($this->iconRegistry->isRegistered($iconName)) {
                return $iconName;
            }
        }

        return $this->iconRegistry->getDefaultIconIdentifier();
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
    protected function mapRecordTypeToOverlayIdentifier($table, array $row)
    {
        $tcaCtrl = $GLOBALS['TCA'][$table]['ctrl'];
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
            if (!empty($enableColumns['starttime']) && $GLOBALS['EXEC_TIME'] < (int)$row[$enableColumns['starttime']]) {
                $status['starttime'] = true;
            }
            // If an "endtime" is set
            if (!empty($enableColumns['endtime'])) {
                if ((int)$row[$enableColumns['endtime']] > 0) {
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
            if (!empty($enableColumns['fe_group']) && $row[$enableColumns['fe_group']]) {
                $status['fe_group'] = true;
            }
        }
        // If "deleted" flag is set (only when listing records which are also deleted!)
        if (isset($tcaCtrl['delete']) && !empty($row[$tcaCtrl['delete']])) {
            $status['deleted'] = true;
        }
        // Detecting extendToSubpages (for pages only)
        if ($table === 'pages' && (int)$row['extendToSubpages'] > 0) {
            $status['protectedSection'] = true;
        }
        if (isset($row['t3ver_state'])
            && VersionState::cast($row['t3ver_state'])->equals(VersionState::DELETE_PLACEHOLDER)) {
            $status['deleted'] = true;
        }

        // Now only show the status with the highest priority
        $iconName = '';
        foreach ($this->overlayPriorities as $priority) {
            if ($status[$priority]) {
                $iconName = $this->recordStatusMapping[$priority];
                break;
            }
        }

        // Hook to define an alternative iconName
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['overrideIconOverlay'])) {
            $hookObjects = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::class]['overrideIconOverlay'];
            foreach ($hookObjects as $classRef) {
                $hookObject = GeneralUtility::getUserObj($classRef);
                if (method_exists($hookObject, 'postOverlayPriorityLookup')) {
                    $iconName = $hookObject->postOverlayPriorityLookup($table, $row, $status, $iconName);
                }
            }
        }

        return $iconName;
    }

    /**
     * Get Icon for a file by its extension
     *
     * @param string $fileExtension
     * @param string $size "large" "small" or "default", see the constants of the Icon class
     * @param string $overlayIdentifier
     * @return Icon
     */
    public function getIconForFileExtension($fileExtension, $size = Icon::SIZE_DEFAULT, $overlayIdentifier = null)
    {
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
     * @param ResourceInterface $resource
     * @param string $size "large" "small" or "default", see the constants of the Icon class
     * @param string $overlayIdentifier
     * @param array $options An associative array with additional options.
     * @return Icon
     */
    public function getIconForResource(
        ResourceInterface $resource,
        $size = Icon::SIZE_DEFAULT,
        $overlayIdentifier = null,
        array $options = []
    ) {
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

            // Check if we find a exact matching mime type
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

        unset($options['mount-root']);
        unset($options['folder-open']);
        list($iconIdentifier, $overlayIdentifier) =
            $this->emitBuildIconForResourceSignal($resource, $size, $options, $iconIdentifier, $overlayIdentifier);
        return $this->getIcon($iconIdentifier, $size, $overlayIdentifier);
    }

    /**
     * Creates an icon object
     *
     * @param string $identifier
     * @param string $size "large", "small" or "default", see the constants of the Icon class
     * @param string $overlayIdentifier
     * @param array $iconConfiguration the icon configuration array
     * @return Icon
     */
    protected function createIcon($identifier, $size, $overlayIdentifier = null, array $iconConfiguration = [])
    {
        $icon = GeneralUtility::makeInstance(Icon::class);
        $icon->setIdentifier($identifier);
        $icon->setSize($size);
        $icon->setState($iconConfiguration['state'] ?: new IconState());
        if (!empty($overlayIdentifier)) {
            $icon->setOverlayIcon($this->getIcon($overlayIdentifier, Icon::SIZE_OVERLAY));
        }
        if (!empty($iconConfiguration['options']['spinning'])) {
            $icon->setSpinning(true);
        }

        return $icon;
    }

    /**
     * Emits a signal right after the identifiers are built.
     *
     * @param ResourceInterface $resource
     * @param string $size
     * @param array $options
     * @param string $iconIdentifier
     * @param string $overlayIdentifier
     * @return mixed
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function emitBuildIconForResourceSignal(
        ResourceInterface $resource,
        $size,
        array $options,
        $iconIdentifier,
        $overlayIdentifier
    ) {
        $result = $this->getSignalSlotDispatcher()->dispatch(
            self::class,
            'buildIconForResourceSignal',
            [$resource, $size, $options, $iconIdentifier, $overlayIdentifier]
        );
        $iconIdentifier = $result[3];
        $overlayIdentifier = $result[4];
        return [$iconIdentifier, $overlayIdentifier];
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        return GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * clear icon cache
     */
    public function clearIconCache()
    {
        static::$iconCache = [];
    }
}
