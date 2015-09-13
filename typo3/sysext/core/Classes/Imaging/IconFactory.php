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

use TYPO3\CMS\Core\Type\Icon\IconState;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * The main factory class, which acts as the entrypoint for generating an Icon object which
 * is responsible for rendering an icon. Checks for the correct icon provider through the IconRegistry.
 */
class IconFactory {

	/**
	 * @var IconRegistry
	 */
	protected $iconRegistry;

	/**
	 * @var string[]
	 */
	protected $fileExtensionMapping = array(
		'htm' => 'mimetypes-text-html',
		'html' => 'mimetypes-text-html',
		'css' => 'mimetypes-text-css',
		'js' => 'mimetypes-text-js',
		'csv' => 'mimetypes-text-csv',
		'php' => 'mimetypes-text-php',
		'php6' => 'mimetypes-text-php',
		'php5' => 'mimetypes-text-php',
		'php4' => 'mimetypes-text-php',
		'php3' => 'mimetypes-text-php',
		'inc' => 'mimetypes-text-php',
		'ts' => 'mimetypes-text-ts',
		'txt' => 'mimetypes-text-text',
		'class' => 'mimetypes-text-text',
		'tmpl' => 'mimetypes-text-text',
		'jpg' => 'mimetypes-media-image',
		'jpeg' => 'mimetypes-media-image',
		'gif' => 'mimetypes-media-image',
		'png' => 'mimetypes-media-image',
		'bmp' => 'mimetypes-media-image',
		'tif' => 'mimetypes-media-image',
		'tiff' => 'mimetypes-media-image',
		'tga' => 'mimetypes-media-image',
		'psd' => 'mimetypes-media-image',
		'eps' => 'mimetypes-media-image',
		'ai' => 'mimetypes-media-image',
		'svg' => 'mimetypes-media-image',
		'pcx' => 'mimetypes-media-image',
		'avi' => 'mimetypes-media-video',
		'mpg' => 'mimetypes-media-video',
		'mpeg' => 'mimetypes-media-video',
		'mov' => 'mimetypes-media-video',
		'wav' => 'mimetypes-media-audio',
		'mp3' => 'mimetypes-media-audio',
		'mid' => 'mimetypes-media-audio',
		'swf' => 'mimetypes-media-flash',
		'swa' => 'mimetypes-media-flash',
		'exe' => 'mimetypes-executable-executable',
		'com' => 'mimetypes-executable-executable',
		't3x' => 'mimetypes-compressed',
		't3d' => 'mimetypes-compressed',
		'zip' => 'mimetypes-compressed',
		'tgz' => 'mimetypes-compressed',
		'gz' => 'mimetypes-compressed',
		'pdf' => 'mimetypes-pdf',
		'doc' => 'mimetypes-word',
		'dot' => 'mimetypes-word',
		'docm' => 'mimetypes-word',
		'docx' => 'mimetypes-word',
		'dotm' => 'mimetypes-word',
		'dotx' => 'mimetypes-word',
		'sxw' => 'mimetypes-word',
		'rtf' => 'mimetypes-word',
		'xls' => 'mimetypes-excel',
		'xlsm' => 'mimetypes-excel',
		'xlsx' => 'mimetypes-excel',
		'xltm' => 'mimetypes-excel',
		'xltx' => 'mimetypes-excel',
		'sxc' => 'mimetypes-excel',
		'pps' => 'mimetypes-powerpoint',
		'ppsx' => 'mimetypes-powerpoint',
		'ppt' => 'mimetypes-powerpoint',
		'pptm' => 'mimetypes-powerpoint',
		'pptx' => 'mimetypes-powerpoint',
		'potm' => 'mimetypes-powerpoint',
		'potx' => 'mimetypes-powerpoint',
		'mount' => 'apps-filetree-mount',
		'folder' => 'apps-filetree-folder-default',
		'default' => 'mimetypes-other-other',
	);

	/**
	 * @param IconRegistry $iconRegistry
	 */
	public function __construct(IconRegistry $iconRegistry = NULL) {
		$this->iconRegistry = $iconRegistry ? $iconRegistry : GeneralUtility::makeInstance(IconRegistry::class);
	}

	/**
	 * @param string $identifier
	 * @param string $size "large", "small" or "default", see the constants of the Icon class
	 * @param string $overlayIdentifier
	 * @param IconState $state
	 * @return Icon
	 */
	public function getIcon($identifier, $size = Icon::SIZE_DEFAULT, $overlayIdentifier = NULL, IconState $state = NULL) {
		if ($this->iconRegistry->isDeprecated($identifier)) {
			$deprecationSettings = $this->iconRegistry->getDeprecationSettings($identifier);
			GeneralUtility::deprecationLog(sprintf($deprecationSettings['message'], $identifier));
			if (!empty($deprecationSettings['replacement'])) {
				$identifier = $deprecationSettings['replacement'];
			}
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

		return $icon;
	}

	/**
	 * Get Icon for a file by its extension
	 *
	 * @param string $fileExtension
	 * @param string $size "large" "small" or "default", see the constants of the Icon class
	 * @param string $overlayIdentifier
	 * @return Icon
	 */
	public function getIconForFileExtension($fileExtension, $size = Icon::SIZE_DEFAULT, $overlayIdentifier = NULL) {
		$iconName = $this->getIconIdentifierForFileExtension($fileExtension);
		return $this->getIcon($iconName, $size, $overlayIdentifier);
	}

	/**
	 * @param string $fileExtension
	 *
	 * @return string
	 */
	protected function getIconIdentifierForFileExtension($fileExtension) {
		// If the file extension is not valid use the default one
		if (!isset($this->fileExtensionMapping[$fileExtension])) {
			$fileExtension = 'default';
		}
		return $this->fileExtensionMapping[$fileExtension];
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
	public function getIconForResource(ResourceInterface $resource, $size = Icon::SIZE_DEFAULT, $overlayIdentifier = NULL, array $options = array()) {
		$iconIdentifier = NULL;

		// Folder
		if ($resource instanceof FolderInterface) {
			// non browsable storage
			if ($resource->getStorage()->isBrowsable() === FALSE && !empty($options['mount-root'])) {
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

				if ($iconIdentifier === NULL) {
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

			// File
		} else {
			if ($resource instanceof File && $resource->isMissing()) {
				$overlayIdentifier = 'overlay-missing';
			}
			$iconIdentifier = $this->getIconIdentifierForFileExtension($resource->getExtension());
		}

		unset($options['mount-root']);
		unset($options['folder-open']);
		list($iconIdentifier, $overlayIdentifier) = $this->emitBuildIconForResourceSignal($resource, $size, $options, $iconIdentifier, $overlayIdentifier);
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
	protected function createIcon($identifier, $size, $overlayIdentifier = NULL, array $iconConfiguration) {
		$icon = GeneralUtility::makeInstance(Icon::class);
		$icon->setIdentifier($identifier);
		$icon->setSize($size);
		$icon->setState($iconConfiguration['state'] ?: new IconState());
		if ($overlayIdentifier !== NULL) {
			$icon->setOverlayIcon($this->getIcon($overlayIdentifier, Icon::SIZE_OVERLAY));
		}
		if (!empty($iconConfiguration['options']['spinning'])) {
			$icon->setSpinning(TRUE);
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
	 *
	 * @return mixed
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	protected function emitBuildIconForResourceSignal(ResourceInterface $resource, $size, array $options, $iconIdentifier, $overlayIdentifier) {
		$result = $this->getSignalSlotDispatcher()->dispatch(IconFactory::class, 'buildIconForResourceSignal', array($resource, $size, $options, $iconIdentifier, $overlayIdentifier));
		$iconIdentifier = $result[3];
		$overlayIdentifier = $result[4];
		return array($iconIdentifier, $overlayIdentifier);
	}

	/**
	 * Get the SignalSlot dispatcher
	 *
	 * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		return GeneralUtility::makeInstance(Dispatcher::class);
	}

}
